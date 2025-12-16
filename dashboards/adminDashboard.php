<?php
// filepath: [adminDashboard.php](http://_vscodecontentref_/3)
require_once __DIR__ . '/../includes/adminCheck.php';
require_once __DIR__ . '/../userAccounts/config.php';

// Get barangay for navbar
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT barangay FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();
$barangay = htmlspecialchars($user_data['barangay'] ?? 'Barangay', ENT_QUOTES, 'UTF-8');

$total_users     = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'] ?? 0;
$total_admins    = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='admin'")->fetch_assoc()['c'] ?? 0;
$total_officials = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='official'")->fetch_assoc()['c'] ?? 0;
$total_residents = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='user'")->fetch_assoc()['c'] ?? 0;
$recent_users    = $conn->query("SELECT id, firstName, lastName, username, role, createdAt FROM users ORDER BY createdAt DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - CommServe</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  html, body { height: 100%; margin: 0; }
  body { display: flex; flex-direction: column; }
  .content-wrapper { flex: 1 0 auto; }
  footer { flex-shrink: 0; }
</style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/dashboards/adminDashboard.php"><i class="bi bi-shield-check me-2"></i>Barangay <?= $barangay ?> Admin</a>
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
            <li><a class="dropdown-item" href="/dashboards/adminDashboard.php"><i class="bi bi-shield-check me-2"></i>Admin Dashboard</a></li>
            <li><a class="dropdown-item" href="/dashboards/officialDashboard.php"><i class="bi bi-briefcase me-2"></i>Official Dashboard</a></li>
            <li><a class="dropdown-item" href="/dashboards/userDashboard.php"><i class="bi bi-house-fill me-2"></i>Resident Dashboard</a></li>
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

<div class="content-wrapper">
<section class="container my-5">
  <div class="row g-4 mb-4">
    <div class="col-md-3"><div class="card bg-dark text-white border-0"><div class="card-body"><h6>Total Users</h6><h2><?= $total_users ?></h2></div></div></div>
    <div class="col-md-3"><div class="card bg-dark text-white border-0"><div class="card-body"><h6>Admins</h6><h2><?= $total_admins ?></h2></div></div></div>
    <div class="col-md-3"><div class="card bg-dark text-white border-0"><div class="card-body"><h6>Officials</h6><h2><?= $total_officials ?></h2></div></div></div>
    <div class="col-md-3"><div class="card bg-dark text-white border-0"><div class="card-body"><h6>Residents</h6><h2><?= $total_residents ?></h2></div></div></div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-dark text-white"><h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Users</h5></div>
    <div class="card-body">
      <?php if ($recent_users && $recent_users->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Role</th><th>Registered</th></tr></thead>
            <tbody>
            <?php while ($u = $recent_users->fetch_assoc()): ?>
              <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['firstName'].' '.$u['lastName']) ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><span class="badge bg-dark"><?= strtoupper($u['role']) ?></span></td>
                <td><?= date('M d, Y', strtotime($u['createdAt'])) ?></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted mb-0">No recent users.</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="row g-4 mt-4 justify-content-center">
    <div class="col-md-2"><a href="/userManagement.php" class="btn btn-dark w-100"><i class="bi bi-people-fill me-1"></i>Manage Users</a></div>
    <div class="col-md-3"><a href="/barangayAnnouncement.php" class="btn btn-dark w-100"><i class="bi bi-megaphone-fill me-1"></i>Manage Announcements</a></div>
    <div class="col-md-2"><a href="/dashboards/adminHotlines.php" class="btn btn-dark w-100"><i class="bi bi-telephone-fill me-1"></i>Manage Hotlines</a></div>
    <div class="col-md-3"><a href="/manage-requests.php" class="btn btn-dark w-100"><i class="bi bi-file-earmark-text-fill me-1"></i>Manage Requests</a></div>
    <div class="col-md-2"><a href="/messages.php" class="btn btn-dark w-100"><i class="bi bi-chat-dots-fill me-1"></i>Messages</a></div>
  </div>

  <div class="row g-4 mt-3 justify-content-center">
    <div class="col-md-3"><a href="/manage-locations.php" class="btn btn-outline-dark w-100"><i class="bi bi-geo-alt-fill me-1"></i>Manage Locations</a></div>
  </div>
</section>
</div>

<!-- Footer -->
<footer class="text-white text-center py-4 bg-dark mt-5">
  <p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>