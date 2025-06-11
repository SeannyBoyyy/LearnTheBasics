<?php
// --- HERO LIST PAGE CODE ---

$save_dir = __DIR__ . '/hero_cache/';
$roles = ['All', 'Tank', 'Fighter', 'Assassin', 'Mage', 'Marksman', 'Support'];
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'All';

$hero_list = [];
for ($i = 1; $i <= 128; $i++) {
    $file = $save_dir . "$i.json";
    if (!file_exists($file)) continue;
    $json = @file_get_contents($file);
    $data = json_decode($json, true);
    if (!isset($data['data']['records'][0]['data']['hero']['data'])) continue;
    $hero = $data['data']['records'][0]['data']['hero']['data'];
    $roles_arr = array_filter($hero['sortlabel'] ?? [], fn($r) => trim($r) !== '');
    $hero_list[] = [
        'heroid'      => $hero['heroid'] ?? $i,
        'name'        => $hero['name'] ?? 'Unknown',
        'painting'    => $hero['painting'] ?? '',
        'head_big'    => $hero['head_big'] ?? '',
        'head'        => $hero['head'] ?? '',
        'smallmap'    => $hero['smallmap'] ?? '',
        'sortlabel'   => array_values($roles_arr),
        'speciality'  => $hero['speciality'] ?? [],
        'squarehead'  => $hero['squarehead'] ?? '',
    ];
}
usort($hero_list, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});
function filter_heroes($heroes, $role_filter) {
    if ($role_filter === 'All') return $heroes;
    $filtered = [];
    foreach ($heroes as $hero) {
        if (!empty($hero['sortlabel']) && in_array($role_filter, $hero['sortlabel'])) {
            $filtered[] = $hero;
        }
    }
    return $filtered;
}
function hero_image_url($hero) {
    foreach (['smallmap', 'painting', 'head_big', 'head'] as $key) {
        if (!empty($hero[$key])) return $hero[$key];
    }
    return 'https://via.placeholder.com/300x400?text=No+Image';
}
$display_heroes = filter_heroes($hero_list, $role_filter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>All Heroes - Mobile Legends</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #161e30;
      color: #f2f4f7;
      font-family: 'Inter', 'Segoe UI', 'Roboto', Arial, sans-serif;
    }
    /* Sticky role selection bar */
    .role-sticky-bar {
      position: sticky;
      top: 78px; /* Height of navbar + small gap */
      z-index: 20;
      background: #1c2333;
      padding: 0 0 0 0;
      border-bottom: 2px solid #232f4a;
      margin-bottom: 24px;
      box-shadow: 0 2px 12px #111a2a16;
    }
    .role-sticky-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      padding: 14px 2vw 0 2vw;
    }
    .hero-nav-tabs {
      display: flex;
      gap: 32px;
      flex-wrap: wrap;
      margin-bottom: 0;
      border-bottom: none;
    }
    .hero-nav-tab {
      color: #bfc3d1;
      background: none;
      font-size: 1.25rem;
      font-weight: 400;
      border: none;
      border-radius: 0;
      padding: 9px 0;
      margin-right: 0;
      border-bottom: 4px solid transparent;
      transition: color 0.16s, border-color 0.16s, background 0.16s;
      cursor: pointer;
      text-transform: capitalize;
      letter-spacing: 0.02em;
      outline: none;
      position: relative;
      text-decoration: none;
    }
    .hero-nav-tab.active, .hero-nav-tab:focus {
      color: #6ea8fe;
      border-bottom: 4px solid #6ea8fe;
      background: rgba(110,168,254,0.08);
      border-radius: 8px 8px 0 0;
      font-weight: 700;
      box-shadow: 0 4px 16px #6ea8fe22 inset;
    }
    .hero-nav-tab:hover:not(.active) {
      color: #fff;
      background: rgba(110,168,254,0.07);
      border-radius: 8px 8px 0 0;
      text-shadow: 0 1px 8px #263d5c44;
    }
    .hero-roles-dropdown {
      background: #252e45;
      border: 1.5px solid #2a3651;
      color: #fff;
      font-weight: 600;
      border-radius: 8px;
      padding: 7px 24px 7px 16px;
      font-size: 1.07em;
      outline: none;
      box-shadow: 0 2px 10px #6ea8fe11;
      cursor: pointer;
      appearance: none;
      margin-left: 18px;
      transition: border 0.15s;
      min-width: 98px;
    }
    .hero-roles-dropdown:focus {
      border-color: #6ea8fe;
    }
    .hero-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 36px 22px;
      margin-top: 12px;
      margin-bottom: 30px;
    }
    .hero-card {
      background: transparent;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 18px #0d1a2a33;
      cursor: pointer;
      border: none;
      position: relative;
      aspect-ratio: 14 / 19;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      min-height: 320px;
      margin-bottom: 0;
      transition: box-shadow 0.17s, transform 0.15s;
    }
    .hero-card:hover {
      box-shadow: 0 12px 48px #6ea8fe77;
      transform: translateY(-3px) scale(1.018);
      z-index: 2;
    }
    .hero-img-wrapper {
      width: 100%;
      height: 100%;
      position: relative;
      background: #181c24;
      overflow: hidden;
      flex: 1 1 auto;
      display: flex;
      align-items: stretch;
      justify-content: stretch;
    }
    .hero-img-wrapper img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      background: #232837;
      transition: filter 0.22s;
      min-height: 240px;
    }
    .hero-name-bar {
      position: absolute;
      left: 0; right: 0; bottom: 0;
      padding: 0;
      z-index: 2;
      height: 56px;
      display: flex;
      align-items: flex-end;
    }
    .hero-name-bar-gradient {
      position: absolute;
      left: 0; right: 0; bottom: 0;
      height: 56px;
      background: linear-gradient(0deg, rgba(15,28,44,0.95) 72%, rgba(15,28,44,0.0) 100%);
      pointer-events: none;
      z-index: 1;
    }
    .hero-name {
      width: 100%;
      color: #fff;
      font-size: 1.18rem;
      font-weight: 700;
      text-align: center;
      letter-spacing: .04em;
      margin: 0;
      padding-bottom: 12px;
      padding-top: 18px;
      position: relative;
      z-index: 2;
      text-shadow: 0 2px 16px #142340ee;
      font-family: 'Inter', 'Segoe UI', 'Roboto', Arial, sans-serif;
      line-height: 1.1;
      user-select: none;
      pointer-events: none;
      background: none;
    }
    @media (max-width: 900px) {
      .hero-grid { gap: 18px 6px; }
      .hero-card { min-height: 220px; }
      .hero-name-bar, .hero-name-bar-gradient { height: 44px; }
      .hero-name { font-size: 1.06rem; padding-bottom: 8px; padding-top: 10px;}
    }
    @media (max-width: 650px) {
      .hero-grid { grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); }
      .custom-navbar { padding-left: 4px; padding-right: 4px; }
      .role-sticky-inner { padding-left: 4px; padding-right: 4px; }
    }
  </style>
</head>
<body>

<?php include 'navbar/navbar.php'; ?>

<!-- Sticky Role Selection -->
<div class="role-sticky-bar">
  <div class="role-sticky-inner">
    <div class="hero-nav-tabs">
      <?php foreach ($roles as $role): ?>
        <a href="?role=<?= urlencode($role) ?>" class="hero-nav-tab<?= $role_filter === $role ? ' active' : '' ?>">
          <?= htmlspecialchars($role) ?>
        </a>
      <?php endforeach; ?>
    </div>
    <form class="d-flex mb-2" method="get" action="">
      <select name="role" class="hero-roles-dropdown" onchange="this.form.submit()">
        <?php foreach ($roles as $role): ?>
          <option value="<?= htmlspecialchars($role) ?>" <?= $role_filter === $role ? 'selected' : '' ?>>
            <?= htmlspecialchars($role) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
</div>
<!-- End Sticky Role Selection -->

<div class="container">
  <div class="hero-grid">
    <?php foreach ($display_heroes as $hero): ?>
      <div class="hero-card" tabindex="0" onclick="window.location='hero-details.php?id=<?= intval($hero['heroid'] ?? $hero['id'] ?? 0) ?>'">
        <div class="hero-img-wrapper">
          <img src="<?= htmlspecialchars(hero_image_url($hero)) ?>"
               alt="<?= htmlspecialchars($hero['name']) ?>">
          <div class="hero-name-bar-gradient"></div>
          <div class="hero-name-bar">
            <div class="hero-name"><?= htmlspecialchars($hero['name']) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (empty($display_heroes)): ?>
    <div class="alert alert-info text-center mt-5">No heroes found for this role.</div>
  <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>