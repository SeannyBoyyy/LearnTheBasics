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
  <title>Admin Analytics - MLBB Studies</title>
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
    .analytics-container {
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
    .analytics-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 28px;
    }
    .analytics-header i {
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
      font-size: 2rem;
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
      font-size: 2.2rem;
      color: #6ea8fe;
      margin-bottom: 12px;
      z-index: 1;
      position: relative;
    }
    .chart-container { 
      margin: 40px auto 0 auto;
      padding: 18px 24px 24px 24px;
      background: #202534;
      border-radius: 14px;
      box-shadow: 0 2px 12px rgba(110,168,254,0.09);
      max-width: 900px;
    }
    .chart-container h3 {
      color: #6ea8fe;
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 18px;
      letter-spacing: 0.01em;
    }
    canvas { 
      background: #2e2e3e; 
      border-radius: 10px; 
      padding: 10px;
    }
    @media (max-width: 991px) {
      .analytics-container {
        padding: 20px 5px;
      }
      .dashboard-value {
        font-size: 1.2rem;
      }
    }
    @media (max-width: 768px) {
      .chart-container {
        padding: 10px 2px;
      }
      .dashboard-icon {
        font-size: 1.3rem;
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

  <div class="container analytics-container">
    <div class="analytics-header">
      <i class="bi bi-bar-chart-line"></i>
      <h2 class="text-light m-0">Chart Analytics</h2>
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
    </div>
    <div class="mt-5 chart-container">
      <h3><i class="bi bi-calendar-range"></i> Daily New Users and Drafts</h3>
      <canvas id="dailyChart"></canvas>
    </div>
    <div class="mt-5 chart-container">
      <h3><i class="bi bi-person-lines-fill"></i> Teams Created Per User</h3>
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
                backgroundColor: 'rgba(46, 204, 113, 0.7)',
                borderColor: '#2ecc71',
                borderWidth: 1,
                borderRadius: 6,
                barPercentage: 0.7
            },
            {
                label: 'Drafts Created',
                data: dailyDrafts,
                backgroundColor: 'rgba(52, 152, 219, 0.7)',
                borderColor: '#3498db',
                borderWidth: 1,
                borderRadius: 6,
                barPercentage: 0.7
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
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return `${context.dataset.label}: ${context.parsed.y}`;
                    }
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
            backgroundColor: 'rgba(231, 76, 60, 0.7)',
            borderColor: '#e74c3c',
            borderWidth: 1,
            borderRadius: 6,
            barPercentage: 0.7
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
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return `Teams Created: ${context.parsed.y}`;
                    }
                }
            }
        }
    }
});
</script>
</body>
</html>