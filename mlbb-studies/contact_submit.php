<?php
// contact_submit.php
// Handles "Contact Us" form submission and saves message to the database (table must already exist).

require_once 'config.php'; // Must provide get_db_connection()

header('Content-Type: application/json; charset=utf-8');

// Helper: validate email
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validation
if (strlen($name) < 2) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please enter your name.'
    ]);
    exit;
}
if (!is_valid_email($email)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please enter a valid email address.'
    ]);
    exit;
}
if (strlen($message) < 2) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please enter your message.'
    ]);
    exit;
}

// Insert into database
try {
    $conn = get_db_connection();

    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("sss", $name, $email, $message);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Thank you for contacting us! We will get back to you soon.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Could not save your message. Please try again later.'
        ]);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . htmlspecialchars($e->getMessage())
    ]);
}
exit;
?>