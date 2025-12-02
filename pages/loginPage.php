<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../userAccounts/config.php';

$error = '';
$identifier = '';

// If already logged in, go straight to the right dashboard
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: /dashboards/adminDashboard.php');
    } elseif ($_SESSION['role'] === 'official') {
        header('Location: /dashboards/officialDashboard.php');
    } else {
        header('Location: /dashboards/userDashboard.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $error = 'Please enter your username/email and password.';
    } else {
        // Find by username OR email
        $sql  = "SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                  // Regenerate session id to prevent session fixation
                  session_regenerate_id(true);

                  // Set session
                  $_SESSION['user_id']  = (int)$row['id'];
                  $_SESSION['username'] = $row['username'];
                  $_SESSION['role']     = $row['role'] ?: 'user';

                    // Redirect by role
                    if ($_SESSION['role'] === 'admin') {
                        header('Location: /dashboards/adminDashboard.php');
                    } elseif ($_SESSION['role'] === 'official') {
                        header('Location: /dashboards/officialDashboard.php');
                    } else {
                        header('Location: /dashboards/userDashboard.php');
                    }
                    exit;
                } else {
                    $error = 'Invalid password!';
                }
            } else {
                $error = 'Account not found.';
            }
            $stmt->close();
        } else {
            $error = 'Server error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Login - CommServe</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body { background:#f4f6f8; }
  .card { border:0; border-radius:14px; box-shadow:0 0.5rem 1rem rgba(0,0,0,.08); }
  .btn-dark { border-radius:999px; }
</style>
</head>

<body style="font-family: 'Lato', sans-serif; background-color: #f7f7f7;">
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/pages/landingPage.php">
      <i class="bi bi-people-fill me-2"></i>CommServe
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
  </div>
</nav>

<!-- Login Section -->
<section class="d-flex align-items-center justify-content-center vh-100">
  <div class="card p-4 shadow-sm rounded-4" style="max-width: 500px; width: 100%;">
    <h3 class="fw-bold mb-3 text-center" style="font-family: 'Montserrat', sans-serif;"><i class="bi bi-box-arrow-in-right me-2"></i>Login</h3>

    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="mb-3">
        <label class="form-label">Username or Email</label>
        <input type="text" name="identifier" class="form-control" value="<?= htmlspecialchars($identifier) ?>" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-dark w-100 rounded-pill mb-2">
        <i class="bi bi-box-arrow-in-right me-1"></i>Login
      </button>
    </form>

    <div class="d-flex justify-content-between mt-3">
      <a href="/forgot.php">Forgot Password?</a>
      <a href="/pages/Signup.php">Create Account</a>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="text-white text-center py-4 bg-dark">
  <p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>