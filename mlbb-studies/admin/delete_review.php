<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'])) {
    $review_id = intval($_POST['review_id']);

    $mysqli = get_db_connection();
    $stmt = $mysqli->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $review_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Review deleted successfully.";
    } else {
        $_SESSION['message'] = "Failed to delete the review.";
    }

    $stmt->close();
    $mysqli->close();
}

header("Location: admin_reviews.php");
exit;
?>