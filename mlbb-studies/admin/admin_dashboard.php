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
    .dashboard-container {
      background: #181c24;
      border-radius: 18px;
      padding: 36px 32px;
      margin-top: 48px;
      box-shadow: 0 8px 36px rgba(110,168,254,0.13);
      border: 1.5px solid #6ea8fe33;
      max-width: 1100px;
      margin-left: auto;
      margin-right: auto;
    }
    .dashboard-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 28px;
    }
    .dashboard-header i {
      font-size: 2rem;
      color: #6ea8fe;
    }
    .dashboard-label {
      font-weight: 600;
      color: #bfc9d1;
      margin-top: 8px;
      margin-bottom: 2px;
    }
    .dashboard-value {
      font-size: 2.1rem;
      font-weight: bold;
      color: #ffffff;
    }
    .dashboard-box {
      background: #181c24;
      border: 1.5px solid #6ea8fe33;
      border-radius: 14px;
      padding: 30px 24px;
      text-align: center;
      box-shadow: 0 4px 24px rgba(110,168,254,0.07);
      transition: 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    .dashboard-box:before {
      content: "";
      position: absolute;
      top: -30px;
      right: -30px;
      width: 90px;
      height: 90px;
      background: radial-gradient(circle, #6ea8fe22 60%, transparent 100%);
      z-index: 0;
    }
    .dashboard-box:hover {
      background: #202534;
      transform: translateY(-4px) scale(1.03);
      box-shadow: 0 8px 36px rgba(110,168,254,0.15);
    }
    .dashboard-icon {
      font-size: 2.4rem;
      color: #6ea8fe;
      margin-bottom: 12px;
      z-index: 1;
      position: relative;
    }
    @media (max-width: 991px) {
      .dashboard-container {
        padding: 20px 5px;
      }
      .dashboard-header h2 {
        font-size: 1.3rem;
      }
    }
    @media (max-width: 768px) {
      .dashboard-value {
        font-size: 1.4rem;
      }
      .dashboard-icon {
        font-size: 1.7rem;
      }
      .dashboard-box {
        padding: 18px 8px;
      }
    }
  </style>
</head>
<body>
  
  <?php include '../navbar/admin_navbar.php'; ?>
  
  <?php include '../navbar/admin_sidepanel.php'; ?>

  <div class="container dashboard-container">
    <div class="dashboard-header">
      <i class="bi bi-speedometer2"></i>
      <h2 class="text-light m-0">Admin Dashboard</h2>
    </div>
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