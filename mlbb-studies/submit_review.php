<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=login_required');
    exit;
}

$user_id = $_SESSION['user_id'];
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!$rating || $rating < 1 || $rating > 5) {
    header('Location: index.php?error=invalid_rating');
    exit;
}

if (empty($comment)) {
    header('Location: index.php?error=empty_comment');
    exit;
}

$conn = get_db_connection();

try {
    $stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $conn->close();
        header('Location: index.php?error=already_reviewed');
        exit;
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO reviews (user_id, rating, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $rating, $comment);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header('Location: index.php?success=review_submitted');
    exit;

} catch (Exception $e) {
    header('Location: index.php?error=server_error');
    exit;
}
