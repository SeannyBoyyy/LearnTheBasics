<?php
// filepath: c:\xampp\htdocs\mlbb-studies\analyze-draft.php

session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Forbidden');
}
$user_id = $_SESSION['user_id'];

// Only allow AJAX POST requests
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
) {
    http_response_code(400);
    exit('Bad Request');
}

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
$heroNameToId = [];

if (isset($data['code']) && $data['code'] == 0) {
    $records = $data['data']['records'];
    foreach ($records as $record) {
        $heroData = $record['data']['hero']['data'] ?? null;
        if (!$heroData) continue;
        $heroName = $heroData['name'] ?? 'Unknown';
        $heroid = $record['data']['hero_id'] ?? ($heroData['heroid'] ?? null);
        if ($heroid) {
            $key = strtolower(preg_replace('/[^a-z0-9]/', '', $heroName));
            $heroNameToId[$key] = $heroid;
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

// Get POST data (from view-draft.php form)
$your_team_id = $_POST['your_team_id'] ?? '';
$enemy_team_id = $_POST['enemy_team_id'] ?? '';
$pick_order = $_POST['pick_order'] ?? '';
$winner = $_POST['winner'] ?? '';
$your_picks = $_POST['your_team'] ?? [];
$enemy_picks = $_POST['enemy_team'] ?? [];
$your_bans = $_POST['your_bans'] ?? [];
$enemy_bans = $_POST['enemy_bans'] ?? [];

// Determine left (first pick, blue) and right (second pick, red) sides
if ($pick_order === 'your_team') {
    $left_team_id = $your_team_id;
    $left_team_name = htmlspecialchars(get_team_name($teams, $your_team_id));
    $left_picks = $your_picks;
    $left_bans = $your_bans;
    $right_team_id = $enemy_team_id;
    $right_team_name = htmlspecialchars(get_team_name($teams, $enemy_team_id));
    $right_picks = $enemy_picks;
    $right_bans = $enemy_bans;
} elseif ($pick_order === 'enemy_team') {
    $left_team_id = $enemy_team_id;
    $left_team_name = htmlspecialchars(get_team_name($teams, $enemy_team_id));
    $left_picks = $enemy_picks;
    $left_bans = $enemy_bans;
    $right_team_id = $your_team_id;
    $right_team_name = htmlspecialchars(get_team_name($teams, $your_team_id));
    $right_picks = $your_picks;
    $right_bans = $your_bans;
} else {
    // fallback: default order
    $left_team_id = $your_team_id;
    $left_team_name = htmlspecialchars(get_team_name($teams, $your_team_id));
    $left_picks = $your_picks;
    $left_bans = $your_bans;
    $right_team_id = $enemy_team_id;
    $right_team_name = htmlspecialchars(get_team_name($teams, $enemy_team_id));
    $right_picks = $enemy_picks;
    $right_bans = $enemy_bans;
}
?>
<style>
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

<div class="mb-4">
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
            if ($winner == 'your_team') {
                $winner_name = htmlspecialchars(get_team_name($teams, $your_team_id));
            } elseif ($winner == 'enemy_team') {
                $winner_name = htmlspecialchars(get_team_name($teams, $enemy_team_id));
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
                        echo "<div class='analysis-counter-row'>";
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
                        echo "<div class='analysis-counter-row'>";
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
                        echo "<div class='analysis-counter-row'>";
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
                        echo "<div class='analysis-counter-row'>";
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