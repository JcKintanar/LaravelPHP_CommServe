<?php
// filepath: [Index.php](http://_vscodecontentref_/2)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: /pages/loginPage.php');
    exit;
}

// Get user details from database
require_once __DIR__ . '/../userAccounts/config.php';
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT barangay, cityMunicipality FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Safe locals for display
$username = htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8');
$role     = $_SESSION['role'] ?? 'user';
$barangay = htmlspecialchars($user_data['barangay'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
$cityMunicipality = htmlspecialchars($user_data['cityMunicipality'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Dashboard - CommServe</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato&display=swap" rel="stylesheet">
<style>
  html, body {
    height: 100%;
  }
  body {
    display: flex;
    flex-direction: column;
  }
  .content {
    flex: 1;
  }
</style>
</head>
<body class="bg-light">
<div class="content">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/dashboards/userDashboard.php"><i class="bi bi-people-fill me-2"></i>Barangay <?= $barangay ?> Resident</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <?php
            // Fetch full user info for navbar
            $stmt = $conn->prepare("SELECT id, lastName, firstName, middleName FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $navbar_user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $navbar_id = $navbar_user['id'] ?? '';
            $navbar_last = strtoupper($navbar_user['lastName'] ?? '');
            $navbar_first = $navbar_user['firstName'] ?? '';
            $navbar_middle = $navbar_user['middleName'] ?? '';
            ?>
            <i class="bi bi-person-circle me-1"></i><?= $navbar_id ?> | <?= $navbar_last ?>, <?= $navbar_first ?> <?= $navbar_middle ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <?php if ($role === 'admin'): ?>
              <li><a class="dropdown-item" href="/dashboards/adminDashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
              <li><a class="dropdown-item" href="/dashboards/officialDashboard.php"><i class="bi bi-briefcase me-2"></i>Official Dashboard</a></li>
              <li><hr class="dropdown-divider"></li>
            <?php elseif ($role === 'official'): ?>
              <li><a class="dropdown-item" href="/dashboards/officialDashboard.php"><i class="bi bi-briefcase me-2"></i>Official Dashboard</a></li>
              <li><hr class="dropdown-divider"></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="/userProfile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
            <li><a class="dropdown-item" href="/settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
            <li><a class="dropdown-item" href="/privacy-security.php"><i class="bi bi-shield-lock me-2"></i>Privacy and Security</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<section class="container my-5">
  <div class="card shadow-sm bg-dark text-white" style="border-radius: 2rem;">
    <div class="card-body text-center py-5">
      <h1 class="fw-bold mb-3" style="font-family: 'Montserrat', sans-serif; font-size: 2.5rem;">Welcome, <?= $username ?></h1>
      <p class="mb-0" style="font-size: 1.15rem; color: #ccc;">CommServe brings barangay services, announcements, and assistance right to your fingertips.</p>
    </div>
  </div>

  <div class="card shadow-sm bg-dark text-white mt-4" style="border-radius: 2rem;">
    <div class="card-body text-center py-1">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <p class="mb-0" style="color: #fff;">Barangay: <strong><?= strtoupper($barangay) ?></strong> | Municipality/City: <strong><?= strtoupper($cityMunicipality) ?></strong></p>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mt-4">
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-dark">
        <div class="card-body text-center">
          <i class="bi bi-megaphone-fill" style="font-size:3rem;color:#000;"></i>
          <h5 class="mt-3">Announcements</h5>
          <p class="text-muted">Latest barangay news</p>
          <a href="/barangayAnnouncement.php" class="btn btn-dark w-100">View Announcements</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-dark">
        <div class="card-body text-center">
          <i class="bi bi-telephone-fill" style="font-size:3rem;color:#000;"></i>
          <h5 class="mt-3">Emergency Hotlines</h5>
          <p class="text-muted">Quick access to contacts</p>
          <a href="hotlines.php" class="btn btn-dark w-100">View Hotlines</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-dark">
        <div class="card-body text-center">
          <i class="bi bi-file-earmark-text-fill" style="font-size:3rem;color:#000;"></i>
          <h5 class="mt-3">Document Requests</h5>
          <p class="text-muted">Request certificates</p>
          <a href="/request-document.php" class="btn btn-dark w-100">Request Now</a>
        </div>
      </div>
    </div>
  </div>
</section>
</div>

<!-- Footer -->
<footer class="text-white text-center py-4 bg-dark">
  <p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>