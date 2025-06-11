<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
// Fetch totals from all relevant tables
$mysqli = get_db_connection();

function getTotalCount($mysqli, $table) {
    $result = $mysqli->query("SELECT COUNT(*) AS total FROM $table");
    return $result ? $result->fetch_assoc()['total'] : 0;
}

$total_users = getTotalCount($mysqli, 'users');
$total_teams = getTotalCount($mysqli, 'teams');
$total_drafts = getTotalCount($mysqli, 'drafts');
$total_team_notes = getTotalCount($mysqli, 'team_notes');
$total_match_notes = getTotalCount($mysqli, 'match_notes');

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - MLBB Studies</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #232837 0%, #181c24 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
    }
    .dashboard-box {
      background: #181c24;
      border: 1.5px solid #6ea8fe33;
      border-radius: 14px;
      padding: 24px;
      text-align: center;
      box-shadow: 0 4px 24px rgba(110,168,254,0.07);
      transition: 0.3s ease;
    }
    .dashboard-box:hover {
      background: #202534;
      transform: translateY(-4px);
      box-shadow: 0 8px 36px rgba(110,168,254,0.15);
    }
    .dashboard-icon {
      font-size: 32px;
      color: #6ea8fe;
      margin-bottom: 12px;
    }
    .dashboard-label {
      font-weight: 600;
      color: #bfc9d1;
    }
    .dashboard-value {
      font-size: 1.8rem;
      font-weight: bold;
      color: #ffffff;
    }
  </style>
</head>
<body>
  
  <?php include '../navbar/admin_navbar.php'; ?>
  
  <div class="container mt-5">
    <h2 class="text-center text-light mb-4"><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="dashboard-box">
          <div class="dashboard-icon"><i class="bi bi-people-fill"></i></div>
          <div class="dashboard-label">Total Users</div>
          <div class="dashboard-value"><?= $total_users ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="dashboard-box">
          <div class="dashboard-icon"><i class="bi bi-diagram-3-fill"></i></div>
          <div class="dashboard-label">Teams Created</div>
          <div class="dashboard-value"><?= $total_teams ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="dashboard-box">
          <div class="dashboard-icon"><i class="bi bi-journal-code"></i></div>
          <div class="dashboard-label">Total Drafts</div>
          <div class="dashboard-value"><?= $total_drafts ?></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="dashboard-box">
          <div class="dashboard-icon"><i class="bi bi-card-text"></i></div>
          <div class="dashboard-label">Team Notes</div>
          <div class="dashboard-value"><?= $total_team_notes ?></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="dashboard-box">
          <div class="dashboard-icon"><i class="bi bi-clipboard2-pulse"></i></div>
          <div class="dashboard-label">Match Notes</div>
          <div class="dashboard-value"><?= $total_match_notes ?></div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>