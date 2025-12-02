<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profile - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

  <style>
    body {
      background-color: #f8f9fa;
      color: #000;
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    }

    .profile-card {
      max-width: 1000px;
      margin: 50px auto;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
      background: #fff;
    }

    .profile-header {
      background: linear-gradient(to right, #000, #333);
      color: #fff;
      padding: 30px;
      text-align: center;
    }

    .profile-header h2 {
      margin: 0;
      font-weight: bold;
    }

    .profile-body {
      padding: 30px;
    }

    .profile-label {
      font-weight: bold;
      color: #333;
    }

    .btn-black {
      background-color: #000;
      color: #fff;
    }

    .btn-black:hover {
      background-color: #333;
      color: #fff;
    }

    footer {
      background-color: #000;
      color: #fff;
      padding: 20px 0;
      margin-top: 50px;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <?php
        session_start();
        require_once __DIR__ . '/userAccounts/config.php';
        $user_id = $_SESSION['user_id'] ?? 0;
        $stmt_nav = $conn->prepare("SELECT role, barangay FROM users WHERE id = ?");
        $stmt_nav->bind_param("i", $user_id);
        $stmt_nav->execute();
        $nav_user = $stmt_nav->get_result()->fetch_assoc();
        $stmt_nav->close();
        
        $barangay = htmlspecialchars($nav_user['barangay'] ?? 'Barangay', ENT_QUOTES, 'UTF-8');
        $role_display = 'CommServe';
        $dashboard_link = '/';
        $navbar_icon = 'bi-people-fill';
        if (!empty($nav_user['role'])) {
          if ($nav_user['role'] === 'admin') {
            $role_display = 'Barangay ' . $barangay . ' Admin';
            $dashboard_link = '/dashboards/adminDashboard.php';
            $navbar_icon = 'bi-shield-check';
          } elseif ($nav_user['role'] === 'official') {
            $role_display = 'Barangay ' . $barangay . ' Official';
            $dashboard_link = '/dashboards/officialDashboard.php';
            $navbar_icon = 'bi-briefcase';
          } elseif ($nav_user['role'] === 'user') {
            $role_display = 'Barangay ' . $barangay . ' Resident';
            $dashboard_link = '/dashboards/userDashboard.php';
            $navbar_icon = 'bi-house-door-fill';
          } else {
            $role_display = ucfirst($nav_user['role']);
          }
        }
      ?>
      <a class="navbar-brand fw-bold" href="<?= $dashboard_link ?>">
        <i class="bi <?= $navbar_icon ?> me-2"></i><?= $role_display ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>
  </nav>

  <?php
  $stmt = $conn->prepare("SELECT lastName, firstName, middleName, email, phoneNumber, role, sitio, barangay, cityMunicipality, province, region FROM users WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  ?>
  <div class="container">
    <div class="profile-card">
      <div class="profile-header">
        <h2>User Profile</h2>
      </div>
      <div class="profile-body">
        <div class="row g-4">
          <div class="col-md-6">
            <p><span class="profile-label">Full Name:</span> <?= htmlspecialchars($user['lastName'].' '.$user['firstName'].' '.$user['middleName']) ?></p>
            <p><span class="profile-label">Email:</span> <?= htmlspecialchars($user['email']) ?></p>
            <p><span class="profile-label">Phone:</span> <?= htmlspecialchars($user['phoneNumber']) ?></p>
            <p><span class="profile-label">Role:</span> <?= htmlspecialchars(ucfirst($user['role'])) ?></p>
          </div>
          <div class="col-md-6">
            <p><span class="profile-label">Address:</span> <?= htmlspecialchars($user['sitio']) ?></p>
            <p><span class="profile-label">Barangay:</span> <?= htmlspecialchars($user['barangay']) ?></p>
            <p><span class="profile-label">Municipality/City:</span> <?= htmlspecialchars($user['cityMunicipality']) ?></p>
            <p><span class="profile-label">Region:</span> <?= htmlspecialchars($user['region']) ?></p>
            <p><span class="profile-label">Province:</span> <?= htmlspecialchars($user['province']) ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="text-center" style="position:fixed;left:0;bottom:0;width:100%;z-index:999;background-color:#000;color:#fff;">
    <p class="mb-0">&copy; 2025 CommServe. All rights reserved.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
