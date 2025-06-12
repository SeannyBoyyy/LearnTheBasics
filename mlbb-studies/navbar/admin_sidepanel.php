<!-- Admin Sidebar Menu Button - Themed for MLBB Studies, with Minimize/Hamburger -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700&display=swap" rel="stylesheet">
<style>
.admin-sidebar-menu {
  background: linear-gradient(135deg, #0d1736 80%, #11182a 100%);
  border-radius: 12px;
  box-shadow: 0 2px 18px rgba(56,98,199,0.10);
  padding: 16px 0 16px 0;
  width: 68px;
  min-height: 320px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  position: fixed;
  top: 86px; /* Moved lower to clear the navbar/logo */
  left: 30px;
  z-index: 1050;
  font-family: 'Inter', 'Segoe UI', 'Roboto', Arial, sans-serif;
  transition: left 0.2s, box-shadow 0.2s, top 0.2s;
}
.admin-sidebar-menu.minimized {
  left: -82px;
  box-shadow: none;
}
.admin-sidebar-menu .menu-btn {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #b9cffd;
  background: none;
  border: none;
  border-radius: 9px;
  font-size: 1.7rem;
  margin-bottom: 2px;
  transition: background 0.18s, color 0.18s;
  cursor: pointer;
  position: relative;
}
.admin-sidebar-menu .menu-btn.active,
.admin-sidebar-menu .menu-btn:hover {
  background: linear-gradient(90deg, #193b7b 90%, #141c2b 100%);
  color: #6ea8fe;
  box-shadow: 0 2px 10px rgba(110,168,254,0.08);
}
.admin-sidebar-menu .menu-btn i {
  pointer-events: none;
}
.admin-sidebar-menu .tooltip-side {
  display: none;
  position: absolute;
  left: 50px;
  top: 50%;
  transform: translateY(-50%);
  background: #10172a;
  color: #6ea8fe;
  padding: 4px 13px;
  font-size: 0.97rem;
  border-radius: 7px;
  white-space: nowrap;
  box-shadow: 0 2px 8px #193b7b33;
  z-index: 1052;
}
.admin-sidebar-menu .menu-btn:hover .tooltip-side {
  display: block;
}
.admin-sidebar-menu .minimize-btn {
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 6px;
  border-radius: 8px;
  background: none;
  border: none;
  color: #6ea8fe;
  font-size: 1.45rem;
  cursor: pointer;
  transition: background 0.18s, color 0.18s;
}
.admin-sidebar-menu .minimize-btn:hover {
  background: #193b7b;
  color: #fff;
}
.hamburger-toggle {
  display: none;
  position: fixed;
  top: 92px; /* match sidebar top + margin */
  left: 28px;
  z-index: 1060;
  background: #10172a;
  color: #6ea8fe;
  border: none;
  border-radius: 8px;
  width: 48px;
  height: 48px;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  box-shadow: 0 2px 8px rgba(56,98,199,0.19);
  cursor: pointer;
}
@media (max-width: 600px) {
  .admin-sidebar-menu {
    left: -82px;
    width: 54px;
    padding: 10px 0;
    border-radius: 8px;
    min-height: 0;
    top: 58px; /* lower for mobile */
  }
  .hamburger-toggle {
    display: flex;
    left: 8px;
    top: 60px;
    width: 38px;
    height: 38px;
    font-size: 1.3rem;
  }
}
</style>
<!-- Hamburger Button (visible when sidebar is minimized) -->
<button class="hamburger-toggle" id="sidebarHamburger" style="display: none;" aria-label="Open admin menu">
  <i class="bi bi-list"></i>
</button>
<div class="admin-sidebar-menu" id="adminSidebarMenu">
  <button class="minimize-btn" id="sidebarMinimize" aria-label="Minimize admin menu"><i class="bi bi-chevron-left"></i></button>
  <a href="admin_dashboard.php" class="menu-btn<?php if(basename($_SERVER['PHP_SELF'])=='admin_dashboard.php') echo ' active'; ?>">
    <i class="bi bi-house-fill"></i>
    <span class="tooltip-side">Dashboard</span>
  </a>
  <a href="../hero-details.php" class="menu-btn<?php if(basename($_SERVER['PHP_SELF'])=='hero-details.php') echo ' active'; ?>">
    <i class="bi bi-person-badge-fill"></i>
    <span class="tooltip-side">Hero Details</span>
  </a>
  <a href="../hero-statistics.php" class="menu-btn<?php if(basename($_SERVER['PHP_SELF'])=='hero-statistics.php') echo ' active'; ?>">
    <i class="bi bi-bar-chart-fill"></i>
    <span class="tooltip-side">Hero Statistics</span>
  </a>
  <a href="../hero-position.php" class="menu-btn<?php if(basename($_SERVER['PHP_SELF'])=='hero-position.php') echo ' active'; ?>">
    <i class="bi bi-diagram-3-fill"></i>
    <span class="tooltip-side">Hero Positions</span>
  </a>
  <a href="../hero-rank.php" class="menu-btn<?php if(basename($_SERVER['PHP_SELF'])=='hero-rank.php') echo ' active'; ?>">
    <i class="bi bi-trophy-fill"></i>
    <span class="tooltip-side">Hero Ranks</span>
  </a>
  <a href="../dashboard.php" class="menu-btn<?php if(basename($_SERVER['PHP_SELF'])=='dashboard.php') echo ' active'; ?>">
    <i class="bi bi-people-fill"></i>
    <span class="tooltip-side">Analyze a Team</span>
  </a>
  <a href="admin_logout.php" class="menu-btn">
    <i class="bi bi-box-arrow-right"></i>
    <span class="tooltip-side">Logout</span>
  </a>
</div>
<script>
const sidebar = document.getElementById('adminSidebarMenu');
const minimizeBtn = document.getElementById('sidebarMinimize');
const hamburger = document.getElementById('sidebarHamburger');

// Minimize sidebar
minimizeBtn.addEventListener('click', function() {
  sidebar.classList.add('minimized');
  minimizeBtn.style.display = 'none';
  hamburger.style.display = 'flex';
});

// Show sidebar with hamburger
hamburger.addEventListener('click', function() {
  sidebar.classList.remove('minimized');
  minimizeBtn.style.display = 'flex';
  hamburger.style.display = 'none';
});

// Responsive: auto-minimize on mobile
function mobileSidebarInit() {
  if (window.innerWidth <= 600) {
    sidebar.classList.add('minimized');
    minimizeBtn.style.display = 'none';
    hamburger.style.display = 'flex';
  } else {
    sidebar.classList.remove('minimized');
    minimizeBtn.style.display = 'flex';
    hamburger.style.display = 'none';
  }
}
window.addEventListener('resize', mobileSidebarInit);
window.addEventListener('DOMContentLoaded', mobileSidebarInit);
</script>