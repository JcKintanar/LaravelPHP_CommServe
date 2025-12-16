<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';

// Guard - redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: /pages/loginPage.php');
  exit;
}

$user_id = $_SESSION['user_id'];

// Flash message helper
function flash($type, $message) {
  $_SESSION['flash_type'] = $type;
  $_SESSION['flash_message'] = $message;
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
  $current_password = $_POST['current_password'] ?? '';
  $new_password = $_POST['new_password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';

  // Validate inputs
  if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    flash('danger', 'All password fields are required.');
    header('Location: /security-settings.php');
    exit;
  }

  // Check if new passwords match
  if ($new_password !== $confirm_password) {
    flash('danger', 'New password and confirmation do not match.');
    header('Location: /security-settings.php');
    exit;
  }

  // Validate password strength (minimum 6 characters)
  if (strlen($new_password) < 6) {
    flash('danger', 'New password must be at least 6 characters long.');
    header('Location: /security-settings.php');
    exit;
  }

  // Get current password from database
  $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();
  $stmt->close();

  // Verify current password
  if (!password_verify($current_password, $user['password'])) {
    flash('danger', 'Current password is incorrect.');
    header('Location: /security-settings.php');
    exit;
  }

  // Hash new password
  $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

  // Update password
  $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
  $update_stmt->bind_param("si", $new_password_hashed, $user_id);

  if ($update_stmt->execute()) {
    flash('success', 'Password changed successfully!');
  } else {
    flash('danger', 'Failed to change password: ' . $conn->error);
  }
  $update_stmt->close();
  header('Location: /security-settings.php');
  exit;
}

// Get flash messages
$flash_type = $_SESSION['flash_type'] ?? '';
$flash_message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_type'], $_SESSION['flash_message']);

// Get user data
$stmt = $conn->prepare("SELECT username, email, role, barangay, createdAt FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$role = $user['role'] ?? 'user';
$barangay = htmlspecialchars($user['barangay'] ?? 'Barangay', ENT_QUOTES, 'UTF-8');

switch ($role) {
  case 'admin':
    $role_display = 'Barangay ' . $barangay . ' Admin';
    $dashboard_link = '/dashboards/adminDashboard.php';
    $navbar_icon = 'bi-shield-check';
    break;
  case 'official':
    $role_display = 'Barangay ' . $barangay . ' Official';
    $dashboard_link = '/dashboards/officialDashboard.php';
    $navbar_icon = 'bi-briefcase';
    break;
  default:
    $role_display = 'Barangay ' . $barangay;
    $dashboard_link = '/dashboards/userDashboard.php';
    $navbar_icon = 'bi-house-fill';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Privacy & Security Settings - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      padding-bottom: 80px;
    }
    .settings-container {
      max-width: 900px;
      margin: 50px auto;
    }
    .settings-card {
      border-radius: 15px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .password-strength {
      height: 5px;
      border-radius: 3px;
      transition: all 0.3s;
    }
    .strength-weak { background-color: #dc3545; width: 33%; }
    .strength-medium { background-color: #ffc107; width: 66%; }
    .strength-strong { background-color: #28a745; width: 100%; }
    footer {
      position: fixed;
      left: 0;
      bottom: 0;
      width: 100%;
      z-index: 999;
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="<?= $dashboard_link ?>">
        <i class="bi <?= $navbar_icon ?> me-2"></i><?= $role_display ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="/settings.php">
              <i class="bi bi-arrow-left me-1"></i>Back to Settings
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Settings Content -->
  <div class="container settings-container">
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-shield-lock-fill text-dark me-3" style="font-size: 2.5rem;"></i>
      <h2 class="mb-0">Privacy & Security Settings</h2>
    </div>

    <?php if (!empty($flash_message)): ?>
      <div class="alert alert-<?= $flash_type ?> alert-dismissible fade show" role="alert">
        <i class="bi <?= $flash_type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
        <?= htmlspecialchars($flash_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Change Password -->
    <div class="card settings-card mb-4">
      <div class="card-body p-4">
        <h5 class="card-title mb-4">
          <i class="bi bi-shield-lock me-2"></i>Change Password
        </h5>
        
        <form method="POST" action="/security-settings.php" id="passwordForm">
          <div class="mb-3">
            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="current_password" name="current_password" required>
              <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                <i class="bi bi-eye" id="current_password_icon"></i>
              </button>
            </div>
          </div>

          <div class="mb-3">
            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-key"></i></span>
              <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                <i class="bi bi-eye" id="new_password_icon"></i>
              </button>
            </div>
            <small class="text-muted">Password must be at least 6 characters long</small>
            <div class="password-strength mt-2" id="strengthBar"></div>
            <small id="strengthText" class="text-muted"></small>
          </div>

          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                <i class="bi bi-eye" id="confirm_password_icon"></i>
              </button>
            </div>
            <small id="matchText" class="text-muted"></small>
          </div>

          <div class="d-flex justify-content-between">
            <a href="/settings.php" class="btn btn-outline-secondary">
              <i class="bi bi-x-circle me-2"></i>Cancel
            </a>
            <button type="submit" name="change_password" class="btn btn-dark">
              <i class="bi bi-shield-check me-2"></i>Change Password
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Account Security Info -->
    <div class="card settings-card mb-4">
      <div class="card-body p-4">
        <h5 class="card-title mb-4">
          <i class="bi bi-info-circle me-2"></i>Account Security Information
        </h5>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <p class="text-muted mb-1">Username</p>
            <p class="fw-bold"><i class="bi bi-person-badge me-2"></i><?= htmlspecialchars($user['username']) ?></p>
          </div>
          <div class="col-md-6 mb-3">
            <p class="text-muted mb-1">Email</p>
            <p class="fw-bold"><i class="bi bi-envelope me-2"></i><?= htmlspecialchars($user['email']) ?></p>
          </div>
          <div class="col-md-6 mb-3">
            <p class="text-muted mb-1">Account Created</p>
            <p class="fw-bold"><i class="bi bi-calendar me-2"></i><?= date('F j, Y', strtotime($user['createdAt'])) ?></p>
          </div>
          <div class="col-md-6 mb-3">
            <p class="text-muted mb-1">Account Type</p>
            <p class="fw-bold">
              <i class="bi bi-shield-fill me-2"></i>
              <?php
              switch($role) {
                case 'admin': echo '<span class="badge bg-danger">Administrator</span>'; break;
                case 'official': echo '<span class="badge bg-warning text-dark">Official</span>'; break;
                default: echo '<span class="badge bg-primary">Resident</span>';
              }
              ?>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Privacy & Security Tips -->
    <div class="card settings-card">
      <div class="card-body p-4">
        <h5 class="card-title mb-3">
          <i class="bi bi-lightbulb me-2"></i>Privacy & Security Tips
        </h5>
        <div class="row">
          <div class="col-md-6">
            <h6 class="text-primary mb-3"><i class="bi bi-shield-check me-2"></i>Security</h6>
            <ul class="list-unstyled mb-3">
              <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Use a strong password with a mix of letters, numbers, and symbols</li>
              <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Change your password regularly</li>
              <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Never share your password with anyone</li>
              <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Don't use the same password for multiple accounts</li>
            </ul>
          </div>
          <div class="col-md-6">
            <h6 class="text-info mb-3"><i class="bi bi-eye-slash me-2"></i>Privacy</h6>
            <ul class="list-unstyled mb-3">
              <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Review who can see your information</li>
              <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Be cautious about sharing personal details</li>
              <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Regularly check your account activity</li>
              <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Log out when using shared or public computers</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="text-white text-center py-4 bg-dark">
    <p class="mb-0">&copy; 2025 CommServe. All rights reserved.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle password visibility
    function togglePassword(fieldId) {
      const field = document.getElementById(fieldId);
      const icon = document.getElementById(fieldId + '_icon');
      
      if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    }

    // Password strength checker
    document.getElementById('new_password').addEventListener('input', function() {
      const password = this.value;
      const strengthBar = document.getElementById('strengthBar');
      const strengthText = document.getElementById('strengthText');
      
      let strength = 0;
      if (password.length >= 6) strength++;
      if (password.length >= 10) strength++;
      if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
      if (/\d/.test(password)) strength++;
      if (/[^a-zA-Z0-9]/.test(password)) strength++;
      
      strengthBar.className = 'password-strength';
      
      if (strength <= 2) {
        strengthBar.classList.add('strength-weak');
        strengthText.textContent = 'Weak password';
        strengthText.style.color = '#dc3545';
      } else if (strength <= 3) {
        strengthBar.classList.add('strength-medium');
        strengthText.textContent = 'Medium strength';
        strengthText.style.color = '#ffc107';
      } else {
        strengthBar.classList.add('strength-strong');
        strengthText.textContent = 'Strong password';
        strengthText.style.color = '#28a745';
      }
    });

    // Password match checker
    document.getElementById('confirm_password').addEventListener('input', function() {
      const newPassword = document.getElementById('new_password').value;
      const confirmPassword = this.value;
      const matchText = document.getElementById('matchText');
      
      if (confirmPassword === '') {
        matchText.textContent = '';
        return;
      }
      
      if (newPassword === confirmPassword) {
        matchText.textContent = '✓ Passwords match';
        matchText.style.color = '#28a745';
      } else {
        matchText.textContent = '✗ Passwords do not match';
        matchText.style.color = '#dc3545';
      }
    });
  </script>
</body>
</html>
