<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    if ($identifier === '') {
        $msg = '<div class="alert alert-danger">Please enter your username, email, or phone number.</div>';
    } else {
        // Search for user by username, email, or phone number
  $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? OR phoneNumber = ?');
  $stmt->bind_param('sss', $identifier, $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            $user_id = $user['id'];
            // Generate a temporary password
            $temp_password = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz'), 0, 8);
            $hashed = password_hash($temp_password, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare('UPDATE users SET password=? WHERE id=?');
            $stmt2->bind_param('si', $hashed, $user_id);
            if ($stmt2->execute()) {
                $msg = '<div class="alert alert-success">Your temporary password is:<br><strong>' . htmlspecialchars($temp_password) . '</strong><br>Please use it to log in and change your password immediately.</div>';
            } else {
                $msg = '<div class="alert alert-danger">Failed to reset password. Please contact support.</div>';
            }
            $stmt2->close();
        } else {
            $msg = '<div class="alert alert-danger">No account found with that information.</div>';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - Quick Cart</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato&display=swap" rel="stylesheet">
</head>
<body style="font-family: 'Lato', sans-serif; background-color: #f7f7f7;">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php" style="font-family: 'Montserrat', sans-serif;">
      <i class="bi bi-person-fill me-2"></i>CommServe
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
  </div>
</nav>

<section class="d-flex align-items-center justify-content-center vh-100">
  <div class="card p-4 shadow-sm rounded-4" style="max-width: 400px; width: 100%;">
    <h3 class="text-center fw-bold mb-3" style="font-family: 'Montserrat', sans-serif;">
      <i class="bi bi-key me-2"></i>Forgot Your Password?
    </h3>
    <p class="text-center mb-3">Enter your email below and we'll send you a reset link.</p>
    <?php if ($msg) echo $msg; ?>
    <form method="POST" action="">
      <div class="mb-3">
        <label for="identifier" class="form-label">Username, Email, or Phone Number</label>
        <input type="text" name="identifier" class="form-control rounded-pill" id="identifier" placeholder="Enter username, email, or phone number" required>
      </div>
      <button type="submit" class="btn btn-dark w-100 rounded-pill mb-2">Send Reset Link</button>
      <div class="text-center">
        <a href="loginpage.php">Back to Login</a>
      </div>
    </form>
  </div>
</section>

<footer class="text-white text-center py-4 bg-dark">
  <p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
