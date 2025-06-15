<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mobile Legends Draft Analysis</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome for star ratings -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Swiper JS CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#317EFB">
  <link rel="apple-touch-icon" href="icons/icon-192x192.png">
  <meta name="mobile-web-app-capable" content="yes">

  <!-- Sweetalert -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
      max-width: 90%;
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
      pointer-events: none;
    }
    .main-title {
      font-size: clamp(2.5rem, 9vw, 6rem);
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
      font-size: clamp(2rem, 9vw, 5rem);
      font-weight: 800;
      letter-spacing: 0.04em;
      line-height: 1;
      text-transform: uppercase;
      color: transparent;
      -webkit-text-stroke: 2px #fff;
      text-stroke: 2px #fff;
      position: relative;
      z-index: 1;
      margin-top: -0.5em;
      margin-bottom: 0;
    }
    .hero-img {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      width: min(800px, 90%);
      max-height: 60vh;
      object-fit: contain;
      z-index: 3;
      pointer-events: none;
      user-select: none;
    }
    .tagline {
      letter-spacing: 0.6em;
      font-size: clamp(0.8rem, 1.1rem, 1.5rem);
      color: #bfc3d1;
      text-align: center;
      margin-top: 80px;
      margin-bottom: 30px;
      font-weight: 500;
      padding: 0 20px;
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
      padding: 0 20px;
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
      text-align: center;
      white-space: nowrap;
      text-decoration: none;
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

    /* Reviews Carousel Styles */
    .reviews-section {
      padding: 60px 0;
      background: rgba(2, 0, 27, 0.8);
      position: relative;
      z-index: 5;
    }
    .reviews-title {
      text-align: center;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 40px;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.1em;
    }
    .review-card {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 15px;
      padding: 30px;
      margin: 20px;
      min-height: 300px;
      display: flex;
      flex-direction: column;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .review-content {
      flex-grow: 1;
      font-size: 1rem;
      line-height: 1.6;
      color: #bfc3d1;
      margin-bottom: 20px;
    }
    .review-author {
      display: flex;
      align-items: center;
      margin-top: auto;
    }
    .author-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 15px;
      border: 2px solid #fff;
    }
    .author-info {
      display: flex;
      flex-direction: column;
    }
    .author-name {
      font-weight: 600;
      color: #fff;
      margin-bottom: 5px;
    }
    .author-title {
      font-size: 0.8rem;
      color: #bfc3d1;
    }
    .swiper-pagination-bullet {
      background: #fff;
      opacity: 0.5;
      width: 10px;
      height: 10px;
    }
    .swiper-pagination-bullet-active {
      opacity: 1;
      background: #fff;
    }
    .swiper-button-next, 
    .swiper-button-prev {
      color: #fff;
      width: 40px;
      height: 40px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      backdrop-filter: blur(5px);
    }
    .swiper-button-next:after, 
    .swiper-button-prev:after {
      font-size: 1.2rem;
    }

    @media (max-width: 1200px) {
      .hero-img {
        top: 45%;
      }
    }

    @media (max-width: 992px) {
      .search-bar {
        position: relative;
        margin: 20px auto 0;
        display: block;
        width: 90%;
        max-width: 400px;
        right: auto;
        top: auto;
      }
      .search-icon {
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
      }
      .tagline {
        letter-spacing: 0.3em;
        margin-top: 40px;
        margin-bottom: 20px;
      }
      .hero-img {
        position: relative;
        top: auto;
        transform: translateX(-50%);
        margin: 30px 0;
      }
      .desc, .analyze-btn {
        position: static;
        display: block;
        margin: 30px auto;
        text-align: center;
        max-width: 90%;
      }
      .desc {
        padding: 0 20px;
      }
      .analyze-btn {
        margin-top: 24px;
        margin-bottom: 50px;
        width: fit-content;
        padding: 12px 30px;
      }
      .reviews-section {
        padding: 40px 0;
      }
      .reviews-title {
        font-size: 1.5rem;
        margin-bottom: 30px;
      }
      .review-card {
        min-height: 280px;
        padding: 20px;
      }
    }

    @media (max-width: 768px) {
      .tagline {
        letter-spacing: 0.2em;
        font-size: 0.9rem;
      }
      .main-title {
        margin-top: 20px;
      }
      .subtitle {
        -webkit-text-stroke: 1px #fff;
        text-stroke: 1px #fff;
      }
      .analyze-btn {
        font-size: 1.1rem;
        padding: 12px 24px;
      }
      .search-bar {
        width: 85%;
      }
      .reviews-title {
        font-size: 1.3rem;
      }
      .review-card {
        min-height: 250px;
      }
    }

    @media (max-width: 576px) {
      .tagline {
        letter-spacing: 0.1em;
        font-size: 0.8rem;
      }
      .search-bar {
        margin-top: 15px;
        width: 80%;
      }
      .search-icon {
        right: 12px;
        font-size: 1.1rem;
      }
      .hero-img {
        width: 90%;
      }
      .desc {
        font-size: 0.9rem;
      }
      .analyze-btn {
        font-size: 1rem;
        padding: 10px 20px;
      }
      .reviews-section {
        padding: 30px 0;
      }
      .reviews-title {
        font-size: 1.1rem;
        margin-bottom: 20px;
      }
      .review-card {
        min-height: 220px;
        margin: 10px;
        padding: 15px;
      }
      .review-content {
        font-size: 0.9rem;
      }
      .author-avatar {
        width: 40px;
        height: 40px;
      }
      .swiper-button-next, 
      .swiper-button-prev {
        display: none;
      }
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

  <!-- Why Use This Website Section -->
  <section class="why-use-section py-5" style="background: linear-gradient(120deg, #222938 60%, #183058 100%); box-shadow: 0 8px 48px 0 #111a2a55;">
    <div class="container py-5">
      <div class="row align-items-center">
        <div class="col-md-6 mb-4 mb-md-0">
          <h2 class="fw-bold mb-3" style="color:#6ea8fe;">
            Why Use This Website for Team Draft Analysis?
          </h2>
          <ul class="list-unstyled mb-4">
            <li class="mb-3">
              <i class="fa fa-crosshairs text-info me-2"></i>
              <strong>Identify Team Weaknesses:</strong> Instantly spot gaps in your lineup using real winrate statistics and synergy data—see which picks leave you vulnerable and which fill your team's needs.
            </li>
            <li class="mb-3">
              <i class="fa fa-shield-halved text-success me-2"></i>
              <strong>Hero Counter Insights:</strong> Get recommendations for heroes that counter the enemy draft based on live and historical matchup data, not just opinion.
            </li>
            <li class="mb-3">
              <i class="fa fa-people-arrows text-warning me-2"></i>
              <strong>Best & Worst Synergies:</strong> Discover which heroes work best together and which combinations are statistically risky, using compatibility and negative impact ally data.
            </li>
            <li class="mb-3">
              <i class="fa fa-chart-bar text-primary me-2"></i>
              <strong>Data-Driven Decisions:</strong> All analysis uses up-to-date winrates, ban rates, and appearance rates—helping you avoid gut-feel mistakes and making every draft count.
            </li>
            <li class="mb-3">
              <i class="fa fa-bolt text-danger me-2"></i>
              <strong>Instant Visual Feedback:</strong> Our interface highlights strengths and weaknesses in your team draft at a glance—no more guesswork, only clear guidance.
            </li>
          </ul>
          <div class="alert alert-info">
            <i class="fa fa-lightbulb me-2"></i>
            <b>Tip:</b> Use the draft analyzer to simulate both your team and the enemy draft. See how your picks stack up—and adjust your strategy on the fly!
          </div>
        </div>
        <div class="col-md-6 text-center">
          <!-- Example Image: Team Draft Analysis -->
          <img src="./logo/logo-v2.png" alt="Draft Analysis Example" class="img-fluid rounded shadow" style="max-width: 95%; border: 3px solid #232837;">
          <div class="text-secondary mt-3" style="font-size:0.95rem;">Example: The left panel details hero stats, strengths and weaknesses. The right panel visualizes the current draft—showing counters, synergies, and winrate data at a glance.</div>
        </div>
      </div>
    </div>
  </section>

  <?php include 'carousel_hero.php'; ?>

  <!-- Reviews Carousel Section -->
  <section class="reviews-section py-5">
    <div class="container">
      <h2 class="reviews-title text-center text-white mb-4">What Players Say</h2>
      <div class="swiper reviews-swiper">
        <div class="swiper-wrapper">
          <?php
          // Fetch reviews from database
          require_once 'config.php';

          try {
              $conn = get_db_connection();
              $result = $conn->query("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 10");

              if ($result->num_rows === 0) {
                  echo '<div class="swiper-slide"><div class="card review-card h-100"><div class="card-body text-center d-flex align-items-center justify-content-center"><p class="mb-0">No reviews yet. Be the first to review!</p></div></div></div>';
              } else {
                  while ($review = $result->fetch_assoc()) {
                      $stars = str_repeat('<i class="fas fa-star text-warning"></i>', $review['rating']) . 
                              str_repeat('<i class="far fa-star text-warning"></i>', 5 - $review['rating']);
                      echo '
                      <div class="swiper-slide">
                        <div class="card review-card h-100 border-0 shadow">
                          <div class="card-body">
                            <div class="review-rating mb-3">'.$stars.'</div>
                            <div class="review-content mb-3">'.$review['comment'].'</div>
                            <div class="review-author d-flex align-items-center mt-auto">
                              <img src="https://ui-avatars.com/api/?name='.urlencode($review['username']).'&background=random&size=64" alt="User" class="author-avatar rounded-circle me-3">
                              <div class="author-info">
                                <span class="author-name d-block fw-bold text-white">'.$review['username'].'</span>
                                <span class="author-title small">'.date('M j, Y', strtotime($review['created_at'])).'</span>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>';
                  }
              }
              $conn->close();
          } catch (Exception $e) {
              echo '<div class="swiper-slide"><div class="card review-card h-100"><div class="card-body text-center d-flex align-items-center justify-content-center"><p class="mb-0 text-danger">Error loading reviews</p></div></div></div>';
          }
          ?>
        </div>
        <!-- Add pagination -->
        <div class="swiper-pagination"></div>
        <!-- Add navigation buttons -->
        <div class="swiper-button-next rounded-circle p-3 opacity-75"></div>
        <div class="swiper-button-prev rounded-circle p-3 opacity-75"></div>
      </div>
      
      <!-- Review Form -->
      <div class="review-form-container container mt-5 bg-dark bg-opacity-25 rounded-3 p-4 p-md-5 border border-light border-opacity-10">
        <h3 class="form-title text-center text-white mb-4">Leave Your Review</h3>
        <form id="reviewForm" action="submit_review.php" onsubmit="return validateReviewForm();" method="POST" class="mx-auto" style="max-width: 600px;">
          <input type="hidden" name="rating" id="ratingValue" value="0">
          
          <div class="rating-input mb-4 text-center">
            <i class="fas fa-star star mx-1" data-value="1" style="font-size: 2rem; cursor: pointer;"></i>
            <i class="fas fa-star star mx-1" data-value="2" style="font-size: 2rem; cursor: pointer;"></i>
            <i class="fas fa-star star mx-1" data-value="3" style="font-size: 2rem; cursor: pointer;"></i>
            <i class="fas fa-star star mx-1" data-value="4" style="font-size: 2rem; cursor: pointer;"></i>
            <i class="fas fa-star star mx-1" data-value="5" style="font-size: 2rem; cursor: pointer;"></i>
          </div>
          
          <div class="mb-4">
            <label for="reviewComment" class="form-label text-white">Your Review</label>
            <textarea class="form-control bg-secondary bg-opacity-50 text-white border-dark" id="reviewComment" name="comment" rows="4"
            placeholder="Type Review" required style="min-height: 120px;"></textarea>
          </div>
          
          <div class="text-center">
            <button type="submit" class="btn btn-light btn-lg px-4 fw-bold">Submit Review</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  
  <?php include 'navbar/footer.php'; ?>

  <!-- Swiper JS -->
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  <script>
    // Initialize Swiper
    document.addEventListener('DOMContentLoaded', function() {
      const swiper = new Swiper('.reviews-swiper', {
        loop: true,
        slidesPerView: 1,
        spaceBetween: 20,
        centeredSlides: true,
        autoplay: {
          delay: 5000,
          disableOnInteraction: false,
        },
        breakpoints: {
          576: {
            slidesPerView: 1,
          },
          768: {
            slidesPerView: 2,
            spaceBetween: 25,
          },
          992: {
            slidesPerView: 3,
            spaceBetween: 30,
          }
        },
        pagination: {
          el: '.swiper-pagination',
          clickable: true,
          dynamicBullets: true,
        },
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
        },
      });

      // Star rating functionality
      const stars = document.querySelectorAll('.star');
      const ratingValue = document.getElementById('ratingValue');
      
      stars.forEach(star => {
        star.addEventListener('click', function() {
          const value = parseInt(this.getAttribute('data-value'));
          ratingValue.value = value;
          
          stars.forEach((s, index) => {
            if (index < value) {
              s.classList.add('text-warning');
              s.classList.remove('far');
              s.classList.add('fas');
            } else {
              s.classList.remove('text-warning');
              s.classList.remove('fas');
              s.classList.add('far');
            }
          });
        });
        
        star.addEventListener('mouseover', function() {
          const value = parseInt(this.getAttribute('data-value'));
          stars.forEach((s, index) => {
            if (index < value) {
              s.classList.add('text-warning');
            } else {
              s.classList.remove('text-warning');
            }
          });
        });
        
        star.addEventListener('mouseout', function() {
          const currentRating = parseInt(ratingValue.value);
          stars.forEach((s, index) => {
            if (index < currentRating) {
              s.classList.add('text-warning');
            } else {
              s.classList.remove('text-warning');
            }
          });
        });
      });
    });
  </script>
  <script>
    function validateReviewForm() {
      const rating = parseInt(document.getElementById('ratingValue').value);
      const comment = document.getElementById('reviewComment').value.trim();

      if (!rating || rating < 1 || rating > 5) {
        Swal.fire({
          icon: 'warning',
          title: 'Invalid Rating',
            text: 'Please select a rating from 1 to 5 stars.'
          });
        return false;
      }

      if (comment.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Empty Review',
          text: 'Please write a comment for your review.'
        });
        return false;
      }

      return true;
    }
  </script>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const params = new URLSearchParams(window.location.search);
      const error = params.get('error');
      const success = params.get('success');

      if (error) {
        let message = '';
        switch (error) {
          case 'login_required':
            message = 'Please log in to submit a review.';
            break;
          case 'invalid_rating':
            message = 'Please select a valid rating (1–5 stars).';
            break;
          case 'empty_comment':
            message = 'Please write your review comment.';
            break;
          case 'already_reviewed':
            message = 'You have already submitted a review.';
            break;
          case 'server_error':
            message = 'Something went wrong. Please try again later.';
            break;
          default:
            message = 'An unknown error occurred.';
        }

        Swal.fire({
          icon: 'error',
          title: 'Review Submission Failed',
          text: message
        });
      }

      if (success === 'review_submitted') {
        Swal.fire({
          icon: 'success',
          title: 'Review Submitted',
          text: 'Thank you for your feedback!',
          showConfirmButton: false,
          timer: 2000
        });
      }
    });
  </script>
  <!-- Bootstrap JS CDN -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('service-worker.js')
        .then(function (registration) {
          console.log('ServiceWorker registered:', registration);
        })
        .catch(function (error) {
          console.log('ServiceWorker registration failed:', error);
        });
    });
  }
  </script>
</body>
</html>