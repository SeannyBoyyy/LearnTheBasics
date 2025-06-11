<?php
// Get hero_id from query string, default to 128 (Lukas) if not set or invalid
$hero_id = isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id'] >= 1 && $_GET['id'] <= 128
    ? intval($_GET['id'])
    : 128;

// Load hero list from local cache (downloaded file)
$hero_list_path = __DIR__ . '/hero_cache/hero-list.json';
if (!file_exists($hero_list_path)) {
    die("Local hero list not found. Please download it using your script.");
}
$hero_list_json = file_get_contents($hero_list_path);
$hero_list = $hero_list_json ? json_decode($hero_list_json, true) : [];

// Build a mapping of heroid => head image for quick lookup
$hero_head_map = [];
foreach ($hero_list as $hero) {
    if (is_array($hero) && isset($hero['heroid']) && isset($hero['head'])) {
        $hero_head_map[$hero['heroid']] = $hero['head'];
    }
}

// Fetch hero detail from local cache
$detail_path = __DIR__ . "/hero_cache/$hero_id.json";
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

// Fetch hero skill combos from local cache
$combo_path = __DIR__ . "/hero_cache/skill-combo-$hero_id.json";
if (file_exists($combo_path)) {
    $combo_json = file_get_contents($combo_path);
    $combo_data = $combo_json ? json_decode($combo_json, true) : [];
    $skill_combos = isset($combo_data['data']['records']) ? $combo_data['data']['records'] : [];
} else {
    // fallback: no combos
    $skill_combos = [];
}

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
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($hero['name']) ?> - Hero Details</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: #181c24;
      color: #e9ecef;
      font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
    }
    .hero-img {
      height: 250px;
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
    .counter-card,
    .recommend-card {
      border: none;
      border-radius: 18px;
      box-shadow: 0 2px 16px rgba(44,62,80,0.13);
      background: #232837;
      transition: box-shadow 0.2s, transform 0.18s, background 0.2s, border 0.2s;
      cursor: pointer;
    }
    .counter-card:hover,
    .recommend-card:hover {
      background: #27304a;
      box-shadow: 0 6px 32px rgba(110,168,254,0.13);
      transform: translateY(-2px) scale(1.015);
      border: 1.5px solid #6ea8fe33;
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
      .hero-img { height: 140px; }
      .skill-icon { height: 36px; width: 36px; }
      .hero-icon { height: 44px; width: 44px; }
      .section-title { font-size: 1.3rem; }
      .search-form { padding: 12px 8px 8px 8px; }
    }
    .container-fluid {
      padding-left: 0;
      padding-right: 0;
    }
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

      <!-- Skills Section -->
      <h3 class="section-title"><i class="bi bi-lightning-charge-fill"></i> Skills</h3>
      <div class="row g-3">
        <?php foreach ($skills as $skill): ?>
          <div class="col-12 col-md-6">
            <div class="card skill-card h-100">
              <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                  <img src="<?= htmlspecialchars($skill['skillicon']) ?>" class="skill-icon" alt="">
                  <h5 class="card-title mb-0"><?= htmlspecialchars($skill['skillname']) ?></h5>
                </div>
                <?php if (!empty($skill['skillcd&cost'])): ?>
                  <p class="mb-1"><small class="text-info"><?= htmlspecialchars($skill['skillcd&cost']) ?></small></p>
                <?php endif; ?>
                <p><?= convertFontColorToSpan($skill['skilldesc']) ?></p>
                <?php if (!empty($skill['skilltag'])): ?>
                  <div>
                    <?php foreach ($skill['skilltag'] as $tag): ?>
                      <span class="badge bg-secondary"><?= htmlspecialchars($tag['tagname']) ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Skill Combos Section -->
      <?php if (!empty($skill_combos)): ?>
        <h3 class="section-title"><i class="bi bi-joystick"></i> Skill Combos</h3>
        <div class="row g-4">
          <?php foreach ($skill_combos as $combo): ?>
            <?php $comboData = $combo['data']; ?>
            <div class="col-12 col-md-6">
              <div class="card skill-card h-100">
                <div class="card-body">
                  <div class="mb-2 fw-bold text-info" style="font-size:1.1em;">
                    <?= htmlspecialchars($comboData['title'] ?? 'Combo') ?>
                  </div>
                  <div class="mb-2 d-flex align-items-center flex-wrap gap-2">
                    <?php foreach ($comboData['skill_id'] as $step): ?>
                      <?php if (!empty($step['data']['skillicon'])): ?>
                        <img src="<?= htmlspecialchars($step['data']['skillicon']) ?>" alt="Skill" style="height:38px;width:38px;border-radius:8px;background:#232837;border:2px solid #232837;">
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                  <div class="mb-1 text-muted" style="font-size:0.98em;">
                    <?= htmlspecialchars($comboData['desc']) ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Recommend Master Plan Section -->
      <?php if (!empty($hero['recommendmasterplan'])): ?>
        <h3 class="section-title"><i class="bi bi-star-fill"></i> Recommend Master Plan</h3>
        <div class="row g-4">
          <?php foreach ($hero['recommendmasterplan'] as $plan): ?>
            <div class="col-12 col-md-6">
              <div class="card recommend-card shadow-sm h-100">
                <div class="card-body">
                  <div class="d-flex align-items-center mb-3">
                    <?php if (!empty($plan['face'])): ?>
                      <img src="<?= htmlspecialchars($plan['face']) ?>" alt="Pro Player" style="height:48px;width:48px;border-radius:50%;margin-right:14px;box-shadow:0 2px 8px #0002;">
                    <?php endif; ?>
                    <div>
                      <div class="fw-bold" style="font-size:1.1em;"><?= htmlspecialchars($plan['name'] ?? 'Pro Player') ?></div>
                      <?php if (!empty($plan['title'])): ?>
                        <span class="badge bg-secondary"><?= htmlspecialchars($plan['title']) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="mb-2">
                    <span class="fw-semibold"> Battle Skill:</span>
                    <?php if (!empty($plan['battleskill']['__data']['skillicon'])): ?>
                      <img src="<?= htmlspecialchars($plan['battleskill']['__data']['skillicon']) ?>" alt="Battle Skill" style="height:32px;width:32px;vertical-align:middle;margin-left:6px;">
                    <?php endif; ?>
                  </div>
                  <div class="mb-2">
                    <span class="fw-semibold"> Emblem:</span>
                    <?php if (!empty($plan['emblemplan']['emblemplan']['attriicon'])): ?>
                      <img src="<?= htmlspecialchars($plan['emblemplan']['emblemplan']['attriicon']) ?>" alt="Emblem" style="height:32px;width:32px;vertical-align:middle;margin-left:6px;">
                    <?php endif; ?>
                  </div>
                  <div class="mb-2">
                    <span class="fw-semibold"> Talents:</span>
                    <?php
                      foreach (['giftid1', 'giftid2', 'giftid3'] as $giftKey) {
                        if (!empty($plan['emblemplan'][$giftKey]['emblemskill']['skillicon'])) {
                          echo '<img src="' . htmlspecialchars($plan['emblemplan'][$giftKey]['emblemskill']['skillicon']) . '" alt="Talent" style="height:32px;width:32px;vertical-align:middle;margin:0 3px 0 3px;">';
                        }
                      }
                    ?>
                  </div>
                  <div class="mb-2">
                    <span class="fw-semibold"> Equipment:</span>
                    <?php if (!empty($plan['equiplist'])): ?>
                      <?php foreach ($plan['equiplist'] as $equip): ?>
                        <?php if (!empty($equip['equipicon'])): ?>
                          <img src="<?= htmlspecialchars($equip['equipicon']) ?>" alt="<?= htmlspecialchars($equip['equipname']) ?>" title="<?= htmlspecialchars($equip['equipname']) ?>" style="height:32px;width:32px;vertical-align:middle;margin:0 3px 0 3px;">
                        <?php endif; ?>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($plan['description'])): ?>
                    <div class="text-muted mt-2" style="font-size:0.97em;"><?= htmlspecialchars($plan['description']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Counters & Compatibility Section -->
      <?php
        $counter_types = [
          'strong' => [
            'label' => 'Strong Against',
            'icon' => 'bi-shield-fill-check',
            'color' => 'success'
          ],
          'weak' => [
            'label' => 'Weak Against',
            'icon' => 'bi-exclamation-triangle-fill',
            'color' => 'danger'
          ],
          'assist' => [
            'label' => 'Best Partners',
            'icon' => 'bi-people-fill',
            'color' => 'primary'
          ]
        ];
      ?>
      <h3 class="section-title"><i class="bi bi-people-fill"></i> Counters & Compatibility</h3>
      <div class="row g-4">
        <?php foreach ($counter_types as $key => $info): ?>
          <?php if (!empty($relation[$key]['desc'])): ?>
            <div class="col-12 col-md-4">
              <div class="card counter-card h-100">
                <div class="card-body">
                  <div class="counter-header mb-2" style="font-size:1.15em;">
                    <i class="bi <?= $info['icon'] ?> text-<?= $info['color'] ?>"></i>
                    <?= $info['label'] ?>
                  </div>
                  <div class="counter-desc mb-3" style="font-size:0.98em;"><?= htmlspecialchars($relation[$key]['desc']) ?></div>
                  <div class="d-flex flex-wrap align-items-center gap-2">
                    <?php foreach ($relation[$key]['target_hero'] as $img): ?>
                      <?php
                        $head = is_array($img) && isset($img['data']['head']) && $img['data']['head']
                          ? $img['data']['head']
                          : 'https://via.placeholder.com/60x60?text=No+Image';
                      ?>
                      <img src="<?= htmlspecialchars($head) ?>" class="hero-icon" alt="Hero" style="height:48px;width:48px;">
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>