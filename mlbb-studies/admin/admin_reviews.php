<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$mysqli = get_db_connection();

// Fetch all reviews with usernames
$query = "
    SELECT reviews.id, reviews.user_id, users.username, reviews.rating, reviews.comment, reviews.created_at
    FROM reviews
    LEFT JOIN users ON reviews.user_id = users.id
    ORDER BY reviews.created_at DESC
";
$result = $mysqli->query($query);
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <title>Admin Reviews - MLBB Studies</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #232837 0%, #181c24 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
    }
    .reviews-container {
      background: #181c24;
      border-radius: 18px;
      padding: 36px 32px;
      margin-top: 48px;
      box-shadow: 0 8px 36px rgba(110,168,254,0.13);
      border: 1.5px solid #6ea8fe33;
      max-width: 1100px;
      margin-left: auto;
      margin-right: auto;
    }
    .reviews-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 28px;
    }
    .reviews-header i {
      font-size: 2rem;
      color: #6ea8fe;
    }
    .table-responsive {
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 12px rgba(110,168,254,0.09);
    }
    .table-dark th, .table-dark td {
      vertical-align: middle;
      background: #202534 !important;
      border-color: #31394b;
      font-size: 1.04rem;
    }
    .table-dark thead th {
      color: #6ea8fe;
      font-weight: 600;
      letter-spacing: 0.03em;
      font-size: 1.06rem;
      background: #1b1f29 !important;
      border-bottom: 2px solid #425b7d;
    }
    .rating-stars {
      font-size: 1.2rem;
      letter-spacing: 0.02em;
      color: #ffcc40;
      font-weight: 600;
      text-shadow: 0 1px 2px #0005;
    }
    .review-comment {
      color: #e3ebf7;
      font-size: 1.01rem;
      white-space: pre-line;
      max-width: 350px;
      word-break: break-word;
    }
    .review-username {
      color: #80b1f7;
      font-weight: 500;
    }
    .review-date {
      color: #bfc9d1;
      font-size: 0.97rem;
    }
    .btn-danger {
      min-width: 82px;
    }
    /* Modal Customization */
    .modal-content {
      background-color: #212529;
      color: #fff;
      border-radius: 10px;
      border: 1.5px solid #6ea8fe33;
    }
    .modal-header {
      border-bottom: 1px solid #425b7d;
    }
    .modal-footer {
      border-top: 1px solid #425b7d;
    }
    @media (max-width: 768px) {
      .reviews-container {
        padding: 20px 5px;
      }
      .reviews-header h2 {
        font-size: 1.3rem;
      }
      .review-comment {
        max-width: 150px;
        font-size: 0.98rem;
      }
    }
  </style>
</head>
<body>

<?php include '../navbar/admin_navbar.php'; ?>

<?php include '../navbar/admin_sidepanel.php'; ?>

<div class="container reviews-container">
  <div class="reviews-header">
    <i class="bi bi-chat-dots-fill"></i>
    <h2 class="text-light m-0">User Ratings & Reviews</h2>
  </div>
  <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <?= $_SESSION['message'] ?>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message']); ?>
  <?php endif; ?>
  <div class="table-responsive">
    <table class="table table-dark table-hover align-middle mb-0">
      <thead>
        <tr>
          <th style="width:60px;">ID</th>
          <th style="width:180px;">Username</th>
          <th style="width:120px;">Rating</th>
          <th>Comment</th>
          <th style="width:160px;">Date Posted</th>
          <th style="width:110px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['id']) ?></td>
              <td class="review-username"><?= htmlspecialchars($row['username']) ?></td>
              <td>
                <span class="rating-stars"><?= str_repeat('★', (int)$row['rating']) ?>
                  <span class="text-muted"><?= str_repeat('☆', 5 - (int)$row['rating']) ?></span>
                </span>
                <span class="text-muted ms-1">(<?= (int)$row['rating'] ?>/5)</span>
              </td>
              <td class="review-comment"><?= nl2br(htmlspecialchars($row['comment'])) ?></td>
              <td class="review-date"><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
              <td>
                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>">
                  <i class="bi bi-trash"></i> Delete
                </button>
                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $row['id'] ?>" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger"></i> Confirm Delete</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        Are you sure you want to delete this review?
                        <div class="mt-2 text-warning"><small>"<?= htmlspecialchars($row['comment']) ?>"</small></div>
                      </div>
                      <div class="modal-footer">
                        <form method="POST" action="delete_review.php">
                          <input type="hidden" name="review_id" value="<?= htmlspecialchars($row['id']) ?>">
                          <button type="submit" class="btn btn-danger">Delete</button>
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="text-center text-secondary py-4">No reviews found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
