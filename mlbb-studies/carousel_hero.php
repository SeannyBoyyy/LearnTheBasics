<?php
  // Directory where you downloaded/cached the hero API JSON files
  $cache_dir = __DIR__ . '/hero_cache/';
  $total_heroes = 128;
  $recent_hero_ids = range($total_heroes, $total_heroes - 4); // [128,127,126,125,124]

  // Helper for image fallback (painting, head_big, head, placeholder)
  function get_best_hero_image($record) {
      $candidates = [
          $record['painting'] ?? '',
          $record['hero']['data']['painting'] ?? '',
          $record['head_big'] ?? '',
          $record['head'] ?? '',
      ];
      foreach ($candidates as $img) {
          if (!empty($img)) return $img;
      }
      return 'https://via.placeholder.com/250x250?text=No+Image';
  }

  // Parse all heroes from local cache
  $heroes = [];
  foreach ($recent_hero_ids as $hid) {
      $file = $cache_dir . "$hid.json";
      if (!file_exists($file)) continue;
      $json = file_get_contents($file);
      $data = $json ? json_decode($json, true) : null;
      $record = $data['data']['records'][0]['data'] ?? null;
      if (!$record) continue;
      $hero = $record['hero']['data'] ?? null;
      if (!$hero) continue;
      $hero['painting_main'] = get_best_hero_image($record);
      $hero['heroid'] = $hero['heroid'] ?? $hid;
      $hero['head'] = $hero['head'] ?? '';
      $heroes[] = $hero;
  }
  ?>
  <section class="container my-5" id="hero-carousel-section">
    <div class="hero-carousel-bg rounded-4 shadow-lg px-0">
      <div class="swiper hero-swiper">
        <div class="swiper-wrapper">
          <?php foreach ($heroes as $index => $hero): ?>
          <div class="swiper-slide">
            <div class="d-flex flex-column flex-lg-row align-items-stretch bg-transparent p-4 p-lg-5" style="min-height:440px;">
              <!-- Left: Hero Splash Art -->
              <div class="flex-shrink-0 text-center position-relative mb-4 mb-lg-0" style="width:340px;">
                <div class="hero-splash-wrapper position-relative mx-auto">
                  <img src="<?= htmlspecialchars($hero['painting_main']) ?>"
                      alt="<?= htmlspecialchars($hero['name']) ?>"
                      class="img-fluid rounded-4 shadow hero-splash"
                      style="max-width:320px; max-height:410px; background:#171a23;">
                  <!-- Mini Hero Head Carousel -->
                  <div class="d-flex justify-content-center align-items-center mt-3 gap-2">
                    <?php foreach ($heroes as $h2): ?>
                      <img src="<?= htmlspecialchars($h2['head']) ?>"
                          class="rounded-circle border border-3 <?= $h2['name']===$hero['name']?'border-info shadow':'' ?>"
                          style="width:48px;height:48px;object-fit:cover;cursor:pointer;<?= $h2['name']===$hero['name']?'opacity:1;filter:brightness(1.1);':'opacity:0.65;' ?>"
                          onclick="heroSwiper.slideTo(<?= array_search($h2['name'], array_column($heroes,'name')) ?>);"
                          alt="<?= htmlspecialchars($h2['name']) ?>"
                          title="<?= htmlspecialchars($h2['name']) ?>">
                    <?php endforeach; ?>
                    <a href="heroes.php" class="btn btn-outline-info ms-2 rounded-circle d-flex align-items-center justify-content-center"
                      style="height:48px;width:48px;font-size:1.6rem;" title="More Heroes">
                      <i class="fas fa-plus"></i>
                    </a>
                  </div>
                </div>
              </div>
              <!-- Right: Hero Info -->
              <div class="flex-grow-1 ps-lg-5 d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center mb-2 flex-wrap gap-2">
                  <span class="h2 mb-0 me-2 fw-bold"><?= htmlspecialchars($hero['name']) ?></span>
                  <?php
                    foreach ($hero['sortlabel'] as $role) {
                      if ($role) echo '<span class="badge bg-primary fw-semibold px-3 py-2" style="font-size:1em;">'.htmlspecialchars($role).'</span>';
                    }
                  ?>
                </div>
                <div class="mb-3 text-info fw-semibold" style="font-size:1.09rem;">
                  <?php if (!empty($hero['speciality'])) echo htmlspecialchars(implode(' · ', $hero['speciality'])); ?>
                </div>
                <!-- Skills Row -->
                <div class="d-flex align-items-center mb-4 gap-3 flex-wrap">
                  <?php
                    $skills = [];
                    foreach ($hero['heroskilllist'] as $sklist) {
                      foreach ($sklist['skilllist'] as $sk) {
                        if (!in_array($sk['skillicon'], array_column($skills, 'skillicon'))) $skills[] = $sk;
                      }
                    }
                    foreach ($skills as $i => $sk):
                  ?>
                    <div class="position-relative">
                      <img src="<?= htmlspecialchars($sk['skillicon']) ?>"
                          class="rounded-circle border border-2 border-info shadow skill-icon"
                          style="width:64px;height:64px;object-fit:cover;cursor:pointer;transition:box-shadow .2s;"
                          title="<?= htmlspecialchars($sk['skillname']) ?>"
                          tabindex="0"
                          data-skill="<?= $index ?>-<?= $i ?>">
                      <div class="skill-tooltip card p-2 bg-dark text-light shadow-sm"
                          id="tooltip-<?= $index ?>-<?= $i ?>"
                          style="position:absolute;display:none;min-width:660px;z-index:60;left:50%;top:50px;transform:translateX(-20%);">
                        <div class="fw-bold mb-1"><?= htmlspecialchars($sk['skillname']) ?></div>
                        <div class="mb-1 text-info" style="font-size:.94em;"><?= htmlspecialchars($sk['skillcd&cost']) ?></div>
                        <div style="font-size:0.78em;">
                          <?= preg_replace('/<font color="([a-zA-Z0-9#]+)">/', '<span style="color:#$1">', $sk['skilldesc']) ?>
                        </div>
                        <?php if (!empty($sk['skilltag'])): ?>
                          <div class="">
                            <?php foreach ($sk['skilltag'] as $tag): ?>
                              <span class="badge" style="background:rgba(<?= $tag['tagrgb'] ?>,0.8);"><?= htmlspecialchars($tag['tagname']) ?></span>
                            <?php endforeach; ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <!-- Stats Bars -->
                <div class="row mb-2">
                  <?php
                    $labels = ['Durability','Offense','Ability Effects','Difficulty'];
                    foreach ($hero['abilityshow'] as $i=>$value):
                      $bar = intval($value);
                  ?>
                    <div class="col-6 col-md-3 mb-2">
                      <div class="d-flex align-items-center mb-1 fw-semibold" style="font-size:.98em;">
                        <span><?= $labels[$i] ?></span>
                      </div>
                      <div class="progress" style="height:9px;background:#232737;">
                        <div class="progress-bar bg-info" role="progressbar"
                          style="width:<?= $bar ?>%;background:linear-gradient(90deg,#13e0e5 40%,#3d8bfa 100%);"
                          aria-valuenow="<?= $bar ?>" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <a href="hero-details.php?id=<?= intval($hero['heroid']) ?>" class="mt-3 d-inline-block fw-semibold text-info" style="text-decoration:none;font-size:1.09em;">
                  More Info <i class="fas fa-arrow-right"></i>
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <!-- Carousel Arrows -->
        <div class="swiper-button-prev hero-swiper-prev"></div>
        <div class="swiper-button-next hero-swiper-next"></div>
      </div>
    </div>
  </section>

  <style>
  #hero-carousel-section {
    background: none;
  }
  .hero-carousel-bg {
    background: linear-gradient(120deg, #222938 60%, #183058 100%);
    box-shadow: 0 8px 48px 0 #111a2a55;
    border-radius: 2rem;
    padding-top: 2rem;
    padding-bottom: 2rem;
  }
  .hero-splash-wrapper {
    background: radial-gradient(ellipse at center, #233f5b 70%, #151922 98%);
    border-radius: 24px 24px 32px 32px;
    box-shadow: 0 8px 40px #099bff33;
    padding: 1.5rem 0 0.5rem 0;
  }
  .hero-splash {
    transition: box-shadow .22s;
    box-shadow: 0 8px 38px 0 #099bff55;
    background: #141722;
  }
  .hero-splash:hover { box-shadow: 0 14px 54px 0 #099bffa0; }
  .skill-icon:hover, .skill-icon:focus { box-shadow:0 0 0 4px #0ff8; }
  .skill-tooltip { pointer-events:none; opacity:0; transition:opacity .2s; }
  .skill-icon.active + .skill-tooltip, .skill-icon:focus + .skill-tooltip {
    display:block;
    pointer-events:auto;
    opacity:1;
  }
  .swiper-button-prev.hero-swiper-prev, .swiper-button-next.hero-swiper-next {
    background: rgba(40,56,78,0.95);
    border-radius: 50%;
    width: 40px; height: 40px;
    top: 49%;
    color: #fff;
    box-shadow: 0 2px 14px #2227;
    border: 2px solid #1e2f49;
  }
  .swiper-button-prev.hero-swiper-prev { left: 10px; }
  .swiper-button-next.hero-swiper-next { right: 10px; }
  .swiper-button-prev.hero-swiper-prev:after, .swiper-button-next.hero-swiper-next:after {
    font-size: 1.5rem;
  }
  @media (max-width: 992px) {
    .hero-splash-wrapper { padding: .5rem 0; }
    .hero-splash { max-width: 220px; max-height: 320px; }
  }
  @media (max-width: 768px) {
    .hero-splash-wrapper { padding: 0.1rem 0; }
    .hero-splash { max-width: 150px; max-height: 200px; }
    .hero-carousel-bg { padding: 1rem 0.5rem; }
  }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  <script>
  var heroSwiper;
  document.addEventListener('DOMContentLoaded', function() {
    heroSwiper = new Swiper('.hero-swiper', {
      slidesPerView: 1,
      centeredSlides: true,
      loop: true,
      navigation: {
        nextEl: '.hero-swiper-next',
        prevEl: '.hero-swiper-prev'
      }
    });

    // Skill tooltip logic (hover/click/focus)
    document.querySelectorAll('.skill-icon').forEach(function(img) {
      let showTip = function() {
        let tip = document.getElementById('tooltip-' + img.getAttribute('data-skill'));
        if (tip) { tip.style.display = 'block'; tip.style.opacity = '1'; }
        img.classList.add('active');
      };
      let hideTip = function() {
        let tip = document.getElementById('tooltip-' + img.getAttribute('data-skill'));
        if (tip) { tip.style.display = 'none'; tip.style.opacity = '0'; }
        img.classList.remove('active');
      };
      img.addEventListener('mouseenter', showTip);
      img.addEventListener('focus', showTip);
      img.addEventListener('mouseleave', hideTip);
      img.addEventListener('blur', hideTip);
      img.addEventListener('click', function(e){
        let tip = document.getElementById('tooltip-' + img.getAttribute('data-skill'));
        if (tip) {
          tip.style.display = tip.style.display === 'block' ? 'none' : 'block';
          tip.style.opacity = tip.style.display === 'block' ? '1' : '0';
        }
        img.classList.toggle('active');
      });
    });
  });
  </script>