<?php
// filepath: [officialDashboard.php](http://_vscodecontentref_/4)
require_once __DIR__ . '/../includes/officialCheck.php';
require_once __DIR__ . '/../userAccounts/config.php';

// Get barangay and city for navbar and header
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT barangay, cityMunicipality FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();
$barangay = htmlspecialchars($user_data['barangay'] ?? 'Barangay', ENT_QUOTES, 'UTF-8');
$cityMunicipality = htmlspecialchars($user_data['cityMunicipality'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
$username = htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Official Dashboard - CommServe</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  /* Ensure content isn't hidden behind fixed footer */
  body { padding-bottom: 80px; }
  @media (max-width: 576px) { body { padding-bottom: 100px; } }
  footer.fixed-bottom-footer { position: fixed; left: 0; bottom: 0; width: 100%; z-index: 999; }
  
</style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/dashboards/officialDashboard.php"><i class="bi bi-briefcase me-2"></i>Barangay <?= $barangay ?> Official</a>
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
            <li><a class="dropdown-item" href="/dashboards/officialDashboard.php"><i class="bi bi-briefcase me-2"></i>Official Dashboard</a></li>
            <li><a class="dropdown-item" href="/dashboards/userDashboard.php"><i class="bi bi-people me-2"></i>Resident Dashboard</a></li>
            <li><hr class="dropdown-divider"></li>
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
  <div class="card shadow-sm bg-dark text-white mb-4" style="border-radius: 2rem;">
    <div class="card-body text-center py-5">
      <h1 class="fw-bold mb-3" style="font-family: 'Montserrat', sans-serif; font-size: 2.5rem;">Welcome, <?= $username ?></h1>
      <p class="mb-0" style="font-size: 1.15rem; color: #ccc;">CommServe brings barangay services, announcements, and assistance right to your fingertips.</p>
    </div>
  </div>

  <div class="card shadow-sm bg-dark text-white mb-4" style="border-radius: 2rem;">
    <div class="card-body text-center py-2">
      <div class="row justify-content-center">
        <div class="col-md-10">
          <p class="mb-0" style="color: #fff;">Barangay: <strong><?= strtoupper($barangay) ?></strong> | Municipality/City: <strong><?= strtoupper($cityMunicipality) ?></strong></p>
        </div>
      </div>
    </div>
  </div>
  <div class="row g-4">
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-dark">
        <div class="card-body text-center">
          <i class="bi bi-megaphone-fill" style="font-size:3.5rem;color:#000;"></i>
          <h5 class="mt-3">Manage Announcements</h5>
          <p class="text-muted">Post, edit, and delete announcements</p>
          <a href="/barangayAnnouncement.php" class="btn btn-dark w-100">Manage Announcement</a>
        </div>
      </div>
    </div>
  <div class="col-md-4">
      <div class="card h-100 shadow-sm border-dark">
        <div class="card-body text-center">
          <i class="bi bi-file-earmark-text-fill" style="font-size:3.5rem;color:#000;"></i>
          <h5 class="mt-3">Review Requests</h5>
          <p class="text-muted">Process document requests</p>
          <a href="/manage-requests.php" class="btn btn-dark w-100">Review Request</a>
        </div>
      </div>
    </div>
  <div class="col-md-4">
      <div class="card h-100 shadow-sm border-dark">
        <div class="card-body text-center">
          <i class="bi bi-telephone-fill" style="font-size:3.5rem;color:#000;"></i>
          <h5 class="mt-3">Emergency Hotlines</h5>
          <p class="text-muted">Manage emergency contact numbers</p>
          <a href="/emergencyHotlines.php" class="btn btn-dark w-100">Manage Hotlines</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-dark">
        <div class="card-body text-center">
          <i class="bi bi-chat-dots-fill" style="font-size:3.5rem;color:#000;"></i>
          <h5 class="mt-3">Messages</h5>
          <p class="text-muted">Communicate with residents</p>
          <a href="/messages.php" class="btn btn-dark w-100">View Messages</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="text-white text-center py-4 bg-dark fixed-bottom-footer">
  <p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p>
  
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>