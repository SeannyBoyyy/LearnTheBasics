<?php
// Get hero_id from query string, default to 128 (Lukas) if not set or invalid
$hero_id = isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id'] >= 1 && $_GET['id'] <= 128
    ? intval($_GET['id'])
    : 128;

// --------- LOCAL FILES: Use your downloaded hero_cache/ files ---------

$cache_dir = __DIR__ . '/hero_cache/';

// Fetch hero list for dropdown and for hero images (from local hero-list.json)
$hero_list_path = $cache_dir . 'hero-list.json';
if (!file_exists($hero_list_path)) {
    die("Local hero list not found. Please download it using your script.");
}
$hero_list_json = file_get_contents($hero_list_path);
$hero_list = $hero_list_json ? json_decode($hero_list_json, true) : [];

// Build a mapping of heroid => head image for quick lookup
$hero_head_map = [];
foreach ($hero_list as $id => $hero) {
    if (is_array($hero) && isset($hero['head'])) {
        $hero_head_map[$id] = $hero['head'];
    }
}

// Fetch hero detail (from local {id}.json)
$detail_path = $cache_dir . "$hero_id.json";
if (!file_exists($detail_path)) {
    die("Local hero detail for ID $hero_id not found. Please download it using your script.");
}
$response = file_get_contents($detail_path);
$data = json_decode($response, true);

if (!isset($data['data']['records'][0]['data'])) {
    die("Invalid hero detail data.");
}

$record = $data['data']['records'][0]['data'];
$hero = $record['hero']['data'];
$skills = array_merge(...array_column($hero['heroskilllist'], 'skilllist'));
$relation = $record['relation'];

// Painting fix: check both possible locations
if (!empty($record['painting'])) {
    $painting = $record['painting'];
} elseif (!empty($record['hero']['data']['painting'])) {
    $painting = $record['hero']['data']['painting'];
} else {
    $painting = 'https://via.placeholder.com/250x250?text=No+Image';
}

$roles = isset($hero['sortlabel']) ? array_filter($hero['sortlabel']) : [];
$specialities = isset($hero['speciality']) ? $hero['speciality'] : [];

// Function to convert <font color="..."> to <span style="color:...">
function convertFontColorToSpan($html) {
    $html = preg_replace_callback(
        '/<font\s+color=["\']?([a-zA-Z0-9#]+)["\']?>/i',
        function ($matches) {
            $color = $matches[1];
            if (preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
                $color = "#$color";
            }
            return '<span style="color:' . htmlspecialchars($color) . '">';
        },
        $html
    );
    $html = str_ireplace('</font>', '</span>', $html);
    return $html;
}

// --- Hero Detail Stats (from local hero-detail-stats-{id}.json) ---
$detail_stats_path = $cache_dir . "hero-detail-stats-$hero_id.json";
$detail_stats = null;
if (file_exists($detail_stats_path)) {
    $detail_stats_json = file_get_contents($detail_stats_path);
    $detail_stats_data = $detail_stats_json ? json_decode($detail_stats_json, true) : null;
    $detail_stats = $detail_stats_data['data']['records'][0]['data'] ?? null;
}

// --- Hero Counter (from local hero-counter-{id}.json) ---
$counter_path = $cache_dir . "hero-counter-$hero_id.json";
$counter_stats = null;
if (file_exists($counter_path)) {
    $counter_json = file_get_contents($counter_path);
    $counter_data = $counter_json ? json_decode($counter_json, true) : null;
    $counter_stats = $counter_data['data']['records'][0]['data'] ?? null;
}

// --- Hero Compatibility (from local hero-compatibility-{id}.json) ---
$compat_path = $cache_dir . "hero-compatibility-$hero_id.json";
$compat_stats = null;
if (file_exists($compat_path)) {
    $compat_json = file_get_contents($compat_path);
    $compat_data = $compat_json ? json_decode($compat_json, true) : null;
    $compat_stats = $compat_data['data']['records'][0]['data'] ?? null;
}

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($hero['name']) ?> - Hero Statistics</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: #181c24;
      color: #e9ecef;
      font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
    }
    .hero-img {
      height: 360px; /* increased from 250px */
      max-height: 60vh; /* keep it responsive */
      width: 100%;
      object-fit: contain;
      border-radius: 18px;
      box-shadow: 0 2px 16px rgba(44,62,80,0.13);
      background: #232837;
    }
    .section-title {
      margin-top: 40px;
      margin-bottom: 30px;
      font-weight: 700;
      letter-spacing: 1px;
      color: #6ea8fe;
      text-shadow: 0 2px 8px #0d223a44;
    }
    .badge.bg-primary, .badge.bg-success, .badge.bg-secondary {
      opacity: 0.92;
      font-size: 0.95em;
      margin-right: 3px;
    }
    .skill-card {
      border: none;
      border-radius: 16px;
      background: #232837;
      box-shadow: 0 2px 12px rgba(44,62,80,0.10);
      margin-bottom: 22px;
      transition: box-shadow 0.2s, transform 0.18s, background 0.2s;
      cursor: pointer;
    }
    .skill-card:hover {
      background: #27304a;
      box-shadow: 0 6px 32px rgba(110,168,254,0.13);
      transform: translateY(-2px) scale(1.015);
      border: 1.5px solid #6ea8fe33;
    }
    .skill-icon {
      height: 54px;
      width: 54px;
      object-fit: contain;
      border-radius: 10px;
      background: #232837;
      margin-right: 16px;
      border: 2px solid #232837;
      transition: border 0.2s;
    }
    .skill-card:hover .skill-icon {
      border: 2px solid #6ea8fe;
    }
    .card-title {
      margin: 0;
      color: #fff;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
    .counter-card {
      border: none;
      border-radius: 18px;
      box-shadow: 0 2px 16px rgba(44,62,80,0.13);
      margin-bottom: 32px;
      background: #232837;
      transition: box-shadow 0.2s;
    }
    .counter-card:hover {
      box-shadow: 0 4px 24px rgba(110,168,254,0.18);
    }
    .counter-header {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 10px;
      color: #6ea8fe;
    }
    .counter-desc {
      color: #bfc9d1;
      margin-bottom: 16px;
    }
    .hero-icon {
      height: 60px;
      width: 60px;
      object-fit: cover;
      border-radius: 50%;
      border: 2px solid #232837;
      margin-right: 12px;
      margin-bottom: 8px;
      background: #181c24;
      transition: transform 0.15s, border 0.2s;
    }
    .hero-icon:hover {
      transform: scale(1.08);
      border-color: #6ea8fe;
    }
    .text-muted {
      color: #bfc9d1 !important;
    }
    .shadow-sm {
      box-shadow: 0 2px 12px rgba(44,62,80,0.10)!important;
    }
    .search-form {
      max-width: 340px;
      margin: 0 auto 32px auto;
      background: #232837;
      border-radius: 14px;
      padding: 18px 20px 12px 20px;
      box-shadow: 0 2px 12px rgba(44,62,80,0.10);
    }
    .search-form label {
      color: #bfc9d1;
      font-weight: 500;
      margin-bottom: 6px;
    }
    .search-form select {
      background: #181c24;
      color: #e9ecef;
      border: 1px solid #343a40;
      border-radius: 6px;
    }
    .search-form button {
      background: #6ea8fe;
      border: none;
      color: #fff;
      font-weight: 600;
      border-radius: 6px;
      transition: background 0.18s;
    }
    .search-form button:hover {
      background: #468be6;
    }
    @media (max-width: 576px) {
      .hero-img { height: 200px; }
      .skill-icon { height: 36px; width: 36px; }
      .hero-icon { height: 44px; width: 44px; }
      .section-title { font-size: 1.3rem; }
      .search-form { padding: 12px 8px 8px 8px; }
    }
    /*
    .container-fluid {
      padding-left: 0;
      padding-right: 0;
    }
    */
    @media (min-width: 768px) {
      .container-fluid > .row > .col-12 {
        padding-left: 24px;
        padding-right: 24px;
      }
    }
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
  </style>
</head>
<body>
  
<!-- Loading Overlay -->
<div id="loading-overlay">
  <div class="spinner-border" role="status"></div>
  <div class="loading-text">Loading hero data...</div>
</div>

<?php include 'navbar/navbar.php'; ?>

<div class="container-fluid py-4" id="main-content" style="opacity:0;">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-10 col-xl-9">

      <!-- Dropdown for Hero Selection -->
      <form class="search-form mb-4" method="get" action="">
        <div class="mb-2">
          <label for="id" class="form-label"><i class="bi bi-search"></i> Select Hero:</label>
          <select class="form-select" id="id" name="id" required>
            <option value="">-- Choose Hero --</option>
            <?php foreach (array_reverse($hero_list) as $heroData): ?>
              <option value="<?= htmlspecialchars($heroData['heroid']) ?>" <?= $hero_id == $heroData['heroid'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($heroData['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary w-100 mt-2"><i class="bi bi-arrow-right-circle"></i> View</button>
      </form>

      <!-- Hero Info Row -->
      <div class="row align-items-center mb-4 g-3">
        <div class="col-12 col-md-4 text-center">
          <img src="<?= htmlspecialchars($painting) ?>" class="img-fluid hero-img mb-3" alt="Hero Image">
        </div>
        <div class="col-12 col-md-8">
          <h1 class="mb-1"><?= htmlspecialchars($hero['name']) ?></h1>
          <div class="mb-2">
            <?php foreach ($roles as $role): ?>
              <span class="badge bg-primary"><?= htmlspecialchars($role) ?></span>
            <?php endforeach; ?>
            <?php foreach ($specialities as $spec): ?>
              <span class="badge bg-success"><?= htmlspecialchars($spec) ?></span>
            <?php endforeach; ?>
          </div>
          <p class="text-muted"><?= htmlspecialchars($hero['story']) ?></p>
        </div>
      </div>

      <!-- Hero Detail Stats Section -->
      <?php if ($detail_stats): ?>
        <h3 class="section-title"><i class="bi bi-bar-chart-fill"></i> Hero Detailed Stats</h3>
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-4">
            <div class="card counter-card h-100">
              <div class="card-body">
                <div class="counter-header">
                  <i class="bi bi-person-fill"></i>
                  <?= htmlspecialchars($detail_stats['main_hero']['data']['name'] ?? $hero['name']) ?>
                </div>
                <ul class="list-unstyled mb-0">
                  <li><strong>Win Rate:</strong> <?= isset($detail_stats['main_hero_win_rate']) ? round($detail_stats['main_hero_win_rate'] * 100, 2) . '%' : 'N/A' ?></li>
                  <li><strong>Ban Rate:</strong> <?= isset($detail_stats['main_hero_ban_rate']) ? round($detail_stats['main_hero_ban_rate'] * 100, 2) . '%' : 'N/A' ?></li>
                  <li><strong>Appearance Rate:</strong> <?= isset($detail_stats['main_hero_appearance_rate']) ? round($detail_stats['main_hero_appearance_rate'] * 100, 2) . '%' : 'N/A' ?></li>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-8">
            <div class="card counter-card h-100">
              <div class="card-body">
                <div class="counter-header">
                  <i class="bi bi-people"></i>
                  Frequent Allies & Impact
                </div>
                <div class="row">
                  <?php foreach (array_slice($detail_stats['sub_hero'] ?? [], 0, 5) as $ally): ?>
                    <?php
                      $img = 'https://via.placeholder.com/40x40?text=No+Image';
                      if (!empty($ally['hero']['data']['head'])) {
                          $img = $ally['hero']['data']['head'];
                      }
                    ?>
                    <div class="col-12 col-sm-6 mb-2">
                      <div class="d-flex align-items-center">
                        <img src="<?= htmlspecialchars($img) ?>" style="height:40px;width:40px;border-radius:50%;margin-right:10px;">
                        <div>
                          <strong>WR:</strong> <?= round($ally['hero_win_rate'] * 100, 2) ?>%
                          <span class="text-<?= $ally['increase_win_rate'] >= 0 ? 'success' : 'danger' ?> ms-2">
                            <?= $ally['increase_win_rate'] >= 0 ? '+' : '' ?><?= round($ally['increase_win_rate'] * 100, 2) ?>%
                          </span>
                          <br>
                          <small>Appear: <?= round($ally['hero_appearance_rate'] * 100, 2) ?>%</small>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Negative Impact Allies -->
        <?php if (!empty($detail_stats['sub_hero_last'])): ?>
        <div class="row g-3 mb-4">
          <div class="col-12">
            <div class="card counter-card">
              <div class="card-body">
                <div class="counter-header">
                  <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                  Negative Impact Allies
                </div>
                <div class="row">
                  <?php foreach (array_slice($detail_stats['sub_hero_last'], 0, 5) as $ally): ?>
                    <?php
                      $img = 'https://via.placeholder.com/40x40?text=No+Image';
                      $heroid = $ally['heroid'] ?? null;
                      // Try to get from hero_head_map
                      if ($heroid && !empty($hero_head_map[$heroid])) {
                          $img = $hero_head_map[$heroid];
                      } else if ($heroid) {
                          // Fallback: try to read from local cache
                          $herodetail_path = $cache_dir . "$heroid.json";
                          if (file_exists($herodetail_path)) {
                              $herodetail_json = file_get_contents($herodetail_path);
                              $herodetail_data = $herodetail_json ? json_decode($herodetail_json, true) : null;
                              if (!empty($herodetail_data['data']['records'][0]['data']['hero']['data']['head'])) {
                                  $img = $herodetail_data['data']['records'][0]['data']['hero']['data']['head'];
                              }
                          }
                      }
                    ?>
                    <div class="col-12 col-sm-6 mb-2">
                      <div class="d-flex align-items-center">
                        <img src="<?= htmlspecialchars($img) ?>" style="height:40px;width:40px;border-radius:50%;margin-right:10px;">
                        <div>
                          <strong>WR:</strong> <?= round($ally['hero_win_rate'] * 100, 2) ?>%
                          <span class="text-danger ms-2">
                            <?= round($ally['increase_win_rate'] * 100, 2) ?>%
                          </span>
                          <br>
                          <small>Appear: <?= round($ally['hero_appearance_rate'] * 100, 2) ?>%</small>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
      <?php endif; ?>

      <!-- Hero Counter Section -->
      <?php if ($counter_stats): ?>
        <h3 class="section-title"><i class="bi bi-shield-shaded"></i> Hero Counters</h3>
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-6">
            <div class="card counter-card h-100">
              <div class="card-body">
                <div class="counter-header">
                  <i class="bi bi-arrow-up-circle-fill text-success"></i>
                  Strong Against (Counters)
                </div>
                <div class="row">
                  <?php foreach (array_slice($counter_stats['sub_hero'] ?? [], 0, 5) as $counter): ?>
                    <?php
                      $img = 'https://via.placeholder.com/40x40?text=No+Image';
                      $heroid = $counter['heroid'] ?? null;
                      if ($heroid && !empty($hero_head_map[$heroid])) {
                          $img = $hero_head_map[$heroid];
                      } else if ($heroid) {
                          $herodetail_path = $cache_dir . "$heroid.json";
                          if (file_exists($herodetail_path)) {
                              $herodetail_json = file_get_contents($herodetail_path);
                              $herodetail_data = $herodetail_json ? json_decode($herodetail_json, true) : null;
                              if (!empty($herodetail_data['data']['records'][0]['data']['hero']['data']['head'])) {
                                  $img = $herodetail_data['data']['records'][0]['data']['hero']['data']['head'];
                              }
                          }
                      }
                    ?>
                    <div class="col-12 col-sm-6 mb-2">
                      <div class="d-flex align-items-center">
                        <img src="<?= htmlspecialchars($img) ?>" style="height:40px;width:40px;border-radius:50%;margin-right:10px;">
                        <div>
                          <strong>WR:</strong> <?= round($counter['hero_win_rate'] * 100, 2) ?>%
                          <span class="text-success ms-2">
                            <?= $counter['increase_win_rate'] >= 0 ? '+' : '' ?><?= round($counter['increase_win_rate'] * 100, 2) ?>%
                          </span>
                          <br>
                          <small>Appear: <?= round($counter['hero_appearance_rate'] * 100, 2) ?>%</small>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="card counter-card h-100">
              <div class="card-body">
                <div class="counter-header">
                  <i class="bi bi-arrow-down-circle-fill text-danger"></i>
                  Weak Against (Countered By)
                </div>
                <div class="row">
                  <?php foreach (array_slice($counter_stats['sub_hero_last'] ?? [], 0, 5) as $counter): ?>
                    <?php
                      $img = 'https://via.placeholder.com/40x40?text=No+Image';
                      $heroid = $counter['heroid'] ?? null;
                      if ($heroid && !empty($hero_head_map[$heroid])) {
                          $img = $hero_head_map[$heroid];
                      } else if ($heroid) {
                          $herodetail_path = $cache_dir . "$heroid.json";
                          if (file_exists($herodetail_path)) {
                              $herodetail_json = file_get_contents($herodetail_path);
                              $herodetail_data = $herodetail_json ? json_decode($herodetail_json, true) : null;
                              if (!empty($herodetail_data['data']['records'][0]['data']['hero']['data']['head'])) {
                                  $img = $herodetail_data['data']['records'][0]['data']['hero']['data']['head'];
                              }
                          }
                      }
                    ?>
                    <div class="col-12 col-sm-6 mb-2">
                      <div class="d-flex align-items-center">
                        <img src="<?= htmlspecialchars($img) ?>" style="height:40px;width:40px;border-radius:50%;margin-right:10px;">
                        <div>
                          <strong>WR:</strong> <?= round($counter['hero_win_rate'] * 100, 2) ?>%
                          <span class="text-danger ms-2">
                            <?= round($counter['increase_win_rate'] * 100, 2) ?>%
                          </span>
                          <br>
                          <small>Appear: <?= round($counter['hero_appearance_rate'] * 100, 2) ?>%</small>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Hero Compatibility Section -->
      <?php if ($compat_stats): ?>
        <h3 class="section-title"><i class="bi bi-people-fill"></i> Hero Compatibility</h3>
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-6">
            <div class="card counter-card h-100">
              <div class="card-body">
                <div class="counter-header">
                  <i class="bi bi-hand-thumbs-up-fill text-success"></i>
                  Best Synergy (Good Partners)
                </div>
                <div class="row">
                  <?php foreach (array_slice($compat_stats['sub_hero'] ?? [], 0, 5) as $partner): ?>
                    <?php
                      $img = 'https://via.placeholder.com/40x40?text=No+Image';
                      $heroid = $partner['heroid'] ?? null;
                      if ($heroid && !empty($hero_head_map[$heroid])) {
                          $img = $hero_head_map[$heroid];
                      } else if ($heroid) {
                          $herodetail_path = $cache_dir . "$heroid.json";
                          if (file_exists($herodetail_path)) {
                              $herodetail_json = file_get_contents($herodetail_path);
                              $herodetail_data = $herodetail_json ? json_decode($herodetail_json, true) : null;
                              if (!empty($herodetail_data['data']['records'][0]['data']['hero']['data']['head'])) {
                                  $img = $herodetail_data['data']['records'][0]['data']['hero']['data']['head'];
                              }
                          }
                      }
                    ?>
                    <div class="col-12 col-sm-6 mb-2">
                      <div class="d-flex align-items-center">
                        <img src="<?= htmlspecialchars($img) ?>" style="height:40px;width:40px;border-radius:50%;margin-right:10px;">
                        <div>
                          <strong>WR:</strong> <?= round($partner['hero_win_rate'] * 100, 2) ?>%
                          <span class="text-success ms-2">
                            <?= $partner['increase_win_rate'] >= 0 ? '+' : '' ?><?= round($partner['increase_win_rate'] * 100, 2) ?>%
                          </span>
                          <br>
                          <small>Appear: <?= round($partner['hero_appearance_rate'] * 100, 2) ?>%</small>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="card counter-card h-100">
              <div class="card-body">
                <div class="counter-header">
                  <i class="bi bi-hand-thumbs-down-fill text-danger"></i>
                  Poor Synergy (Bad Partners)
                </div>
                <div class="row">
                  <?php foreach (array_slice($compat_stats['sub_hero_last'] ?? [], 0, 5) as $partner): ?>
                    <?php
                      $img = 'https://via.placeholder.com/40x40?text=No+Image';
                      $heroid = $partner['heroid'] ?? null;
                      if ($heroid && !empty($hero_head_map[$heroid])) {
                          $img = $hero_head_map[$heroid];
                      } else if ($heroid) {
                          $herodetail_path = $cache_dir . "$heroid.json";
                          if (file_exists($herodetail_path)) {
                              $herodetail_json = file_get_contents($herodetail_path);
                              $herodetail_data = $herodetail_json ? json_decode($herodetail_json, true) : null;
                              if (!empty($herodetail_data['data']['records'][0]['data']['hero']['data']['head'])) {
                                  $img = $herodetail_data['data']['records'][0]['data']['hero']['data']['head'];
                              }
                          }
                      }
                    ?>
                    <div class="col-12 col-sm-6 mb-2">
                      <div class="d-flex align-items-center">
                        <img src="<?= htmlspecialchars($img) ?>" style="height:40px;width:40px;border-radius:50%;margin-right:10px;">
                        <div>
                          <strong>WR:</strong> <?= round($partner['hero_win_rate'] * 100, 2) ?>%
                          <span class="text-danger ms-2">
                            <?= round($partner['increase_win_rate'] * 100, 2) ?>%
                          </span>
                          <br>
                          <small>Appear: <?= round($partner['hero_appearance_rate'] * 100, 2) ?>%</small>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>