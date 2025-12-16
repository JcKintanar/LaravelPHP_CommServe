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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
  $firstName = trim($_POST['firstName'] ?? '');
  $lastName = trim($_POST['lastName'] ?? '');
  $middleName = trim($_POST['middleName'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phoneNumber = trim($_POST['phoneNumber'] ?? '');
  $dateOfBirth = trim($_POST['dateOfBirth'] ?? '');
  $civilStatus = trim($_POST['civilStatus'] ?? '');
  $yearResidency = !empty($_POST['yearResidency']) ? (int)$_POST['yearResidency'] : null;

  // Validate required fields
  if (empty($firstName) || empty($lastName) || empty($email) || empty($phoneNumber)) {
    flash('danger', 'First name, last name, email, and phone number are required.');
    header('Location: /account-settings.php');
    exit;
  }

  // Validate phone number (must be exactly 11 digits)
  if (!preg_match('/^\d{11}$/', $phoneNumber)) {
    flash('danger', 'Mobile number must be exactly 11 digits.');
    header('Location: /account-settings.php');
    exit;
  }

  // Check if email is already used by another user
  $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
  $check_email->bind_param('si', $email, $user_id);
  $check_email->execute();
  $check_email->store_result();
  
  if ($check_email->num_rows > 0) {
    flash('danger', 'Email is already used by another account.');
    $check_email->close();
    header('Location: /account-settings.php');
    exit;
  }
  $check_email->close();

  // Check if phone number is already used by another user
  $check_phone = $conn->prepare("SELECT id FROM users WHERE phoneNumber = ? AND id != ?");
  $check_phone->bind_param('si', $phoneNumber, $user_id);
  $check_phone->execute();
  $check_phone->store_result();
  
  if ($check_phone->num_rows > 0) {
    flash('danger', 'Phone number is already used by another account.');
    $check_phone->close();
    header('Location: /account-settings.php');
    exit;
  }
  $check_phone->close();

  // Update user information
  $stmt = $conn->prepare("UPDATE users SET firstName=?, lastName=?, middleName=?, email=?, phoneNumber=?, dateOfBirth=?, civilStatus=?, yearResidency=? WHERE id=?");
  $stmt->bind_param('sssssssii', $firstName, $lastName, $middleName, $email, $phoneNumber, $dateOfBirth, $civilStatus, $yearResidency, $user_id);

  if ($stmt->execute()) {
    flash('success', 'Account information updated successfully!');
  } else {
    flash('danger', 'Failed to update account information: ' . $conn->error);
  }
  $stmt->close();
  header('Location: /account-settings.php');
  exit;
}

// Get flash messages
$flash_type = $_SESSION['flash_type'] ?? '';
$flash_message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_type'], $_SESSION['flash_message']);

// Get user data
$stmt = $conn->prepare("SELECT firstName, lastName, middleName, username, email, phoneNumber, dateOfBirth, civilStatus, yearResidency, role, barangay FROM users WHERE id = ?");
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
  <title>Account Settings - CommServe</title>
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
      <i class="bi bi-person-gear text-dark me-3" style="font-size: 2.5rem;"></i>
      <h2 class="mb-0">Account Settings</h2>
    </div>

    <?php if (!empty($flash_message)): ?>
      <div class="alert alert-<?= $flash_type ?> alert-dismissible fade show" role="alert">
        <i class="bi <?= $flash_type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
        <?= htmlspecialchars($flash_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="card settings-card">
      <div class="card-body p-4">
        <h5 class="card-title mb-4">Personal Information</h5>
        
        <form method="POST" action="/account-settings.php">
          <div class="row g-3">
            <div class="col-md-4">
              <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="lastName" name="lastName" value="<?= htmlspecialchars($user['lastName']) ?>" required>
            </div>
            <div class="col-md-4">
              <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="firstName" name="firstName" value="<?= htmlspecialchars($user['firstName']) ?>" required>
            </div>
            <div class="col-md-4">
              <label for="middleName" class="form-label">Middle Name</label>
              <input type="text" class="form-control" id="middleName" name="middleName" value="<?= htmlspecialchars($user['middleName'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label for="username" class="form-label">Username</label>
              <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($user['username']) ?>" disabled>
              <small class="text-muted">Username cannot be changed</small>
            </div>
            <div class="col-md-6">
              <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="col-md-6">
              <label for="phoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="phoneNumber" name="phoneNumber" value="<?= htmlspecialchars($user['phoneNumber']) ?>" placeholder="09123456789" maxlength="11" required>
              <small class="text-muted">Must be exactly 11 digits</small>
            </div>
            <div class="col-md-6">
              <label for="dateOfBirth" class="form-label">Date of Birth</label>
              <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth" value="<?= htmlspecialchars($user['dateOfBirth'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label for="civilStatus" class="form-label">Civil Status</label>
              <select class="form-select" id="civilStatus" name="civilStatus">
                <option value="Single" <?= ($user['civilStatus'] ?? '') === 'Single' ? 'selected' : '' ?>>Single</option>
                <option value="Married" <?= ($user['civilStatus'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                <option value="Widowed" <?= ($user['civilStatus'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                <option value="Divorced" <?= ($user['civilStatus'] ?? '') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                <option value="Separated" <?= ($user['civilStatus'] ?? '') === 'Separated' ? 'selected' : '' ?>>Separated</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="yearResidency" class="form-label">Year Started Residing</label>
              <input type="number" class="form-control" id="yearResidency" name="yearResidency" min="1900" max="<?= date('Y') ?>" value="<?= htmlspecialchars($user['yearResidency'] ?? '') ?>" placeholder="e.g., 2015">
            </div>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <a href="/settings.php" class="btn btn-outline-secondary">
              <i class="bi bi-x-circle me-2"></i>Cancel
            </a>
            <button type="submit" name="update_account" class="btn btn-dark">
              <i class="bi bi-save me-2"></i>Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card settings-card mt-4">
      <div class="card-body p-4">
        <h5 class="card-title mb-3">Account Information</h5>
        <div class="row">
          <div class="col-md-6">
            <p class="text-muted mb-1">Account Type</p>
            <p class="fw-bold">
              <?php
              switch($role) {
                case 'admin': echo '<span class="badge bg-danger">Administrator</span>'; break;
                case 'official': echo '<span class="badge bg-warning text-dark">Official</span>'; break;
                default: echo '<span class="badge bg-primary">Resident</span>';
              }
              ?>
            </p>
          </div>
          <div class="col-md-6">
            <p class="text-muted mb-1">Barangay</p>
            <p class="fw-bold"><?= htmlspecialchars($user['barangay'] ?? 'Not Set') ?></p>
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
</body>
</html>
