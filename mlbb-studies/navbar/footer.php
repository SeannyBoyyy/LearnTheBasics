<style>
  .footer {
    font-family: 'Montserrat', Arial, sans-serif;
    font-size: 1rem;
    letter-spacing: 0.01em;
    border-top: 1px solid #232849;
  }
  .footer .btn-link:hover,
  .footer .link-info:hover {
    text-decoration: underline;
    color: #6ea8fe!important;
  }
  .footer .vr {
    height: 20px;
    border-left: 2px solid #2a3147;
    margin: 0 0.5rem;
  }
  .footer .modal-content {
    border-radius: 1.2rem;
  }
  @media (max-width: 767.98px) {
    .footer .col-md-4 {
      text-align: center !important;
      margin-bottom: 1.25rem;
    }
    .footer .row > .col-md-4:last-child {
      margin-bottom: 0;
    }
    .footer img {
      margin: 0 auto 1rem auto;
      display: block;
    }
  }
</style>
<!-- Professional Footer Section -->
<footer class="footer mt-auto bg-gradient-dark pt-5 pb-4 shadow-lg" style="background: linear-gradient(120deg, #151922 60%, #232849 100%); color:#adb5bd;">
  <div class="container">
    <div class="row gy-4 align-items-center justify-content-between">
      <!-- Brand + Credits -->
      <div class="col-md-4 mb-3 mb-md-0 d-flex align-items-center">
        <img src="./logo/logo-v2.png" alt="Logo" style="height: 48px; width: 48px;" class="me-3 rounded shadow-sm">
        <div>
          <span class="fw-bold text-white" style="font-size: 1.22rem;">Mobile Legends Draft Analysis</span>
          <div style="font-size: 0.95rem;">
            <span class="text-secondary">Powered by </span>
            <a href="https://mlbb-stats.ridwaanhall.com/api/" class="link-info text-decoration-none" target="_blank" rel="noopener">Mobile Legends API</a>
            <span class="text-secondary">&amp;</span>
            <a href="https://mlbb-stats-docs.ridwaanhall.com/" class="link-info text-decoration-none" target="_blank" rel="noopener">MLBB Stats</a>
          </div>
        </div>
      </div>
      <!-- Quick Links: Admin & Contact -->
      <div class="col-md-4 mb-3 mb-md-0 text-center">
        <a href="admin_login.php" class="btn btn-link link-light text-decoration-none px-2 mx-2" style="font-weight:600;">
          <i class="fa fa-user-shield me-2"></i>Admin Login
        </a>
        <span class="vr mx-2 text-secondary"></span>
        <button type="button" class="btn btn-link link-info text-decoration-none px-2 mx-2" style="font-weight:600;" data-bs-toggle="modal" data-bs-target="#contactModal">
          <i class="fa fa-envelope me-2"></i>Contact Us
        </button>
      </div>
      <!-- Social / Legal -->
      <div class="col-md-4 text-center text-md-end">
        <div class="mb-2">
          <a href="https://facebook.com" target="_blank" rel="noopener" class="text-info me-2" style="font-size: 1.3rem;"><i class="fab fa-facebook"></i></a>
          <a href="https://twitter.com" target="_blank" rel="noopener" class="text-info me-2" style="font-size: 1.3rem;"><i class="fab fa-twitter"></i></a>
          <a href="https://github.com" target="_blank" rel="noopener" class="text-info me-2" style="font-size: 1.3rem;"><i class="fab fa-github"></i></a>
        </div>
        <small class="text-secondary">
          &copy; <?php echo date("Y"); ?> <span class="text-light">Mobile Legends Draft Analysis</span>. Not affiliated with Moonton.
        </small>
      </div>
    </div>
    <hr class="mt-4 mb-3" style="border-color: #242b3c;">
    <div class="row">
      <div class="col text-center" style="font-size:0.95rem;">
        <span class="text-secondary">All trademarks &amp; copyrights are property of their respective owners.</span>
      </div>
    </div>
  </div>

  <!-- Contact Us Modal -->
  <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-0 shadow">
        <div class="modal-header border-0">
            <h5 class="modal-title" id="contactModalLabel"><i class="fa fa-envelope me-2"></i>Contact Us</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <form id="contactForm" action="contact_submit.php" method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="contactName" class="form-label">Name</label>
                <input type="text" class="form-control bg-secondary bg-opacity-25 text-white border-0" id="contactName" name="name" required autocomplete="name">
            </div>
            <div class="mb-3">
                <label for="contactEmail" class="form-label">Email</label>
                <input type="email" class="form-control bg-secondary bg-opacity-25 text-white border-0" id="contactEmail" name="email" required autocomplete="email">
            </div>
            <div class="mb-3">
                <label for="contactMessage" class="form-label">Message</label>
                <textarea class="form-control bg-secondary bg-opacity-25 text-white border-0" id="contactMessage" name="message" rows="4" required></textarea>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-info rounded-pill px-4">Send</button>
            </div>
            </form>
        </div>
        </div>
    </div>
  </div>
</footer>

<!-- Contact form client validation -->
 <script>
  // SweetAlert2 validation and AJAX submit
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('contactForm');
    if (!form) return;
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const name = document.getElementById('contactName').value.trim();
      const email = document.getElementById('contactEmail').value.trim();
      const message = document.getElementById('contactMessage').value.trim();

      // Client-side validation
      if (name.length < 2) {
        Swal.fire({ icon: 'warning', title: 'Invalid Name', text: 'Please enter your name.' });
        return;
      }
      if (!/^[^@]+@[^@]+\.[^@]+$/.test(email)) {
        Swal.fire({ icon: 'warning', title: 'Invalid Email', text: 'Please enter a valid email address.' });
        return;
      }
      if (message.length < 2) {
        Swal.fire({ icon: 'warning', title: 'Empty Message', text: 'Please write your message.' });
        return;
      }

      // AJAX POST
      fetch('contact_submit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ name, email, message })
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Message Sent',
            text: data.message,
            showConfirmButton: false,
            timer: 2200
          });
          form.reset();
          setTimeout(() => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('contactModal'));
            if (modal) modal.hide();
          }, 2000);
        } else {
          Swal.fire({ icon: 'error', title: 'Could not send', text: data.message });
        }
      })
      .catch(() => {
        Swal.fire({ icon: 'error', title: 'Error', text: 'An unexpected error occurred. Please try again.' });
      });
    });
  });
</script>
<script>
  function validateContactForm() {
    const name = document.getElementById('contactName').value.trim();
    const email = document.getElementById('contactEmail').value.trim();
    const message = document.getElementById('contactMessage').value.trim();
    if (name.length < 2) {
      Swal.fire({ icon: 'warning', title: 'Invalid Name', text: 'Please enter your name.' });
      return false;
    }
    if (!email.match(/^[^@]+@[^@]+\.[^@]+$/)) {
      Swal.fire({ icon: 'warning', title: 'Invalid Email', text: 'Please enter a valid email address.' });
      return false;
    }
    if (message.length === 0) {
      Swal.fire({ icon: 'warning', title: 'Empty Message', text: 'Please write your message.' });
      return false;
    }
    return true;
  }
</script>