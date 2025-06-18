<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Get contact messages
$mysqli = get_db_connection();
$result = $mysqli->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Admin - Contact Messages</title>
    <link rel="icon" href="logo/logo-v2.png" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #232837 0%, #181c24 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        .dashboard-container {
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
        .contact-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }
        .contact-header i {
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
        }
        .table-dark thead th {
            color: #6ea8fe;
            font-weight: 600;
            letter-spacing: 0.03em;
            font-size: 1.05rem;
            background: #1b1f29 !important;
        }
        .contact-message {
            white-space: pre-line;
            font-size: 1.01rem;
            color: #e3ebf7;
        }
        .contact-name {
            font-weight: 500;
            color: #80b1f7;
        }
        .contact-email a {
            color: #5cb3fd;
            text-decoration: none;
            transition: color 0.2s;
        }
        .contact-email a:hover {
            text-decoration: underline;
            color: #fff;
        }
        .contact-date {
            color: #bfc9d1;
            font-size: 0.98rem;
        }
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 20px 5px;
            }
            .contact-header h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../navbar/admin_navbar.php'; ?>
    
    <?php include '../navbar/admin_sidepanel.php'; ?>
    
    <div class="container dashboard-container">
        <div class="contact-header">
            <i class="bi bi-envelope-at"></i>
            <h2 class="text-light m-0">Contact Messages</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">#</th>
                        <th style="width:180px;">Name</th>
                        <th style="width:220px;">Email</th>
                        <th>Message</th>
                        <th style="width:150px;">Received</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">No contact messages found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <tr>
                            <td><?= htmlspecialchars($msg['id']) ?></td>
                            <td class="contact-name"><?= htmlspecialchars($msg['name']) ?></td>
                            <td class="contact-email">
                                <a href="mailto:<?= htmlspecialchars($msg['email']) ?>">
                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($msg['email']) ?>
                                </a>
                            </td>
                            <td class="contact-message"><?= nl2br(htmlspecialchars($msg['message'])) ?></td>
                            <td class="contact-date"><?= date('Y-m-d H:i', strtotime($msg['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>