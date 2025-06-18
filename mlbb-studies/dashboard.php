<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    // Show a friendly message and a signup button if not logged in
    ?>
    <!DOCTYPE html>
    <html lang="en" data-bs-theme="dark">
    <head>
      <meta charset="UTF-8">
      <title>Dashboard - MLBB Studies</title>
      <link rel="icon" href="logo/logo-v2.png" type="image/png">
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
      <style>
        body {
          background: linear-gradient(135deg, #232837 0%, #181c24 100%);
          min-height: 100vh;
          font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        .center-card {
          border-radius: 18px;
          box-shadow: 0 6px 32px rgba(110,168,254,0.13);
          background: #232837;
          padding: 2.5rem 2rem 2rem 2rem;
          max-width: 500px;
          margin: 80px auto;
          border: 1.5px solid #6ea8fe33;
          text-align: center;
        }
        .btn-primary {
          background: linear-gradient(90deg, #6ea8fe 60%, #468be6 100%);
          border: none;
          font-weight: 600;
          border-radius: 8px;
          transition: background 0.18s;
          box-shadow: 0 2px 8px #6ea8fe22;
        }
        .btn-primary:hover {
          background: linear-gradient(90deg, #468be6 60%, #6ea8fe 100%);
        }
      </style>
    </head>
    <body>
      <?php include 'navbar/navbar.php'; ?>
      <div class="center-card">
        <h2 class="mb-3" style="color:#6ea8fe;"><i class="bi bi-speedometer2"></i> Dashboard</h2>
        <p class="mb-4 text-light">
          <strong>Sign up to unlock the full MLBB Draft Analysis experience!</strong><br>
          <span class="text-muted">Create teams, analyze drafts, save your strategies, and more.</span>
        </p>
        <a href="signup.php" class="btn btn-primary px-4 py-2"><i class="bi bi-person-plus-fill me-1"></i>Sign Up Now</a>
        <p class="mt-4 text-secondary" style="font-size:0.98em;">
          Already have an account? <a href="login.php" style="color:#6ea8fe;">Login here</a>
        </p>
      </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}


$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);
$success = $error = "";

// Handle team creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_name'])) {
    $team_name = trim($_POST['team_name']);
    $image_path = null;

    // Handle image upload if provided
    if (isset($_FILES['team_image']) && $_FILES['team_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['team_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $image_path = 'uploads/team_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['team_image']['tmp_name'], $image_path);
        } else {
            $error = "Invalid image format.";
        }
    }

    if (!$error && $team_name) {
        $mysqli = get_db_connection();
        $stmt = $mysqli->prepare("INSERT INTO teams (user_id, team_name, image) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $user_id, $team_name, $image_path);
        if ($stmt->execute()) {
            $success = "Team created successfully!";
        } else {
            $error = "Failed to create team.";
        }
        $stmt->close();
        $mysqli->close();
    } elseif (!$team_name) {
        $error = "Team name is required.";
    }
}

// Fetch user's teams
$mysqli = get_db_connection();
$stmt = $mysqli->prepare("SELECT id, team_name, image, created_at FROM teams WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$teams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$mysqli->close();

// Fetch recent drafts (limit 5)
$mysqli = get_db_connection();
$stmt = $mysqli->prepare("SELECT d.*, 
    t1.team_name AS your_team_name, 
    t2.team_name AS enemy_team_name 
    FROM drafts d 
    JOIN teams t1 ON d.your_team_id = t1.id 
    JOIN teams t2 ON d.enemy_team_id = t2.id 
    WHERE d.user_id=? 
    ORDER BY d.created_at DESC LIMIT 5");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$recent_drafts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - MLBB Studies</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #232837 0%, #181c24 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
    }
    .dashboard-card {
      border-radius: 18px;
      box-shadow: 0 6px 32px rgba(110,168,254,0.13);
      background: #232837;
      padding: 2.5rem 2rem 2rem 2rem;
      max-width: 800px;
      margin: 60px auto;
      border: 1.5px solid #6ea8fe33;
    }
    .dashboard-header {
      display: flex;
      align-items: center;
      gap: 18px;
      margin-bottom: 24px;
    }
    .avatar {
      width: 64px;
      height: 64px;
      border-radius: 16px;
      background: #181c24;
      box-shadow: 0 2px 12px #6ea8fe33;
      object-fit: cover;
      border: 2px solid #6ea8fe44;
    }
    .welcome-title {
      font-weight: 700;
      color: #6ea8fe;
      letter-spacing: 1px;
      text-shadow: 0 2px 8px #0d223a44;
      margin-bottom: 2px;
    }
    .team-card {
      background: #181c24;
      border-radius: 14px;
      box-shadow: 0 2px 12px #6ea8fe11;
      padding: 1.2rem 1rem;
      min-width: 180px;
      text-align: left;
      transition: box-shadow 0.18s, transform 0.15s, background 0.18s;
      border: 1.5px solid #6ea8fe22;
      color: #e9ecef;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .team-card img {
      width: 56px;
      height: 56px;
      border-radius: 12px;
      object-fit: cover;
      background: #232837;
      border: 2px solid #6ea8fe33;
    }
    .team-info {
      flex: 1;
    }
    .team-name {
      font-weight: 600;
      font-size: 1.1em;
      color: #6ea8fe;
      margin-bottom: 2px;
    }
    .team-date {
      color: #bfc9d1;
      font-size: 0.97em;
    }
    .create-team-form {
      background: #181c24;
      border-radius: 14px;
      box-shadow: 0 2px 12px #6ea8fe11;
      padding: 1.5rem 1.2rem;
      margin-bottom: 32px;
      border: 1.5px solid #6ea8fe22;
    }
    .btn-primary {
      background: linear-gradient(90deg, #6ea8fe 60%, #468be6 100%);
      border: none;
      font-weight: 600;
      border-radius: 8px;
      transition: background 0.18s;
      box-shadow: 0 2px 8px #6ea8fe22;
    }
    .btn-primary:hover {
      background: linear-gradient(90deg, #468be6 60%, #6ea8fe 100%);
    }
    .logout-btn {
      position: absolute;
      top: 24px;
      right: 32px;
      background: #232837;
      border: 1.5px solid #6ea8fe33;
      color: #e9ecef;
      border-radius: 8px;
      padding: 8px 18px;
      font-weight: 600;
      transition: background 0.18s, color 0.18s, border 0.18s;
      text-decoration: none;
    }
    .logout-btn:hover {
      background: #6ea8fe;
      color: #181c24;
      border: 1.5px solid #6ea8fe;
      text-decoration: none;
    }
    .recent-drafts-table th, .recent-drafts-table td {
      font-size: 0.98em;
      vertical-align: middle;
    }
    .recent-drafts-table th {
      color: #6ea8fe;
      font-weight: 600;
      background: #181c24;
      border-bottom: 2px solid #6ea8fe33;
    }
    .recent-drafts-table td {
      background: #232837;
      border-bottom: 1px solid #6ea8fe11;
      color: #e9ecef;
    }
    .badge-win {
      background: linear-gradient(90deg, #6ea8fe 60%, #468be6 100%);
      color: #181c24;
      font-weight: 600;
      border-radius: 6px;
      padding: 4px 10px;
      font-size: 0.95em;
    }
    @media (max-width: 768px) {
      .dashboard-card { padding: 1.5rem 0.5rem; }
      .logout-btn { right: 10px; top: 10px; padding: 7px 12px; }
      .recent-drafts-table th, .recent-drafts-table td { font-size: 0.93em; }
    }
  </style>
</head>
<body>

<?php include 'navbar/navbar.php'; ?>

  <div class="dashboard-card position-relative">
    <div class="dashboard-header">
      <img src="https://api.dicebear.com/7.x/identicon/svg?seed=<?= urlencode($username) ?>" class="avatar" alt="Avatar">
      <div>
        <div class="welcome-title">Welcome, <?= $username ?>!</div>
        <div class="text-muted">Manage your teams below.</div>
        <a href="logout.php" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>
    </div>
    <hr style="border-color:#6ea8fe33;">
    <div class="create-team-form mb-4">
      <h5 class="mb-3" style="color:#6ea8fe;">Create a New Team</h5>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="team_name" class="form-label">Team Name</label>
          <input type="text" class="form-control" id="team_name" name="team_name" maxlength="100" required
            value="<?= isset($_POST['team_name']) ? htmlspecialchars($_POST['team_name']) : '' ?>">
        </div>
        <div class="mb-3">
          <label for="team_image" class="form-label">Team Image (optional)</label>
          <input type="file" class="form-control" id="team_image" name="team_image" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary px-4">Create Team</button>
      </form>
    </div>
    <div class="text-center my-4">
      <a href="draft-analysis.php" class="btn btn-success btn-lg px-5">
        <i class="bi bi-lightning-charge-fill me-2"></i>
        Draft Simulation & Analysis
      </a>
    </div>
    <h5 class="mb-3" style="color:#6ea8fe;">Your Teams</h5>
    <?php if (empty($teams)): ?>
      <div class="text-muted">You have not created any teams yet.</div>
    <?php else: ?>
      <?php foreach ($teams as $team): ?>
        <div class="team-card">
          <img src="<?= $team['image'] ? htmlspecialchars($team['image']) : 'https://via.placeholder.com/56x56?text=Team' ?>" alt="Team Image">
          <div class="team-info">
            <div class="team-name"><?= htmlspecialchars($team['team_name']) ?></div>
            <div class="team-date">Created at: <?= htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($team['created_at']))) ?></div>
          </div>
          <a href="view-team.php?id=<?= $team['id'] ?>" class="btn btn-outline-primary btn-sm ms-auto">View Team</a>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <h5 class="mb-3 mt-5" style="color:#6ea8fe;">Recent Drafts</h5>
    <?php if (empty($recent_drafts)): ?>
      <div class="text-muted">No recent drafts yet.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table recent-drafts-table align-middle">
          <thead>
            <tr>
              <th>Date</th>
              <th>Your Team</th>
              <th>Enemy Team</th>
              <th>Pick Order</th>
              <th>Winner</th>
              <th>Picks</th>
              <th>Bans</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_drafts as $draft): ?>
              <tr>
                <td><?= htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($draft['created_at']))) ?></td>
                <td><?= htmlspecialchars($draft['your_team_name']) ?></td>
                <td><?= htmlspecialchars($draft['enemy_team_name']) ?></td>
                <td>
                  <?php
                    if ($draft['pick_order'] == 'your_team') {
                      echo 'First Pick (' . htmlspecialchars($draft['your_team_name']) . ')';
                    } elseif ($draft['pick_order'] == 'enemy_team') {
                      echo 'First Pick (' . htmlspecialchars($draft['enemy_team_name']) . ')';
                    } else {
                      echo '-';
                    }
                  ?>
                </td>
                <td>
                  <?php
                    if ($draft['winner'] == 'your_team') {
                      echo '<span class="badge badge-win">' . htmlspecialchars($draft['your_team_name']) . '</span>';
                    } elseif ($draft['winner'] == 'enemy_team') {
                      echo '<span class="badge badge-win">' . htmlspecialchars($draft['enemy_team_name']) . '</span>';
                    } else {
                      echo '-';
                    }
                  ?>
                </td>
                <td>
                  <span class="d-block"><strong><?= htmlspecialchars($draft['your_team_name']) ?>:</strong> <?= implode(', ', array_map('htmlspecialchars', json_decode($draft['your_picks'], true))) ?></span>
                  <span class="d-block"><strong><?= htmlspecialchars($draft['enemy_team_name']) ?>:</strong> <?= implode(', ', array_map('htmlspecialchars', json_decode($draft['enemy_picks'], true))) ?></span>
                </td>
                <td>
                  <span class="d-block"><strong><?= htmlspecialchars($draft['your_team_name']) ?>:</strong> <?= implode(', ', array_map('htmlspecialchars', json_decode($draft['your_bans'], true))) ?></span>
                  <span class="d-block"><strong><?= htmlspecialchars($draft['enemy_team_name']) ?>:</strong> <?= implode(', ', array_map('htmlspecialchars', json_decode($draft['enemy_bans'], true))) ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
  
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>