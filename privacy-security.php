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
  <title>Privacy & Security - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      padding-bottom: 80px;
    }
    .privacy-container {
      max-width: 900px;
      margin: 50px auto;
    }
    .privacy-section {
      background: #fff;
      border-radius: 15px;
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .section-icon {
      font-size: 2.5rem;
      color: #000;
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

  <!-- Privacy & Security Content -->
  <div class="container privacy-container">
    <h2 class="mb-4"><i class="bi bi-shield-lock-fill me-2"></i>Privacy & Security</h2>

    <!-- Data Privacy -->
    <div class="privacy-section">
      <div class="d-flex align-items-center mb-3">
        <i class="bi bi-file-lock-fill section-icon me-3"></i>
        <h4 class="mb-0">Data Privacy</h4>
      </div>
      <p class="text-muted">Your personal information is protected and used only for barangay services.</p>
      <ul>
        <li>We collect only necessary information for service delivery</li>
        <li>Your data is stored securely in our database</li>
        <li>We do not share your information with third parties without consent</li>
        <li>You have the right to access and correct your personal data</li>
      </ul>
    </div>

    <!-- Account Security -->
    <div class="privacy-section">
      <div class="d-flex align-items-center mb-3">
        <i class="bi bi-shield-check section-icon me-3"></i>
        <h4 class="mb-0">Account Security</h4>
      </div>
      <p class="text-muted">Keep your account secure with these best practices:</p>
      <ul>
        <li>Use a strong, unique password for your account</li>
        <li>Never share your password with anyone</li>
        <li>Log out when using shared or public computers</li>
        <li>Contact administrators immediately if you suspect unauthorized access</li>
      </ul>
      <button class="btn btn-dark" disabled>
        <i class="bi bi-key-fill me-2"></i>Change Password (Coming Soon)
      </button>
    </div>

    <!-- Session Management -->
    <div class="privacy-section">
      <div class="d-flex align-items-center mb-3">
        <i class="bi bi-clock-history section-icon me-3"></i>
        <h4 class="mb-0">Session Management</h4>
      </div>
      <p class="text-muted">Your session is managed securely:</p>
      <ul>
        <li>Sessions expire automatically after inactivity</li>
        <li>Each login generates a new secure session</li>
        <li>You can manually log out anytime</li>
      </ul>
      <a href="/logout.php" class="btn btn-danger">
        <i class="bi bi-box-arrow-right me-2"></i>Logout Now
      </a>
    </div>

    <!-- Data Rights -->
    <div class="privacy-section">
      <div class="d-flex align-items-center mb-3">
        <i class="bi bi-person-badge section-icon me-3"></i>
        <h4 class="mb-0">Your Rights</h4>
      </div>
      <p class="text-muted">Under the Data Privacy Act, you have the right to:</p>
      <ul>
        <li><strong>Access:</strong> Request a copy of your personal data</li>
        <li><strong>Correction:</strong> Update incorrect or incomplete information</li>
        <li><strong>Erasure:</strong> Request deletion of your data (subject to legal requirements)</li>
        <li><strong>Object:</strong> Refuse processing of your data for certain purposes</li>
      </ul>
      <p class="mb-0">
        <i class="bi bi-info-circle me-2"></i>
        For data privacy concerns, contact your barangay administrator.
      </p>
    </div>

    <div class="text-center mt-4">
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
