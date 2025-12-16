<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';

// Guard - redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: /pages/loginPage.php');
  exit;
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $conn->prepare("SELECT id, lastName, firstName, middleName, email, phoneNumber, dateOfBirth, civilStatus, yearResidency, role, sitio, barangay, cityMunicipality, province, region, username, createdAt FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
  header('Location: /logout.php');
  exit;
}

// Determine dashboard link and navbar display based on role
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

$fullName = htmlspecialchars(trim($user['firstName'] . ' ' . ($user['middleName'] ?? '') . ' ' . $user['lastName']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      padding-bottom: 80px;
    }
    .profile-card {
      max-width: 900px;
      margin: 50px auto;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
      background: #fff;
    }
    .profile-header {
      background: linear-gradient(135deg, #000, #333);
      color: #fff;
      padding: 40px 30px;
      text-align: center;
    }
    .profile-avatar {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      font-size: 3rem;
      color: #000;
    }
    .profile-body {
      padding: 40px;
    }
    .info-label {
      font-weight: 600;
      color: #666;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .info-value {
      font-size: 1.1rem;
      color: #000;
      margin-bottom: 1.5rem;
    }
    .btn-black {
      background-color: #000;
      color: #fff;
      border: none;
    }
    .btn-black:hover {
      background-color: #333;
      color: #fff;
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

  <!-- Profile Content -->
  <div class="container">
    <div class="profile-card">
      <div class="profile-header">
        <div class="profile-avatar">
          <i class="bi bi-person-fill"></i>
        </div>
        <h2 class="mb-2"><?= $fullName ?></h2>
        <p class="mb-0"><span class="badge bg-light text-dark"><?= strtoupper($user['role']) ?></span></p>
      </div>
      
      <div class="profile-body">
        <h4 class="mb-4"><i class="bi bi-person-badge me-2"></i>Personal Information</h4>
        <div class="row g-4 mb-5">
          <div class="col-md-6">
            <div class="info-label">User ID</div>
            <div class="info-value"><?= htmlspecialchars($user['id']) ?></div>
          </div>
          <div class="col-md-6">
            <div class="info-label">Username</div>
            <div class="info-value"><?= htmlspecialchars($user['username']) ?></div>
          </div>
          <div class="col-md-6">
            <div class="info-label">Email Address</div>
            <div class="info-value">
              <i class="bi bi-envelope me-2"></i><?= htmlspecialchars($user['email']) ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="info-label">Phone Number</div>
            <div class="info-value">
              <i class="bi bi-telephone me-2"></i><?= htmlspecialchars($user['phoneNumber']) ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="info-label">Date of Birth</div>
            <div class="info-value">
              <i class="bi bi-calendar-heart me-2"></i><?= !empty($user['dateOfBirth']) ? date('F d, Y', strtotime($user['dateOfBirth'])) : 'Not set' ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="info-label">Civil Status</div>
            <div class="info-value">
              <i class="bi bi-person-vcard me-2"></i><?= !empty($user['civilStatus']) ? htmlspecialchars($user['civilStatus']) : 'Not set' ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="info-label">Year Started Residing</div>
            <div class="info-value">
              <i class="bi bi-house-heart me-2"></i><?php 
                if (!empty($user['yearResidency'])) {
                  $years = date('Y') - (int)$user['yearResidency'];
                  echo htmlspecialchars($user['yearResidency']) . ' (' . $years . ' years)';
                } else {
                  echo 'Not set';
                }
              ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="info-label">Account Created</div>
            <div class="info-value">
              <i class="bi bi-calendar-check me-2"></i><?= date('F d, Y', strtotime($user['createdAt'])) ?>
            </div>
          </div>
        </div>

        <h4 class="mb-4"><i class="bi bi-geo-alt me-2"></i>Address Information</h4>
        <div class="row g-4">
          <div class="col-md-6">
            <div class="info-label">Sitio/Purok</div>
            <div class="info-value"><?= htmlspecialchars($user['sitio']) ?></div>
          </div>
          <div class="col-md-6">
            <div class="info-label">Barangay</div>
            <div class="info-value"><?= htmlspecialchars($user['barangay']) ?></div>
          </div>
          <div class="col-md-6">
            <div class="info-label">Municipality/City</div>
            <div class="info-value"><?= htmlspecialchars($user['cityMunicipality']) ?></div>
          </div>
          <div class="col-md-6">
            <div class="info-label">Province</div>
            <div class="info-value"><?= htmlspecialchars($user['province']) ?></div>
          </div>
          <div class="col-md-12">
            <div class="info-label">Region</div>
            <div class="info-value"><?= htmlspecialchars($user['region']) ?></div>
          </div>
        </div>

        <div class="text-center mt-5">
          <a href="<?= $dashboard_link ?>" class="btn btn-black btn-lg px-5">
            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
          </a>
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