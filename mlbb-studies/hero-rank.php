<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MLBB Hero Rankings - Search & Filter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #181c24;
            color: #e9ecef;
        }
        .hero-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 2px solid #343a40;
            background: #232837;
        }
        .sub-hero-img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin: 2px;
            border: 2px solid #343a40;
            background: #232837;
        }
        .card {
            background: #232837;
            color: #e9ecef;
            border: none;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.10);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 32px rgba(110,168,254,0.13);
            border: 1.5px solid #6ea8fe33;
        }
        .list-group-item {
            background: #232837;
            color: #bfc9d1;
            border: none;
            border-bottom: 1px solid #232b3e;
        }
        .list-group-item:last-child {
            border-bottom: none;
        }
        .card-title {
            color: #fff;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .text-primary, .text-light-blue {
            color: #6ea8fe !important;
        }
        .synergy-label {
            color: #bfc9d1;
            font-weight: 500;
        }
        .form-label {
            color: #bfc9d1;
            font-weight: 500;
        }
        .bg-dark-blue {
            background: #232837 !important;
        }
        @media (max-width: 767px) {
            .hero-img { width: 60px; height: 60px; }
            .sub-hero-img { width: 28px; height: 28px; }
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
    <h2 class="text-light-blue mb-4 text-center">MLBB Hero Rankings - Search & Filter</h2>
    <?php
    // Default/filter values
    $days = isset($_GET['days']) ? intval($_GET['days']) : 1;
    $rank = isset($_GET['rank']) ? $_GET['rank'] : 'all';
    $size = 15; // fixed page size
    $index = isset($_GET['index']) ? max(1, min(128, intval($_GET['index']))) : 1;
    $sort_field = isset($_GET['sort_field']) ? $_GET['sort_field'] : 'win_rate';
    $sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'desc';

    // Options
    $days_options = [1, 3, 7, 15, 30];
    $rank_options = [
        'all' => 'All',
        'epic' => 'Epic',
        'legend' => 'Legend',
        'mythic' => 'Mythic',
        'honor' => 'Honor',
        'glory' => 'Glory'
    ];
    $sort_field_options = [
        'pick_rate' => 'Pick Rate',
        'ban_rate' => 'Ban Rate',
        'win_rate' => 'Win Rate'
    ];
    $sort_order_options = [
        'asc' => 'Ascending',
        'desc' => 'Descending'
    ];

    // API request URL
    $api_url = "https://mlbb-stats.ridwaanhall.com/api/hero-rank/?days={$days}&rank={$rank}&size={$size}&index={$index}&sort_field={$sort_field}&sort_order={$sort_order}";
    ?>
    <form class="row g-3 mb-4 bg-dark-blue rounded-3 p-3" method="get" action="">
        <div class="col-6 col-md-3">
            <label for="days" class="form-label">Days</label>
            <select name="days" id="days" class="form-select">
                <?php foreach ($days_options as $val): ?>
                    <option value="<?php echo $val; ?>" <?php if ($days == $val) echo 'selected'; ?>><?php echo $val; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label for="rank" class="form-label">Rank</label>
            <select name="rank" id="rank" class="form-select">
                <?php foreach ($rank_options as $val => $label): ?>
                    <option value="<?php echo $val; ?>" <?php if ($rank == $val) echo 'selected'; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label for="sort_field" class="form-label">Sort By</label>
            <select name="sort_field" id="sort_field" class="form-select">
                <?php foreach ($sort_field_options as $val => $label): ?>
                    <option value="<?php echo $val; ?>" <?php if ($sort_field == $val) echo 'selected'; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label for="sort_order" class="form-label">Order</label>
            <select name="sort_order" id="sort_order" class="form-select">
                <?php foreach ($sort_order_options as $val => $label): ?>
                    <option value="<?php echo $val; ?>" <?php if ($sort_order == $val) echo 'selected'; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Search</button>
        </div>
    </form>

    <?php
    try {
        $response = @file_get_contents($api_url);
        if ($response === FALSE) {
            throw new Exception("Failed to fetch data from API.");
        }
        $data = json_decode($response, true);

        if (isset($data['data']['records']) && is_array($data['data']['records']) && count($data['data']['records']) > 0) {
            echo "<div class='row row-cols-1 row-cols-md-3 g-4'>";
            foreach ($data['data']['records'] as $record) {
                $hero = $record['data']['main_hero']['data'];
                $image = $hero['head'];
                $name = $hero['name'];
                $winRate = number_format($record['data']['main_hero_win_rate'] * 100, 2);
                $pickRate = number_format($record['data']['main_hero_appearance_rate'] * 100, 2);
                $banRate = number_format($record['data']['main_hero_ban_rate'] * 100, 2);

                echo "
                <div class='col mb-4'>
                    <div class='card shadow-sm p-3 h-100'>
                        <div class='text-center'>
                            <img src='$image' alt='$name' class='hero-img'>
                            <h5 class='mt-2 card-title'>$name</h5>
                        </div>
                        <ul class='list-group list-group-flush mb-2'>
                            <li class='list-group-item'>Win Rate: <strong class='text-light-blue'>$winRate%</strong></li>
                            <li class='list-group-item'>Pick Rate: <strong class='text-light-blue'>$pickRate%</strong></li>
                            <li class='list-group-item'>Ban Rate: <strong class='text-light-blue'>$banRate%</strong></li>
                        </ul>
                        <div class='mt-3'>
                            <span class='synergy-label'>Top Synergy Heroes:</span><br>";

                if (isset($record['data']['sub_hero'])) {
                    foreach ($record['data']['sub_hero'] as $subHero) {
                        $subImage = $subHero['hero']['data']['head'];
                        $subName = $subHero['hero']['data']['name'] ?? 'Sub Hero';
                        $increaseWin = number_format($subHero['increase_win_rate'] * 100, 2);
                        echo "<img src='$subImage' title='+$increaseWin% Win Rate' class='sub-hero-img' alt='$subName'>";
                    }
                } else {
                    echo "<small class='text-muted'>No synergy data available.</small>";
                }

                echo "
                        </div>
                    </div>
                </div>
                ";
            }
            echo "</div>";

            // PAGINATION BAR
            if (isset($data['data']['total'])) {
                $total = intval($data['data']['total']);
                $page_size = intval($size);
                $current_page = intval($index);
                $total_pages = max(1, ceil($total / $page_size));

                if ($total_pages > 1) {
                    // Build base URL for pagination links (preserving filters except size/index)
                    $base_url = strtok($_SERVER["REQUEST_URI"], '?');
                    $query = $_GET;
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
        } else {
            echo "<p class='text-danger'>No hero ranking data found.</p>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>