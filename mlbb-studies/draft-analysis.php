<?php
// filepath: c:\xampp\htdocs\mlbb-studies\draft-analysis.php

session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);
$success = $error = "";

// Fetch user's teams
$mysqli = get_db_connection();
$stmt = $mysqli->prepare("SELECT id, team_name, image FROM teams WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$teams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$mysqli->close();

// Fetch all heroes grouped by role and build robust name<->id maps
$api_url = "https://mlbb-stats.ridwaanhall.com/api/hero-position/?role=all&lane=all&size=128&index=1";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$groupedHeroes = [];
$heroNameToId = [];
$heroIdToName = [];

if (isset($data['code']) && $data['code'] == 0) {
    $records = $data['data']['records'];
    foreach ($records as $record) {
        $heroData = $record['data']['hero']['data'] ?? null;
        if (!$heroData) continue;

        $heroName = $heroData['name'] ?? 'Unknown';
        $heroid = $record['data']['hero_id'] ?? ($heroData['heroid'] ?? null);
        $roles = array_map(fn($r) => $r['data']['sort_title'] ?? 'Unknown', $heroData['sortid'] ?? []);
        $primaryRole = $roles[0] ?? 'Unknown';
        $groupedHeroes[$primaryRole][] = $heroName;
        if ($heroid) {
            $key = strtolower(preg_replace('/[^a-z0-9]/', '', $heroName));
            $heroNameToId[$key] = $heroid;
            $heroIdToName[$heroid] = $heroName;
        }
    }
}

// Helper to get team name by ID
function get_team_name($teams, $id) {
    foreach ($teams as $t) {
        if ($t['id'] == $id) return $t['team_name'];
    }
    return '';
}

// Fetch hero counter data from API
function fetch_hero_counter($heroid) {
    $url = "https://mlbb-stats.ridwaanhall.com/api/hero-counter/$heroid/";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Fetch hero compatibility data from API
function fetch_hero_compatibility($heroid) {
    $url = "https://mlbb-stats.ridwaanhall.com/api/hero-compatibility/$heroid/";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Handle draft submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['your_team_id'], $_POST['enemy_team_id'])) {
    $your_team_id = intval($_POST['your_team_id']);
    $enemy_team_id = intval($_POST['enemy_team_id']);
    $pick_order = $_POST['pick_order'] ?? '';
    $winner = $_POST['winner'] ?? '';
    $your_picks = $_POST['your_team'] ?? [];
    $enemy_picks = $_POST['enemy_team'] ?? [];
    $your_bans = $_POST['your_bans'] ?? [];
    $enemy_bans = $_POST['enemy_bans'] ?? [];

    if ($your_team_id === $enemy_team_id) {
        $error = "You cannot select the same team for both sides.";
    } elseif (
        !$pick_order || !$winner ||
        count($your_picks) !== 5 || count($enemy_picks) !== 5 ||
        count($your_bans) !== 5 || count($enemy_bans) !== 5
    ) {
        $error = "Please complete all picks, bans, and select a winner.";
    } else {
        // Assign JSON to variables before binding
        $your_picks_json = json_encode($your_picks);
        $enemy_picks_json = json_encode($enemy_picks);
        $your_bans_json = json_encode($your_bans);
        $enemy_bans_json = json_encode($enemy_bans);

        // Save draft to database
        $mysqli = get_db_connection();
        $stmt = $mysqli->prepare("INSERT INTO drafts (user_id, your_team_id, enemy_team_id, pick_order, your_picks, enemy_picks, your_bans, enemy_bans, winner) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            'iiissssss',
            $user_id,
            $your_team_id,
            $enemy_team_id,
            $pick_order,
            $your_picks_json,
            $enemy_picks_json,
            $your_bans_json,
            $enemy_bans_json,
            $winner
        );
        if ($stmt->execute()) {
            $success = "Draft saved successfully!";
        } else {
            $error = "Failed to save draft.";
        }
        $stmt->close();
        $mysqli->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MLBB Draft Tool with Bans</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
      body {
        background: #181c24;
        font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
      }
      .card {
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
      }
      .select2-container .select2-selection--single {
        height: 38px;
        padding: 6px 12px;
      }
      .form-label {
        font-weight: 600;
      }
      /* Loading overlay styles */
      #loading-overlay {
        position: fixed;
        z-index: 9999;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(24,28,36,0.97);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        transition: opacity 0.3s;
      }
      #loading-overlay .spinner-border {
        width: 3.5rem;
        height: 3.5rem;
        color: #6ea8fe;
        margin-bottom: 18px;
      }
      #loading-overlay .loading-text {
        color: #e9ecef;
        font-size: 1.25rem;
        font-weight: 500;
        letter-spacing: 1px;
      }
      #loading-overlay.hide {
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.4s;
      }
      /* Analysis UI styles */
      .analysis-section-title {
          font-size: 1.35rem;
          font-weight: 700;
          letter-spacing: 0.5px;
          margin-bottom: 1.2rem;
          color: #6ea8fe;
          display: flex;
          align-items: center;
          gap: 0.5rem;
      }
      .analysis-team-card {
          background: #232837;
          border-radius: 16px;
          box-shadow: 0 2px 16px #6ea8fe11;
          border: 1.5px solid #6ea8fe22;
          margin-bottom: 2rem;
      }
      .analysis-team-header {
          font-weight: 600;
          font-size: 1.13rem;
          padding: 0.85rem 1.2rem;
          border-bottom: 1.5px solid #6ea8fe22;
          background: #181c24;
          border-radius: 16px 16px 0 0;
          display: flex;
          align-items: center;
          gap: 0.7rem;
      }
      .analysis-team-header.info { color: #6ea8fe; }
      .analysis-team-header.danger { color: #ff5c5c; }
      .analysis-team-body { padding: 1.2rem 1.2rem 1rem 1.2rem; }
      .analysis-hero-block {
          background: #181c24;
          border-radius: 10px;
          padding: 0.8rem 1rem;
          margin-bottom: 1.1rem;
          border: 1.5px solid #6ea8fe22;
          display: flex;
          align-items: flex-start;
          gap: 1rem;
      }
      .analysis-hero-img {
          width: 38px;
          height: 38px;
          border-radius: 8px;
          object-fit: cover;
          border: 2px solid #6ea8fe44;
          background: #232837;
          margin-right: 0.5rem;
      }
      .analysis-hero-title {
          font-weight: 600;
          color: #fff;
          font-size: 1.07rem;
          margin-bottom: 0.2rem;
      }
      .analysis-section-sub {
          font-weight: 600;
          font-size: 1.01rem;
          margin-top: 0.5rem;
          margin-bottom: 0.2rem;
      }
      .analysis-counter-row {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          margin-bottom: 0.3rem;
          padding-left: 0.2rem;
      }
      .analysis-counter-img {
          width: 28px;
          height: 28px;
          border-radius: 6px;
          object-fit: cover;
          border: 1.5px solid #6ea8fe44;
          background: #232837;
      }
      .analysis-counter-name {
          font-weight: 500;
          color: #e9ecef;
          font-size: 0.98rem;
          margin-right: 0.3rem;
      }
      .analysis-badge {
          font-size: 0.93em;
          padding: 2px 8px;
          border-radius: 6px;
          margin-left: 0.2rem;
      }
      .analysis-badge.win { background: #198754; color: #fff; }
      .analysis-badge.lose { background: #dc3545; color: #fff; }
      .analysis-badge.inc { background: #6ea8fe; color: #181c24; }
      .analysis-badge.dec { background: #ff5c5c; color: #fff; }
      .analysis-muted { color: #bfc9d1; font-size: 0.97em; }
      @media (max-width: 768px) {
          .analysis-team-body { padding: 1rem 0.5rem 0.7rem 0.5rem; }
          .analysis-team-header { font-size: 1rem; padding: 0.7rem 0.7rem; }
      }
  </style>
</head>
<body>

<?php include 'navbar/navbar.php'; ?>

<!-- Loading Overlay -->
<div id="loading-overlay">
  <div class="spinner-border" role="status"></div>
  <div class="loading-text">Saving and Loading hero data...</div>
</div>

<div class="container py-5" id="main-content" style="opacity:0;">
  <div class="card p-4">
    <h2 class="text-center mb-4 text-primary">MLBB Drafting Analysis Tool</h2>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="row mb-4">
        <div class="col-md-6">
          <label for="your_team_id" class="form-label">Your Team</label>
          <select class="form-select" id="your_team_id" name="your_team_id" required>
            <option value="">-- Select Your Team --</option>
            <?php foreach ($teams as $team): ?>
              <option value="<?= $team['id'] ?>" <?= (isset($_POST['your_team_id']) && $_POST['your_team_id'] == $team['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($team['team_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label for="enemy_team_id" class="form-label">Enemy Team</label>
          <select class="form-select" id="enemy_team_id" name="enemy_team_id" required>
            <option value="">-- Select Enemy Team --</option>
            <?php foreach ($teams as $team): ?>
              <option value="<?= $team['id'] ?>" <?= (isset($_POST['enemy_team_id']) && $_POST['enemy_team_id'] == $team['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($team['team_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="mb-4">
        <label for="pick_order" class="form-label">First Pick Order</label>
        <select name="pick_order" id="pick_order" class="form-select" required>
          <option value="" disabled <?= !isset($_POST['pick_order']) ? 'selected' : '' ?>>-- Select First Pick --</option>
          <option value="your_team" <?= (isset($_POST['pick_order']) && $_POST['pick_order'] == 'your_team') ? 'selected' : '' ?>>
            <?= isset($_POST['your_team_id']) && $_POST['your_team_id'] ? htmlspecialchars(get_team_name($teams, $_POST['your_team_id'])) : 'Your Team' ?> (First Pick)
          </option>
          <option value="enemy_team" <?= (isset($_POST['pick_order']) && $_POST['pick_order'] == 'enemy_team') ? 'selected' : '' ?>>
            <?= isset($_POST['enemy_team_id']) && $_POST['enemy_team_id'] ? htmlspecialchars(get_team_name($teams, $_POST['enemy_team_id'])) : 'Enemy Team' ?> (First Pick)
          </option>
        </select>
      </div>

      <div class="mb-4">
        <label for="winner" class="form-label">Who Won?</label>
        <select name="winner" id="winner" class="form-select" required>
          <option value="" disabled selected>-- Select Winner --</option>
          <option value="your_team" <?= (isset($_POST['winner']) && $_POST['winner'] == 'your_team') ? 'selected' : '' ?>>
            <?= isset($_POST['your_team_id']) ? htmlspecialchars(get_team_name($teams, $_POST['your_team_id'])) : 'Your Team' ?>
          </option>
          <option value="enemy_team" <?= (isset($_POST['winner']) && $_POST['winner'] == 'enemy_team') ? 'selected' : '' ?>>
            <?= isset($_POST['enemy_team_id']) ? htmlspecialchars(get_team_name($teams, $_POST['enemy_team_id'])) : 'Enemy Team' ?>
          </option>
        </select>
      </div>

      <div class="row">
        <!-- Your Team Picks -->
        <div class="col-md-6">
          <h5 class="text-success"><?= isset($_POST['your_team_id']) ? htmlspecialchars(get_team_name($teams, $_POST['your_team_id'])) : 'Your Team' ?> - Picks</h5>
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <div class="mb-3">
              <label class="form-label">Pick <?= $i ?></label>
              <select name="your_team[]" class="form-select select2-hero" required>
                <option value="">Select Hero</option>
                <?php foreach ($groupedHeroes as $role => $heroes): ?>
                  <optgroup label="<?= htmlspecialchars($role) ?>">
                    <?php foreach ($heroes as $hero): ?>
                      <option value="<?= htmlspecialchars($hero) ?>" <?= (isset($_POST['your_team'][$i-1]) && $_POST['your_team'][$i-1] == $hero) ? 'selected' : '' ?>><?= htmlspecialchars($hero) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endfor; ?>
        </div>

        <!-- Enemy Team Picks -->
        <div class="col-md-6">
          <h5 class="text-danger"><?= isset($_POST['enemy_team_id']) ? htmlspecialchars(get_team_name($teams, $_POST['enemy_team_id'])) : 'Enemy Team' ?> - Picks</h5>
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <div class="mb-3">
              <label class="form-label">Pick <?= $i ?></label>
              <select name="enemy_team[]" class="form-select select2-hero" required>
                <option value="">Select Hero</option>
                <?php foreach ($groupedHeroes as $role => $heroes): ?>
                  <optgroup label="<?= htmlspecialchars($role) ?>">
                    <?php foreach ($heroes as $hero): ?>
                      <option value="<?= htmlspecialchars($hero) ?>" <?= (isset($_POST['enemy_team'][$i-1]) && $_POST['enemy_team'][$i-1] == $hero) ? 'selected' : '' ?>><?= htmlspecialchars($hero) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endfor; ?>
        </div>
      </div>

      <hr>

      <div class="row mt-4">
        <!-- Your Team Bans -->
        <div class="col-md-6">
          <h5 class="text-success"><?= isset($_POST['your_team_id']) ? htmlspecialchars(get_team_name($teams, $_POST['your_team_id'])) : 'Your Team' ?> - Bans</h5>
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <div class="mb-3">
              <label class="form-label">Ban <?= $i ?></label>
              <select name="your_bans[]" class="form-select select2-hero" required>
                <option value="">Select Hero</option>
                <?php foreach ($groupedHeroes as $role => $heroes): ?>
                  <optgroup label="<?= htmlspecialchars($role) ?>">
                    <?php foreach ($heroes as $hero): ?>
                      <option value="<?= htmlspecialchars($hero) ?>" <?= (isset($_POST['your_bans'][$i-1]) && $_POST['your_bans'][$i-1] == $hero) ? 'selected' : '' ?>><?= htmlspecialchars($hero) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endfor; ?>
        </div>

        <!-- Enemy Team Bans -->
        <div class="col-md-6">
          <h5 class="text-danger"><?= isset($_POST['enemy_team_id']) ? htmlspecialchars(get_team_name($teams, $_POST['enemy_team_id'])) : 'Enemy Team' ?> - Bans</h5>
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <div class="mb-3">
              <label class="form-label">Ban <?= $i ?></label>
              <select name="enemy_bans[]" class="form-select select2-hero" required>
                <option value="">Select Hero</option>
                <?php foreach ($groupedHeroes as $role => $heroes): ?>
                  <optgroup label="<?= htmlspecialchars($role) ?>">
                    <?php foreach ($heroes as $hero): ?>
                      <option value="<?= htmlspecialchars($hero) ?>" <?= (isset($_POST['enemy_bans'][$i-1]) && $_POST['enemy_bans'][$i-1] == $hero) ? 'selected' : '' ?>><?= htmlspecialchars($hero) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endfor; ?>
        </div>
      </div>

      <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary px-5">Save Draft</button>
      </div>
    </form>

    <?php if ($_SERVER["REQUEST_METHOD"] === "POST" && !$error): ?>
      <!-- Analysis UI START -->
      <?php
        // Determine left (first pick, blue) and right (second pick, red) sides
        if ($_POST['pick_order'] === 'your_team') {
            $left_team_id = $_POST['your_team_id'];
            $left_team_name = htmlspecialchars(get_team_name($teams, $_POST['your_team_id']));
            $left_picks = $_POST['your_team'];
            $left_bans = $_POST['your_bans'];
            $right_team_id = $_POST['enemy_team_id'];
            $right_team_name = htmlspecialchars(get_team_name($teams, $_POST['enemy_team_id']));
            $right_picks = $_POST['enemy_team'];
            $right_bans = $_POST['enemy_bans'];
        } elseif ($_POST['pick_order'] === 'enemy_team') {
            $left_team_id = $_POST['enemy_team_id'];
            $left_team_name = htmlspecialchars(get_team_name($teams, $_POST['enemy_team_id']));
            $left_picks = $_POST['enemy_team'];
            $left_bans = $_POST['enemy_bans'];
            $right_team_id = $_POST['your_team_id'];
            $right_team_name = htmlspecialchars(get_team_name($teams, $_POST['your_team_id']));
            $right_picks = $_POST['your_team'];
            $right_bans = $_POST['your_bans'];
        } else {
            $left_team_id = $_POST['your_team_id'];
            $left_team_name = htmlspecialchars(get_team_name($teams, $_POST['your_team_id']));
            $left_picks = $_POST['your_team'];
            $left_bans = $_POST['your_bans'];
            $right_team_id = $_POST['enemy_team_id'];
            $right_team_name = htmlspecialchars(get_team_name($teams, $_POST['enemy_team_id']));
            $right_picks = $_POST['enemy_team'];
            $right_bans = $_POST['enemy_bans'];
        }
      ?>
      <div class="mb-4 mt-5">
        <div class="card shadow-sm border-0" style="background:#232837;">
          <div class="card-body">
            <div class="analysis-section-title"><i class="bi bi-clipboard-data"></i>Draft Summary</div>
            <ul class="list-group list-group-flush mb-3" style="background:transparent;">
              <li class="list-group-item bg-transparent text-light">
                <strong>Pick Order:</strong>
                <?php
                  echo "First Pick (<span class='text-info fw-bold'>" . $left_team_name . "</span>)";
                ?>
              </li>
              <li class="list-group-item bg-transparent text-light">
                <strong>Second Pick:</strong>
                <?php
                  echo "<span class='text-danger fw-bold'>" . $right_team_name . "</span>";
                ?>
              </li>
              <li class="list-group-item bg-transparent text-light">
                <strong>Winner:</strong>
                <?php
                  if ($_POST['winner'] == 'your_team') {
                      $winner_name = htmlspecialchars(get_team_name($teams, $_POST['your_team_id']));
                  } elseif ($_POST['winner'] == 'enemy_team') {
                      $winner_name = htmlspecialchars(get_team_name($teams, $_POST['enemy_team_id']));
                  } else {
                      $winner_name = "Unknown";
                  }
                  // Color winner badge blue if left, red if right
                  if ($winner_name === $left_team_name) {
                      echo "<span class='analysis-badge win' style='background:#6ea8fe;color:#181c24'>" . $winner_name . "</span>";
                  } elseif ($winner_name === $right_team_name) {
                      echo "<span class='analysis-badge win' style='background:#ff5c5c;color:#fff'>" . $winner_name . "</span>";
                  } else {
                      echo $winner_name;
                  }
                ?>
              </li>
              <li class="list-group-item bg-transparent text-light">
                <strong><span class='text-info fw-bold'><?= $left_team_name ?></span> Picks:</strong>
                <span class="text-info"><?= implode(', ', array_map('htmlspecialchars', $left_picks)) ?></span>
              </li>
              <li class="list-group-item bg-transparent text-light">
                <strong><span class='text-danger fw-bold'><?= $right_team_name ?></span> Picks:</strong>
                <span class="text-danger"><?= implode(', ', array_map('htmlspecialchars', $right_picks)) ?></span>
              </li>
              <li class="list-group-item bg-transparent text-light">
                <strong><span class='text-info fw-bold'><?= $left_team_name ?></span> Bans:</strong>
                <span class="text-info"><?= implode(', ', array_map('htmlspecialchars', $left_bans)) ?></span>
              </li>
              <li class="list-group-item bg-transparent text-light">
                <strong><span class='text-danger fw-bold'><?= $right_team_name ?></span> Bans:</strong>
                <span class="text-danger"><?= implode(', ', array_map('htmlspecialchars', $right_bans)) ?></span>
              </li>
            </ul>
            <div class="alert alert-info mt-3 mb-0">🧠 Add counter analysis, ban justification, or synergy scoring here.</div>
          </div>
        </div>
      </div>

      <div class="analysis-section-title"><i class="bi bi-shield-shaded"></i>Hero Counter Analysis</div>
      <div class="row g-4">
      <?php
      $all_picks = [
          [
              'team' => $left_team_name,
              'picks' => $left_picks,
              'color' => 'info'
          ],
          [
              'team' => $right_team_name,
              'picks' => $right_picks,
              'color' => 'danger'
          ]
      ];
      foreach ($all_picks as $side) {
          echo '<div class="col-md-6">';
          echo '<div class="analysis-team-card">';
          echo '<div class="analysis-team-header ' . $side['color'] . '"><i class="bi bi-people"></i>' . $side['team'] . ' (Hero Counter)</div>';
          echo '<div class="analysis-team-body">';
          foreach ($side['picks'] as $pick) {
              $key = strtolower(preg_replace('/[^a-z0-9]/', '', $pick));
              $heroid = $heroNameToId[$key] ?? null;
              // Hero block with image
              $hero_img = '';
              if ($heroid && isset($data['data']['records'])) {
                  foreach ($data['data']['records'] as $record) {
                      $heroData = $record['data']['hero']['data'] ?? null;
                      if ($heroData && strtolower($heroData['name']) === strtolower($pick)) {
                          $hero_img = $heroData['smallmap'] ?? '';
                          break;
                      }
                  }
              }
              echo '<div class="analysis-hero-block">';
              if ($hero_img) {
                  echo '<img src="' . htmlspecialchars($hero_img) . '" class="analysis-hero-img" alt="' . htmlspecialchars($pick) . '">';
              } else {
                  echo '<img src="https://via.placeholder.com/38x38?text=?" class="analysis-hero-img" alt="?">';
              }
              echo '<div style="flex:1">';
              echo '<div class="analysis-hero-title">' . htmlspecialchars($pick) . '</div>';
              if ($heroid) {
                  $counter_data = fetch_hero_counter($heroid);
                  if (isset($counter_data['code']) && $counter_data['code'] == 0 && !empty($counter_data['data']['records'][0]['data'])) {
                      $info = $counter_data['data']['records'][0]['data'];
                      // Strong Against
                      echo '<div class="analysis-section-sub text-success">Strong Against (Counters)</div>';
                      if (!empty($info['sub_hero'])) {
                          foreach (array_slice($info['sub_hero'], 0, 3) as $counter) {
                              $img = $counter['hero']['data']['head'] ?? '';
                              $wr = isset($counter['hero_win_rate']) ? round($counter['hero_win_rate']*100,2) : 0;
                              $inc = isset($counter['increase_win_rate']) ? round($counter['increase_win_rate']*100,2) : 0;
                              echo "<div class='analysis-counter-row text-white'>";
                              if ($img) echo "<img src='" . htmlspecialchars($img) . "' class='analysis-counter-img' alt='Hero'>";
                              echo "<span class='me-2'>WR: {$wr}%</span>";
                              echo "<span class='analysis-badge " . ($inc>=0?'inc':'dec') . "'>".($inc>=0?'+':'')."{$inc}%</span>";
                              echo "</div>";
                          }
                      } else {
                          echo '<div class="analysis-muted">No data.</div>';
                      }
                      // Weak Against
                      echo '<div class="analysis-section-sub text-danger mt-2">Weak Against (Countered By)</div>';
                      if (!empty($info['sub_hero_last'])) {
                          foreach (array_slice($info['sub_hero_last'], 0, 3) as $counter) {
                              $img = $counter['hero']['data']['head'] ?? '';
                              $wr = isset($counter['hero_win_rate']) ? round($counter['hero_win_rate']*100,2) : 0;
                              $inc = isset($counter['increase_win_rate']) ? round($counter['increase_win_rate']*100,2) : 0;
                              echo "<div class='analysis-counter-row text-white'>";
                              if ($img) echo "<img src='" . htmlspecialchars($img) . "' class='analysis-counter-img' alt='Hero'>";
                              echo "<span class='me-2'>WR: {$wr}%</span>";
                              echo "<span class='analysis-badge " . ($inc>=0?'inc':'dec') . "'>".($inc>=0?'+':'')."{$inc}%</span>";
                              echo "</div>";
                          }
                      } else {
                          echo '<div class="analysis-muted">No data.</div>';
                      }
                  } else {
                      echo '<div class="text-warning mt-2" style="font-size:0.97em;">No counter data available for this hero. (Check spelling or API update)</div>';
                  }
              } else {
                  echo '<div class="text-warning mt-2" style="font-size:0.97em;">No counter data available for this hero. (Check spelling or API update)</div>';
              }
              echo '</div></div>';
          }
          echo '</div></div></div>';
      }
      ?>
      </div>

      <div class="analysis-section-title"><i class="bi bi-people-fill"></i>Hero Compatibility Analysis</div>
      <div class="row g-4">
      <?php
      foreach ($all_picks as $side) {
          echo '<div class="col-md-6">';
          echo '<div class="analysis-team-card">';
          echo '<div class="analysis-team-header ' . $side['color'] . '"><i class="bi bi-people"></i>' . $side['team'] . ' (Hero Compatibility)</div>';
          echo '<div class="analysis-team-body">';
          foreach ($side['picks'] as $pick) {
              $key = strtolower(preg_replace('/[^a-z0-9]/', '', $pick));
              $heroid = $heroNameToId[$key] ?? null;
              // Hero block with image
              $hero_img = '';
              if ($heroid && isset($data['data']['records'])) {
                  foreach ($data['data']['records'] as $record) {
                      $heroData = $record['data']['hero']['data'] ?? null;
                      if ($heroData && strtolower($heroData['name']) === strtolower($pick)) {
                          $hero_img = $heroData['smallmap'] ?? '';
                          break;
                      }
                  }
              }
              echo '<div class="analysis-hero-block">';
              if ($hero_img) {
                  echo '<img src="' . htmlspecialchars($hero_img) . '" class="analysis-hero-img" alt="' . htmlspecialchars($pick) . '">';
              } else {
                  echo '<img src="https://via.placeholder.com/38x38?text=?" class="analysis-hero-img" alt="?">';
              }
              echo '<div style="flex:1">';
              echo '<div class="analysis-hero-title">' . htmlspecialchars($pick) . '</div>';
              if ($heroid) {
                  $compat_data = fetch_hero_compatibility($heroid);
                  if (isset($compat_data['code']) && $compat_data['code'] == 0 && !empty($compat_data['data']['records'][0]['data'])) {
                      $info = $compat_data['data']['records'][0]['data'];
                      // Good Partners
                      echo '<div class="analysis-section-sub text-success">Good Partners</div>';
                      if (!empty($info['sub_hero'])) {
                          foreach (array_slice($info['sub_hero'], 0, 3) as $compat) {
                              $img = $compat['hero']['data']['head'] ?? '';
                              $wr = isset($compat['hero_win_rate']) ? round($compat['hero_win_rate']*100,2) : 0;
                              $inc = isset($compat['increase_win_rate']) ? round($compat['increase_win_rate']*100,2) : 0;
                              echo "<div class='analysis-counter-row text-white'>";
                              if ($img) echo "<img src='" . htmlspecialchars($img) . "' class='analysis-counter-img' alt='Hero'>";
                              echo "<span class='me-2'>WR: {$wr}%</span>";
                              echo "<span class='analysis-badge " . ($inc>=0?'inc':'dec') . "'>".($inc>=0?'+':'')."{$inc}%</span>";
                              echo "</div>";
                          }
                      } else {
                          echo '<div class="analysis-muted">No data.</div>';
                      }
                      // Bad Partners
                      echo '<div class="analysis-section-sub text-danger mt-2">Bad Partners</div>';
                      if (!empty($info['sub_hero_last'])) {
                          foreach (array_slice($info['sub_hero_last'], 0, 3) as $compat) {
                              $img = $compat['hero']['data']['head'] ?? '';
                              $wr = isset($compat['hero_win_rate']) ? round($compat['hero_win_rate']*100,2) : 0;
                              $inc = isset($compat['increase_win_rate']) ? round($compat['increase_win_rate']*100,2) : 0;
                              echo "<div class='analysis-counter-row text-white'>";
                              if ($img) echo "<img src='" . htmlspecialchars($img) . "' class='analysis-counter-img' alt='Hero'>";
                              echo "<span class='me-2'>WR: {$wr}%</span>";
                              echo "<span class='analysis-badge " . ($inc>=0?'inc':'dec') . "'>".($inc>=0?'+':'')."{$inc}%</span>";
                              echo "</div>";
                          }
                      } else {
                          echo '<div class="analysis-muted">No data.</div>';
                      }
                  } else {
                      echo '<div class="text-warning mt-2" style="font-size:0.97em;">No compatibility data available for this hero. (Check spelling or API update)</div>';
                  }
              } else {
                  echo '<div class="text-warning mt-2" style="font-size:0.97em;">No compatibility data available for this hero. (Check spelling or API update)</div>';
              }
              echo '</div></div>';
          }
          echo '</div></div></div>';
      }
      ?>
      </div>
      <!-- Analysis UI END -->
    <?php endif; ?>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// No repeated selected team
$(document).ready(function () {
    function updateTeamOptions() {
        var yourTeam = $('#your_team_id').val();
        var enemyTeam = $('#enemy_team_id').val();

        // Enable all options first
        $('#your_team_id option, #enemy_team_id option').prop('disabled', false);

        // Disable selected enemy in your_team and vice versa
        if (enemyTeam) {
            $('#your_team_id option[value="' + enemyTeam + '"]').prop('disabled', true);
        }
        if (yourTeam) {
            $('#enemy_team_id option[value="' + yourTeam + '"]').prop('disabled', true);
        }
    }

    $('#your_team_id, #enemy_team_id').on('change', updateTeamOptions);
    updateTeamOptions();
});
</script>
<script>
  $(document).ready(function () {
    $('.select2-hero').select2({
      width: '100%',
      placeholder: "Select a Hero"
    });

    function updateHeroOptions() {
      const selectedHeroes = new Set();

      // Gather all selected values from all dropdowns
      $('.select2-hero').each(function () {
        const val = $(this).val();
        if (val) selectedHeroes.add(val);
      });

      // Re-enable all options first
      $('.select2-hero option').prop('disabled', false);

      // Disable selected options in other selects
      $('.select2-hero').each(function () {
        const $select = $(this);
        const currentVal = $select.val();

        $select.find('option').each(function () {
          const optionVal = $(this).val();
          if (optionVal && selectedHeroes.has(optionVal) && optionVal !== currentVal) {
            $(this).prop('disabled', true);
          }
        });
      });
    }

    // Trigger on change
    $('.select2-hero').on('change', updateHeroOptions);

    // Initial run
    updateHeroOptions();
  });
</script>

<script>
  // Show loading overlay until page and images are loaded
  document.addEventListener('DOMContentLoaded', function() {
    // Wait for all images in #main-content to load
    const mainContent = document.getElementById('main-content');
    const images = mainContent.querySelectorAll('img');
    let loaded = 0;
    if (images.length === 0) {
      finishLoading();
    } else {
      images.forEach(img => {
        if (img.complete) {
          loaded++;
          if (loaded === images.length) finishLoading();
        } else {
          img.addEventListener('load', checkLoaded);
          img.addEventListener('error', checkLoaded);
        }
      });
    }
    function checkLoaded() {
      loaded++;
      if (loaded === images.length) finishLoading();
    }
    function finishLoading() {
      document.getElementById('loading-overlay').classList.add('hide');
      setTimeout(() => {
        document.getElementById('loading-overlay').style.display = 'none';
        mainContent.style.opacity = 1;
      }, 400);
    }
  });
</script>

</body>
</html>