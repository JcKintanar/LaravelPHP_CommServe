<?php
/**
 * Example: Secure Announcements Page with Location-Based Access Control
 * Demonstrates how to use the location middleware to restrict data access
 */
session_start();
require_once __DIR__ . '/userAccounts/config.php';
require_once __DIR__ . '/includes/locationMiddleware.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
  header('Location: /pages/loginPage.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Validate user's location access
$location_check = validateLocationAccess($conn, $user_id, $user_role);

if (!$location_check['authorized']) {
  die('Access Denied: ' . $location_check['message']);
}

$user_location = $location_check['user_location'];
$user_barangay_id = $user_location['barangay_id'];

// Build location-filtered query
$where_clause = getLocationWhereClause($user_role, $user_barangay_id, 'a');

// Fetch announcements based on user's location access
$query = "
  SELECT a.*, b.name as barangay_name, m.name as municipality_name
  FROM announcements a
  LEFT JOIN barangays b ON a.barangay_id = b.id
  LEFT JOIN municipalities m ON b.municipality_id = m.id
  WHERE $where_clause
  ORDER BY a.createdAt DESC
";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Secure Announcements - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
      <span class="navbar-brand">
        <i class="bi bi-shield-lock-fill me-2"></i>Secure Announcements
      </span>
      <span class="text-white">
        Role: <span class="badge bg-light text-dark"><?= ucfirst($user_role) ?></span>
        <?php if ($user_role !== 'admin'): ?>
          | Barangay: <span class="badge bg-info"><?= htmlspecialchars($user_location['barangay_name']) ?></span>
        <?php endif; ?>
      </span>
    </div>
  </nav>

  <div class="container my-5">
    <div class="alert alert-info">
      <h5><i class="bi bi-info-circle me-2"></i>Location-Based Access Control Demo</h5>
      <p class="mb-1"><strong>Your Access Level:</strong></p>
      <ul class="mb-0">
        <?php if ($user_role === 'admin'): ?>
          <li>Admin: Can view announcements from ALL barangays</li>
        <?php elseif ($user_role === 'official'): ?>
          <li>Official: Can only view/manage announcements for <strong><?= htmlspecialchars($user_location['barangay_name']) ?></strong></li>
        <?php else: ?>
          <li>Resident: Can only view announcements for <strong><?= htmlspecialchars($user_location['barangay_name']) ?></strong></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-megaphone-fill me-2"></i>Announcements</h5>
      </div>
      <div class="card-body">
        <?php if ($result && $result->num_rows > 0): ?>
          <div class="row g-3">
            <?php while ($ann = $result->fetch_assoc()): ?>
              <div class="col-12">
                <div class="card">
                  <div class="card-body">
                    <h6 class="card-title fw-bold"><?= htmlspecialchars($ann['title']) ?></h6>
                    <p class="card-text"><?= htmlspecialchars($ann['content']) ?></p>
                    <small class="text-muted">
                      <i class="bi bi-geo-alt-fill"></i> 
                      <?= htmlspecialchars($ann['barangay_name']) ?>, 
                      <?= htmlspecialchars($ann['municipality_name']) ?>
                      | <?= date('M d, Y', strtotime($ann['createdAt'])) ?>
                    </small>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <p class="text-muted text-center py-4">No announcements available for your location.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="mt-4">
      <a href="/dashboards/<?= $user_role === 'admin' ? 'adminDashboard' : ($user_role === 'official' ? 'officialDashboard' : 'userDashboard') ?>.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
      </a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
