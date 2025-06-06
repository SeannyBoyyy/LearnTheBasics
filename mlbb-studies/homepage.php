<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mobile Legends Draft Analysis</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #151922;
      color: #fff;
      min-height: 100vh;
      font-family: 'Montserrat', Arial, sans-serif;
      overflow-x: hidden;
    }
    .search-bar {
      position: absolute;
      top: 32px;
      right: 60px;
      width: 300px;
      max-width: 90vw;
      z-index: 10;
    }
    .search-input {
      border-radius: 30px;
      border: none;
      padding: 8px 40px 8px 20px;
      width: 100%;
      background: #fff;
      color: #222;
      font-size: 1.1rem;
      outline: none;
    }
    .search-icon {
      position: absolute;
      right: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #222;
      font-size: 1.3rem;
    }
    .main-title {
      font-size: 9vw;
      font-weight: 900;
      letter-spacing: 0.04em;
      line-height: 1;
      text-transform: uppercase;
      margin-top: 10px;
      margin-bottom: 0;
      z-index: 2;
      position: relative;
    }
    .main-title span {
      display: block;
    }
    .subtitle {
      font-size: 9vw;
      font-weight: 800;
      letter-spacing: 0.04em;
      line-height: 1;
      text-transform: uppercase;
      color: transparent;
      -webkit-text-stroke: 2px #fff;
      text-stroke: 2px #fff;
      position: relative;
      z-index: 1;
      margin-top: -30px;
      margin-bottom: 0;
    }
    .hero-img {
      position: absolute;
      left: 50%;
      top: 45%;
      transform: translate(-50%, -44%);
      width: 800px;
      max-width: 90vw;
      z-index: 3;
      pointer-events: none;
      user-select: none;
    }
    .tagline {
      letter-spacing: 0.6em;
      font-size: 1.1rem;
      color: #bfc3d1;
      text-align: center;
      margin-top: 80px;
      margin-bottom: 30px;
      font-weight: 500;
    }
    .desc {
      position: absolute;
      left: 60px;
      bottom: 220px;
      max-width: 400px;
      font-size: 1rem;
      color: #bfc3d1;
      z-index: 4;
      font-weight: 400;
      letter-spacing: 0.01em;
    }
    .analyze-btn {
      position: absolute;
      right: 60px;
      bottom: 200px;
      z-index: 4;
      background: linear-gradient(90deg, #fff, #d3d3d3 80%);
      color: #222;
      border: none;
      border-radius: 8px;
      padding: 16px 38px;
      font-size: 1.3rem;
      font-weight: 600;
      box-shadow: 0 2px 16px 0 rgba(0,0,0,0.18);
      transition: background 0.2s, color 0.2s;
      outline: 2px solid #fff;
      outline-offset: 4px;
    }
    .analyze-btn:hover {
      background: linear-gradient(90deg, #e2e2e2, #fff 80%);
      color: #111;
    }
    .bottom-gradient {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 180px;
      background: linear-gradient(to top,rgb(2, 0, 27) 0%, transparent 100%);
      z-index: 1;
      pointer-events: none;
    }

    @media (max-width: 900px) {
      .main-title { font-size: 8vw; }
      .subtitle { font-size: 5vw; }
      .hero-img { width: 400px; }
      .desc, .analyze-btn { position: static; display: block; margin: 30px auto 0 auto; text-align: center; }
      .desc { max-width: 90vw; }
      .analyze-btn { margin-top: 24px; }
      .search-bar { position: static; margin: 20px auto 0 auto; display: block; }
    }
    @media (max-width: 600px) {
      .main-title { font-size: 11vw; }
      .subtitle { font-size: 7vw; }
      .hero-img { width: 260px; }
      .desc, .analyze-btn { font-size: 1rem; }
    }
  </style>
  <!-- Google Fonts for Montserrat -->
  <link href="https://fonts.googleapis.com/css?family=Montserrat:700,900&display=swap" rel="stylesheet">
</head>
<body>
  
  <?php include 'navbar/navbar.php'; ?>
  <div class="container-fluid position-relative p-0" style="min-height: 100vh; overflow: hidden;">
    <div class="search-bar">
      <input class="search-input" type="text" placeholder="" disabled>
      <span class="search-icon">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </span>
    </div>
    <div class="tagline">OUTDRAFT. &nbsp; OUTPLAY. &nbsp; DOMINATE.</div>
    <h1 class="main-title text-center">
      <span>MOBILE LEGENDS</span>
    </h1>
    <h2 class="subtitle text-center pt-4">
      DRAFT ANALYSIS
    </h2>
    <img src="./logo/bg.png" alt="Hero" class="hero-img">
    <div class="desc">
      ANALYZE PRO-LEVEL DRAFTS, EXPLORE COUNTER-PICKS, AND STAY AHEAD OF THE META IN MOBILE LEGENDS.
    </div>
    <a class="analyze-btn" href="dashboard.php">Analyze a Draft</a>

    <div class="bottom-gradient"></div>
  </div>
</body>
</html>