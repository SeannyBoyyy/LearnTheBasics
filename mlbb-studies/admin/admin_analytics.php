<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
// Fetch totals from all relevant tables
$mysqli = get_db_connection();

// Totals
function getCount($m, $t){ return $m->query("SELECT COUNT(*) AS cnt FROM $t")->fetch_assoc()['cnt']; }
$total_users = getCount($mysqli, 'users');
$total_teams = getCount($mysqli, 'teams');
$total_drafts = getCount($mysqli, 'drafts');
$total_team_notes = getCount($mysqli, 'team_notes');
$total_match_notes = getCount($mysqli, 'match_notes');

// Daily counts: last 30 days
$q = "
  SELECT DATE(created_at) AS dt,
    COUNT(DISTINCT CASE WHEN table_name='users' THEN id END) AS users,
    COUNT(DISTINCT CASE WHEN table_name='drafts' THEN id END) AS drafts
  FROM (
    SELECT id, created_at, 'users' AS table_name FROM users
    UNION ALL
    SELECT id, created_at, 'drafts' AS table_name FROM drafts
  ) AS all_data
  WHERE created_at >= CURDATE() - INTERVAL 29 DAY
  GROUP BY dt
  ORDER BY dt;
";
// --- Chart 1: Daily Breakdown (Users & Drafts) ---
$res = $mysqli->query("
    SELECT dt, 
        SUM(users) AS users, 
        SUM(drafts) AS drafts
    FROM (
        SELECT DATE(created_at) AS dt, COUNT(*) AS users, 0 AS drafts FROM users
        WHERE created_at >= CURDATE() - INTERVAL 29 DAY
        GROUP BY dt
        UNION ALL
        SELECT DATE(created_at), 0, COUNT(*) FROM drafts
        WHERE created_at >= CURDATE() - INTERVAL 29 DAY
        GROUP BY DATE(created_at)
    ) AS combined
    GROUP BY dt
    ORDER BY dt ASC
");

$dates = $daily_users = $daily_drafts = [];
while ($row = $res->fetch_assoc()) {
    $dates[] = $row['dt'];
    $daily_users[] = (int)$row['users'];
    $daily_drafts[] = (int)$row['drafts'];
}

// --- Chart 2: Per User Team Count ---
$res2 = $mysqli->query("
    SELECT u.username, COUNT(t.id) AS team_count
    FROM users u
    LEFT JOIN teams t ON t.user_id = u.id
    GROUP BY u.id
");

$usernames = $team_counts = [];
while ($row = $res2->fetch_assoc()) {
    $usernames[] = $row['username'];
    $team_counts[] = (int)$row['team_count'];
}

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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    .chart-container { 
      margin: 40px auto; 
    }
    canvas { 
      background: #2e2e3e; 
      border-radius: 10px; 
      padding: 10px;
    }
  </style>
</head>
<body>
  
  <?php include '../navbar/admin_navbar.php'; ?>

  <div class="container mt-5">
    <h2 class="text-center text-light mb-4"><i class="bi bi-speedometer2"></i> Chart Analytics </h2>
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
    </div>
    <div class="mt-5 chart-container">
      <h3>Daily New Users and Drafts</h3>
      <canvas id="dailyChart"></canvas>
    </div>
    <div class="mt-5 chart-container">
      <h3>Teams Created Per User</h3>
      <canvas id="perUserChart"></canvas>
    </div>
  </div>
  
<script>
const dates = <?= json_encode($dates) ?>;
const dailyUsers = <?= json_encode($daily_users) ?>;
const dailyDrafts = <?= json_encode($daily_drafts) ?>;
const users = <?= json_encode($usernames) ?>;
const teamCounts = <?= json_encode($team_counts) ?>;

// Chart 1: Daily Activity
const dailyChart = new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: dates,
        datasets: [
            {
                label: 'New Users',
                data: dailyUsers,
                backgroundColor: 'rgba(46, 204, 113, 0.6)',
                borderColor: '#2ecc71',
                borderWidth: 1
            },
            {
                label: 'Drafts Created',
                data: dailyDrafts,
                backgroundColor: 'rgba(52, 152, 219, 0.6)',
                borderColor: '#3498db',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { color: '#fff' },
                grid: { color: '#555' }
            },
            x: {
                ticks: { color: '#fff' },
                grid: { color: '#444' }
            }
        },
        plugins: {
            legend: {
                labels: {
                    color: 'white'
                }
            }
        }
    }
});

// Chart 2: Per User Team Count
const perUserChart = new Chart(document.getElementById('perUserChart'), {
    type: 'bar',
    data: {
        labels: users,
        datasets: [{
            label: 'Teams Created',
            data: teamCounts,
            backgroundColor: 'rgba(231, 76, 60, 0.6)',
            borderColor: '#e74c3c',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { color: '#fff' },
                grid: { color: '#555' }
            },
            x: {
                ticks: { color: '#fff' },
                grid: { color: '#444' }
            }
        },
        plugins: {
            legend: {
                labels: {
                    color: 'white'
                }
            }
        }
    }
});
</script>
</body>
</html>