<?php
// --- CONFIGURE THESE ---
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'draft_mlbb';
// -----------------------

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!$username || !$email || !$password || !$confirm) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_errno) {
            $error = "Database connection failed.";
        } else {
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE username=? OR email=?");
            $stmt->bind_param('ss', $username, $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error = "Username or email already exists.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                $stmt->bind_param('sss', $username, $email, $hash);
                if ($stmt->execute()) {
                    $success = "Signup successful! You can now log in.";
                } else {
                    $error = "Signup failed. Please try again.";
                }
            }
            $stmt->close();
            $mysqli->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <title>Sign Up - MLBB Studies</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #232837 0%, #181c24 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
    }
    .signup-card {
      border-radius: 18px;
      box-shadow: 0 6px 32px rgba(110,168,254,0.13);
      background: #232837;
      padding: 2.5rem 2rem 2rem 2rem;
      max-width: 420px;
      margin: 60px auto;
      border: 1.5px solid #6ea8fe33;
    }
    .form-label {
      color: #bfc9d1;
      font-weight: 500;
    }
    .form-control, .form-control:focus {
      background: #181c24;
      color: #e9ecef;
      border: 1px solid #343a40;
      border-radius: 8px;
      box-shadow: none;
    }
    .btn-primary {
      background: linear-gradient(90deg, #6ea8fe 60%, #468be6 100%);
      border: none;
      font-weight: 600;
      border-radius: 8px;
      transition: background 0.18s;
      box-shadow: 0 2px 8px #6ea8fe22;
    }
    .btn-primary:hover {
      background: linear-gradient(90deg, #468be6 60%, #6ea8fe 100%);
    }
    .logo {
      width: 154px;
      height: 154px;
      border-radius: 12px;
      margin-bottom: 12px;
      box-shadow: 0 2px 12px #6ea8fe33;
    }
    .form-title {
      font-weight: 700;
      color: #6ea8fe;
      margin-bottom: 18px;
      letter-spacing: 1px;
      text-shadow: 0 2px 8px #0d223a44;
    }
    .form-text {
      color: #bfc9d1;
      font-size: 0.98em;
    }
    .alert {
      border-radius: 8px;
      font-size: 1em;
    }
  </style>
</head>
<body>
    
<?php include 'navbar/navbar.php'; ?>

  <div class="signup-card">
    <div class="text-center">
      <img src="./logo/logo-v2.png" class="logo" alt="Logo">
      <h2 class="form-title">Create Your Account</h2>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off" novalidate>
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" maxlength="50" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" class="form-control" id="email" name="email" maxlength="100" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password <span class="form-text">(min 6 chars)</span></label>
        <input type="password" class="form-control" id="password" name="password" minlength="6" required>
      </div>
      <div class="mb-3">
        <label for="confirm" class="form-label">Confirm Password</label>
        <input type="password" class="form-control" id="confirm" name="confirm" minlength="6" required>
      </div>
      <button type="submit" class="btn btn-primary w-100 py-2 mt-2">Sign Up</button>
    </form>
    <div class="text-center mt-3">
      <span class="form-text">Already have an account? <a href="login.php" class="text-decoration-none text-info">Log in</a></span>
    </div>
  </div>
</body>
</html>