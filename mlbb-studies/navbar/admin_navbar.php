<!-- Add these in your <head> -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700&display=swap" rel="stylesheet">

<style>
  /* Professional font and hover effect */
  .navbar, .navbar * {
    font-family: 'Inter', 'Segoe UI', 'Roboto', Arial, sans-serif !important;
  }
  .navbar .nav-link:hover, .navbar .nav-link.active {
    color: #6ea8fe !important;
    background: rgba(110,168,254,0.07);
    border-radius: 8px;
    transition: background 0.2s, color 0.2s;
  }
</style>

<!-- Place this right after <body> -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top" style="background: linear-gradient(90deg, #232837 60%, #181c24 100%); border-bottom: 2px solid #6ea8fe33; font-family: 'Inter', 'Segoe UI', 'Roboto', Arial, sans-serif;">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="../index.php" style="font-size: 1.35rem; letter-spacing: 1px; font-weight: 700;">
      <img src="../logo/logo-png.png" alt="MLBB" width="56" height="56" class="rounded-circle border border-2 border-primary shadow-sm" style="background: #232837;">
      <span style="color: #6ea8fe;">MLBB Studies</span>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mlbbNavbar" aria-controls="mlbbNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mlbbNavbar">
      <ul class="navbar-nav ms-auto gap-lg-2">
        <li class="nav-item">
          <a class="nav-link px-3<?php if(basename($_SERVER['PHP_SELF'])=='admin_dashboard.php') echo ' active'; ?>" href="admin_dashboard.php" style="font-size: 1.08rem; font-weight: 500;">
            Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3<?php if(basename($_SERVER['PHP_SELF'])=='admin_analytics.php') echo ' active'; ?>" href="admin_analytics.php" style="font-size: 1.08rem; font-weight: 500;">
            Chart Analytics
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3<?php if(basename($_SERVER['PHP_SELF'])=='admin_reviews.php') echo ' active'; ?>" href="admin_reviews.php" style="font-size: 1.08rem; font-weight: 500;">
            Reviews
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3<?php if(basename($_SERVER['PHP_SELF'])=='hero-rank.php') echo ' active'; ?>" href="../hero-rank.php" style="font-size: 1.08rem; font-weight: 500;">
            Hero Ranks
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3<?php if(basename($_SERVER['PHP_SELF'])=='dashboard.php') echo ' active'; ?>" href="../dashboard.php" style="font-size: 1.08rem; font-weight: 500;">
            Dashboard
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<!-- End Navbar -->