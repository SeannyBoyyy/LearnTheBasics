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
    .table-container {
      background: #1e222d;
      border-radius: 14px;
      padding: 24px;
      box-shadow: 0 4px 24px rgba(110,168,254,0.07);
    }
    .table th, .table td {
      vertical-align: middle;
    }
    .modal-content {
      background-color: #212529;
      color: #fff;
    }
  </style>
</head>
<body>

<?php include '../navbar/admin_navbar.php'; ?>

<div class="container mt-5">
  <h2 class="text-center text-light mb-4"><i class="bi bi-chat-dots-fill"></i> User Ratings & Reviews</h2>

  <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <?= $_SESSION['message'] ?>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message']); ?>
  <?php endif; ?>

  <div class="table-container mt-4">
    <table class="table table-dark table-hover table-bordered align-middle text-center">
      <thead class="table-secondary text-dark">
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Rating</th>
          <th>Comment</th>
          <th>Date Posted</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['username']) ?></td>
              <td><span class="text-warning"><?= str_repeat('★', $row['rating']) ?></span></td>
              <td><?= htmlspecialchars($row['comment']) ?></td>
              <td><?= $row['created_at'] ?></td>
              <td>
                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>">
                  <i class="bi bi-trash"></i> Delete
                </button>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $row['id'] ?>" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-dark text-light">
                      <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger"></i> Confirm Delete</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        Are you sure you want to delete this review?
                        <div class="mt-2 text-muted"><small>"<?= htmlspecialchars($row['comment']) ?>"</small></div>
                      </div>
                      <div class="modal-footer">
                        <form method="POST" action="delete_review.php">
                          <input type="hidden" name="review_id" value="<?= $row['id'] ?>">
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
            <td colspan="6" class="text-muted">No reviews found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
