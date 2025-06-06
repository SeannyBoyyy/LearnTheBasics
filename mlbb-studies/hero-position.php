<?php
// Handle filter form input
$role = isset($_GET['role']) ? $_GET['role'] : 'all';
$lane = isset($_GET['lane']) ? $_GET['lane'] : 'all';
// Pagination: allow page index from query, default to 1
$size = 13;
$index = isset($_GET['index']) ? max(1, min(128, intval($_GET['index']))) : 1;

// API URL with filters
$api_url = "https://mlbb-stats.ridwaanhall.com/api/hero-position/?role={$role}&lane={$lane}&size={$size}&index={$index}";

// Fetch hero list for mapping IDs to names
$hero_list_url = "https://mlbb-stats.ridwaanhall.com/api/hero-list/";
$hero_list_json = @file_get_contents($hero_list_url);
$hero_list = $hero_list_json ? json_decode($hero_list_json, true) : [];

// Fetch data using cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
    exit;
}

$data = json_decode($response, true);
curl_close($ch);

// Role and lane options
$role_options = [
    'all' => 'All',
    'tank' => 'Tank',
    'fighter' => 'Fighter',
    'ass' => 'Assassin',
    'mage' => 'Mage',
    'mm' => 'Marksman',
    'supp' => 'Support'
];
$lane_options = [
    'all' => 'All',
    'exp' => 'Exp Lane',
    'mid' => 'Mid Lane',
    'roam' => 'Roam',
    'jungle' => 'Jungle',
    'gold' => 'Gold Lane'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Hero Position Filter</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background: #181c24;
        color: #e9ecef;
    }
    .table thead th {
        background: #25304a;
        color: #fff;
        font-weight: 700;
        font-size: 1.05em;
        border-bottom: 2px solid #232b3e;
    }
    .table-striped>tbody>tr:nth-of-type(odd)>* {
        background-color: #232837;
    }
    .table-striped>tbody>tr:nth-of-type(even)>* {
        background-color: #1b2230;
    }
    .table-hover tbody tr:hover > * {
        background-color: #27304a;
    }
    .hero-img {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 10px;
        vertical-align: middle;
        border: 2px solid #343a40;
        background: #181c24;
    }
    .role-badge, .lane-badge {
        display: inline-block;
        margin-right: 6px;
        margin-bottom: 2px;
        background: #343a40;
        color: #fff;
        border-radius: 8px;
        padding: 2px 8px 2px 4px;
        font-size: 0.93em;
    }
    .role-badge img, .lane-badge img {
        height: 16px;
        width: 16px;
        margin-right: 4px;
        border-radius: 4px;
        background: #232837;
        vertical-align: middle;
    }
    .form-label {
        color: #bfc9d1;
        font-weight: 500;
    }
    .bg-dark-blue {
        background: #232837 !important;
    }
    .text-light-blue {
        color: #6ea8fe !important;
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

<div class="container py-4" id="main-content" style="opacity:0;">
    <h1 class="text-center mb-4 text-light-blue">Hero Positions</h1>
    <form class="row g-3 mb-4 bg-dark-blue rounded-3 p-3" method="get" action="">
        <div class="col-6 col-md-4">
            <label for="role" class="form-label">Role</label>
            <select name="role" id="role" class="form-select">
                <?php foreach ($role_options as $val => $label): ?>
                    <option value="<?php echo $val; ?>" <?php if ($role == $val) echo 'selected'; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-4">
            <label for="lane" class="form-label">Lane</label>
            <select name="lane" id="lane" class="form-select">
                <?php foreach ($lane_options as $val => $label): ?>
                    <option value="<?php echo $val; ?>" <?php if ($lane == $val) echo 'selected'; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>Hero</th>
                <th>Lane</th>
                <th>Role</th>
                <th>Assist</th>
                <th>Strong Against</th>
                <th>Weak Against</th>
            </tr>
        </thead>
        <tbody>
    <?php
    if (isset($data['code']) && $data['code'] == 0 && !empty($data['data']['records']) && is_array($data['data']['records'])) {
        $records = $data['data']['records'];
        foreach ($records as $record) {
            $heroData = $record['data']['hero']['data'] ?? null;
            if (!$heroData) continue;

            $heroName = $heroData['name'] ?? 'Unknown Hero';
            $heroImage = $heroData['smallmap'] ?? 'https://via.placeholder.com/48x48?text=?';

            // Lanes
            $lanes = array_filter(array_map(function($road) {
                if (isset($road['data']['road_sort_title'])) {
                    return [
                        'title' => $road['data']['road_sort_title'],
                        'icon' => $road['data']['road_sort_icon'] ?? ''
                    ];
                }
                return null;
            }, $heroData['roadsort'] ?? []));

            // Roles
            $roles = array_filter(array_map(function($sort) {
                if (isset($sort['data']['sort_title'])) {
                    return [
                        'title' => $sort['data']['sort_title'],
                        'icon' => $sort['data']['sort_icon'] ?? ''
                    ];
                }
                return null;
            }, $heroData['sortid'] ?? []));

            // Relations
            $relation = $record['data']['relation'] ?? [];
            ?>
            <tr>
                <!-- HERO -->
                <td>
                    <img src="<?php echo htmlspecialchars($heroImage); ?>" class="hero-img" alt="">
                    <span class="text-white"><?php echo htmlspecialchars($heroName); ?></span>
                </td>
                <!-- LANE -->
                <td>
                    <?php foreach ($lanes as $lane): ?>
                        <span class="lane-badge">
                            <?php if (!empty($lane['icon'])): ?>
                                <img src="<?php echo htmlspecialchars($lane['icon']); ?>" alt="">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($lane['title']); ?>
                        </span>
                    <?php endforeach; ?>
                </td>
                <!-- ROLE -->
                <td>
                    <?php foreach ($roles as $role): ?>
                        <span class="role-badge">
                            <?php if (!empty($role['icon'])): ?>
                                <img src="<?php echo htmlspecialchars($role['icon']); ?>" alt="">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($role['title']); ?>
                        </span>
                    <?php endforeach; ?>
                </td>
                <!-- ASSIST -->
                <td>
                    <ul class="mb-0 text-white">
                    <?php
                    if (!empty($relation['assist']['target_hero_id'])) {
                        foreach ($relation['assist']['target_hero_id'] as $aid) {
                            if ($aid && isset($hero_list[$aid])) {
                                echo '<li>' . htmlspecialchars($hero_list[$aid]) . '</li>';
                            } elseif ($aid) {
                                echo '<li>Unknown</li>';
                            }
                        }
                    } else {
                        echo '<li>Unknown</li>';
                    }
                    ?>
                    </ul>
                </td>
                <!-- STRONG AGAINST -->
                <td>
                    <ul class="mb-0 text-white">
                    <?php
                    if (!empty($relation['strong']['target_hero_id'])) {
                        foreach ($relation['strong']['target_hero_id'] as $sid) {
                            if ($sid && isset($hero_list[$sid])) {
                                echo '<li>' . htmlspecialchars($hero_list[$sid]) . '</li>';
                            } elseif ($sid) {
                                echo '<li>Unknown</li>';
                            }
                        }
                    } else {
                        echo '<li>Unknown</li>';
                    }
                    ?>
                    </ul>
                </td>
                <!-- WEAK AGAINST -->
                <td>
                    <ul class="mb-0 text-white">
                    <?php
                    if (!empty($relation['weak']['target_hero_id'])) {
                        foreach ($relation['weak']['target_hero_id'] as $wid) {
                            if ($wid && isset($hero_list[$wid])) {
                                echo '<li>' . htmlspecialchars($hero_list[$wid]) . '</li>';
                            } elseif ($wid) {
                                echo '<li>Unknown</li>';
                            }
                        }
                    } else {
                        echo '<li>Unknown</li>';
                    }
                    ?>
                    </ul>
                </td>
            </tr>
            <?php
        }
    } else {
        echo '<tr><td colspan="6" class="text-center text-white">No hero found.</td></tr>';
    }
    ?>
        </tbody>
    </table>
    </div>

<?php
// PAGINATION BAR
if (isset($data['code']) && $data['code'] == 0 && isset($data['data']['total'])) {
    $total = intval($data['data']['total']);
    $page_size = intval($size);
    $current_page = intval($index);
    $total_pages = max(1, ceil($total / $page_size));

    if ($total_pages > 1) {
        // Build base URL for pagination links (preserving filters)
        $base_url = strtok($_SERVER["REQUEST_URI"], '?');
        $query = $_GET;
        // Always set size to 13 for pagination links
        $query['size'] = $page_size;

        echo '<nav aria-label="Page navigation" class="mt-3">';
        echo '<ul class="pagination justify-content-center">';

        // Previous button
        $query['index'] = max(1, $current_page - 1);
        $prev_disabled = $current_page <= 1 ? ' disabled' : '';
        echo '<li class="page-item' . $prev_disabled . '">';
        echo '<a class="page-link" href="' . htmlspecialchars($base_url . '?' . http_build_query($query)) . '" tabindex="-1">Previous</a>';
        echo '</li>';

        // Page numbers (show up to 7 pages, with ... if needed)
        $start = max(1, $current_page - 3);
        $end = min($total_pages, $current_page + 3);
        if ($start > 1) {
            $query['index'] = 1;
            echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($base_url . '?' . http_build_query($query)) . '">1</a></li>';
            if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        for ($i = $start; $i <= $end; $i++) {
            $query['index'] = $i;
            $active = $i == $current_page ? ' active' : '';
            echo '<li class="page-item' . $active . '"><a class="page-link" href="' . htmlspecialchars($base_url . '?' . http_build_query($query)) . '">' . $i . '</a></li>';
        }
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            $query['index'] = $total_pages;
            echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($base_url . '?' . http_build_query($query)) . '">' . $total_pages . '</a></li>';
        }

        // Next button
        $query['index'] = min($total_pages, $current_page + 1);
        $next_disabled = $current_page >= $total_pages ? ' disabled' : '';
        echo '<li class="page-item' . $next_disabled . '">';
        echo '<a class="page-link" href="' . htmlspecialchars($base_url . '?' . http_build_query($query)) . '">Next</a>';
        echo '</li>';

        echo '</ul>';
        echo '</nav>';
    }
}
?>

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