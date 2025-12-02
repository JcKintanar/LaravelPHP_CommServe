<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';

// Guard - redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: /pages/loginPage.php');
  exit;
}

$user_id = $_SESSION['user_id'];

// Get user role and barangay for navbar
$stmt = $conn->prepare("SELECT role, barangay FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$nav_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$role = $nav_user['role'] ?? 'user';
$barangay = htmlspecialchars($nav_user['barangay'] ?? 'Barangay', ENT_QUOTES, 'UTF-8');

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
  <title>Settings - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      padding-bottom: 80px;
    }
    .settings-container {
      max-width: 800px;
      margin: 50px auto;
    }
    .setting-card {
      border-radius: 15px;
      transition: transform 0.2s;
    }
    .setting-card:hover {
      transform: translateY(-5px);
    }
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
            <a class="nav-link" href="<?= $dashboard_link ?>">
              <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Settings Content -->
  <div class="container settings-container">
    <h2 class="mb-4"><i class="bi bi-gear-fill me-2"></i>Settings</h2>

    <div class="row g-4">
      <!-- Account Settings -->
      <div class="col-md-6">
        <div class="card setting-card shadow-sm h-100">
          <div class="card-body text-center p-4">
            <i class="bi bi-person-gear text-dark" style="font-size: 3rem;"></i>
            <h5 class="mt-3">Account Settings</h5>
            <p class="text-muted">Update your personal information and account details</p>
            <button class="btn btn-dark" disabled>Coming Soon</button>
          </div>
        </div>
      </div>

      <!-- Notification Settings -->
      <div class="col-md-6">
        <div class="card setting-card shadow-sm h-100">
          <div class="card-body text-center p-4">
            <i class="bi bi-bell-fill text-dark" style="font-size: 3rem;"></i>
            <h5 class="mt-3">Notifications</h5>
            <p class="text-muted">Manage your notification preferences</p>
            <button class="btn btn-dark" disabled>Coming Soon</button>
          </div>
        </div>
      </div>

      <!-- Privacy Settings -->
      <div class="col-md-6">
        <div class="card setting-card shadow-sm h-100">
          <div class="card-body text-center p-4">
            <i class="bi bi-shield-lock-fill text-dark" style="font-size: 3rem;"></i>
            <h5 class="mt-3">Privacy</h5>
            <p class="text-muted">Control who can see your information</p>
            <a href="/privacy-security.php" class="btn btn-dark">Manage Privacy</a>
          </div>
        </div>
      </div>

      <!-- Security Settings -->
      <div class="col-md-6">
        <div class="card setting-card shadow-sm h-100">
          <div class="card-body text-center p-4">
            <i class="bi bi-key-fill text-dark" style="font-size: 3rem;"></i>
            <h5 class="mt-3">Security</h5>
            <p class="text-muted">Change password and security settings</p>
            <button class="btn btn-dark" disabled>Coming Soon</button>
          </div>
        </div>
      </div>

      <!-- Language Settings -->
      <div class="col-md-6">
        <div class="card setting-card shadow-sm h-100">
          <div class="card-body text-center p-4">
            <i class="bi bi-translate text-dark" style="font-size: 3rem;"></i>
            <h5 class="mt-3">Language</h5>
            <p class="text-muted">Choose your preferred language</p>
            <button class="btn btn-dark" disabled>Coming Soon</button>
          </div>
        </div>
      </div>

      <!-- Theme Settings -->
      <div class="col-md-6">
        <div class="card setting-card shadow-sm h-100">
          <div class="card-body text-center p-4">
            <i class="bi bi-palette-fill text-dark" style="font-size: 3rem;"></i>
            <h5 class="mt-3">Appearance</h5>
            <p class="text-muted">Customize the look and feel</p>
            <button class="btn btn-dark" disabled>Coming Soon</button>
          </div>
        </div>
      </div>
    </div>

    <div class="text-center mt-5">
      <a href="<?= $dashboard_link ?>" class="btn btn-outline-dark btn-lg">
        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
      </a>
    </div>
  </div>

  <!-- Footer -->
  <footer class="text-white text-center py-4 bg-dark">
    <p class="mb-0">&copy; 2025 CommServe. All rights reserved.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
