<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';

// Admin guard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: /pages/loginPage.php');
  exit;
}

$user_id = $_SESSION['user_id'];

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

function flash($type, $msg) {
  $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf_token'] ?? '';
  if (!hash_equals($_SESSION['csrf_token'], $token)) {
    flash('danger', 'Invalid CSRF token.');
    header('Location: /manage-locations.php');
    exit;
  }

  $action = $_POST['action'] ?? '';
  
  // REGION ACTIONS
  if ($action === 'add_region') {
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    if ($name === '') {
      flash('danger', 'Region name is required.');
    } else {
      $stmt = $conn->prepare('INSERT INTO regions (name, code) VALUES (?, ?)');
      $stmt->bind_param('ss', $name, $code);
      if ($stmt->execute()) {
        flash('success', 'Region added successfully.');
      } else {
        flash('danger', 'Failed to add region: ' . $conn->error);
      }
      $stmt->close();
    }
    header('Location: /manage-locations.php');
    exit;
  }

  if ($action === 'edit_region') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    if ($id <= 0 || $name === '') {
      flash('danger', 'Invalid data.');
    } else {
      $stmt = $conn->prepare('UPDATE regions SET name=?, code=? WHERE id=?');
      $stmt->bind_param('ssi', $name, $code, $id);
      if ($stmt->execute()) {
        flash('success', 'Region updated successfully.');
      } else {
        flash('danger', 'Failed to update region: ' . $conn->error);
      }
      $stmt->close();
    }
    header('Location: /manage-locations.php');
    exit;
  }

  if ($action === 'delete_region') {
    $id = (int)($_POST['id'] ?? 0);
    $check = $conn->prepare('SELECT COUNT(*) as count FROM provinces WHERE region_id=?');
    $check->bind_param('i', $id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    $check->close();
    
    if ($result['count'] > 0) {
      flash('warning', 'Cannot delete region with existing provinces.');
    } else {
      $stmt = $conn->prepare('DELETE FROM regions WHERE id=?');
      $stmt->bind_param('i', $id);
      if ($stmt->execute()) {
        flash('success', 'Region deleted successfully.');
      } else {
        flash('danger', 'Failed to delete region: ' . $conn->error);
      }
      $stmt->close();
    }
    header('Location: /manage-locations.php');
    exit;
  }

  // PROVINCE ACTIONS
  if ($action === 'add_province') {
    $name = trim($_POST['name'] ?? '');
    $region_id = (int)($_POST['region_id'] ?? 0);
    if ($name === '' || $region_id <= 0) {
      flash('danger', 'Province name and region are required.');
    } else {
      $stmt = $conn->prepare('INSERT INTO provinces (name, region_id) VALUES (?, ?)');
      $stmt->bind_param('si', $name, $region_id);
      if ($stmt->execute()) {
        flash('success', 'Province added successfully.');
      } else {
        flash('danger', 'Failed to add province: ' . $conn->error);
      }
      $stmt->close();
    }
    header('Location: /manage-locations.php');
    exit;
  }

  if ($action === 'edit_province') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $region_id = (int)($_POST['region_id'] ?? 0);
    if ($id <= 0 || $name === '' || $region_id <= 0) {
      flash('danger', 'Invalid data.');
    } else {
      $stmt = $conn->prepare('UPDATE provinces SET name=?, region_id=? WHERE id=?');
      $stmt->bind_param('sii', $name, $region_id, $id);
      if ($stmt->execute()) {
        flash('success', 'Province updated successfully.');
      } else {
        flash('danger', 'Failed to update province: ' . $conn->error);
      }
      $stmt->close();
    }
    header('Location: /manage-locations.php');
    exit;
  }

  if ($action === 'delete_province') {
    $id = (int)($_POST['id'] ?? 0);
    $check = $conn->prepare('SELECT COUNT(*) as count FROM municipalities WHERE province_id=?');
    $check->bind_param('i', $id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    $check->close();
    
    if ($result['count'] > 0) {
      flash('warning', 'Cannot delete province with existing municipalities.');
    } else {
      $stmt = $conn->prepare('DELETE FROM provinces WHERE id=?');
      $stmt->bind_param('i', $id);
      if ($stmt->execute()) {
        flash('success', 'Province deleted successfully.');
      } else {
        flash('danger', 'Failed to delete province: ' . $conn->error);
      }
      $stmt->close();
    }
    header('Location: /manage-locations.php');
    exit;
  }

  // MUNICIPALITY ACTIONS
  if ($action === 'add_municipality') {
    $name = trim($_POST['name'] ?? '');
    $province_id = (int)($_POST['province_id'] ?? 0);
    $region_id = (int)($_POST['region_id'] ?? 0);
    
    if ($name === '' || $province_id <= 0 || $region_id <= 0) {
      flash('danger', 'Municipality name, province, and region are required.');
    } else {
      // Update province text field from province_id
      $province_stmt = $conn->prepare('SELECT name FROM provinces WHERE id = ?');
      $province_stmt->bind_param('i', $province_id);
      $province_stmt->execute();
      $province_result = $province_stmt->get_result()->fetch_assoc();
      $province_name = $province_result['name'] ?? '';
      $province_stmt->close();
      
      $stmt = $conn->prepare('INSERT INTO municipalities (name, province, province_id, region_id) VALUES (?, ?, ?, ?)');
      $stmt->bind_param('ssii', $name, $province_name, $province_id, $region_id);
      if ($stmt->execute()) {
        flash('success', 'Municipality added successfully.');
      } else {
        flash('danger', 'Failed to add municipality: ' . $conn->error);
      }
      $stmt->close();
    }
    header('Location: /manage-locations.php');
    exit;
  }

  if ($action === 'edit_municipality') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $province_id = (int)($_POST['province_id'] ?? 0);
    $region_id = (int)($_POST['region_id'] ?? 0);
    
    if ($id <= 0 || $name === '' || $province_id <= 0 || $region_id <= 0) {
      flash('danger', 'All fields are required.');
    } else {
      // Update province text field from province_id
      $province_stmt = $conn->prepare('SELECT name FROM provinces WHERE id = ?');
      $province_stmt->bind_param('i', $province_id);
      $province_stmt->execute();
      $province_result = $province_stmt->get_result()->fetch_assoc();
      $province_name = $province_result['name'] ?? '';
      $province_stmt->close();
      
      $stmt = $conn->prepare('UPDATE municipalities SET name=?, province=?, province_id=?, region_id=? WHERE id=?');
      $stmt->bind_param('ssiii', $name, $province_name, $province_id, $region_id, $id);
      if ($stmt->execute()) {
        flash('success', 'Municipality updated successfully.');
      } else {
        flash('danger', 'Failed to update municipality: ' . $conn->error);
      }
      $stmt->close();
    }
    header('Location: /manage-locations.php');
    exit;
  }

  if ($action === 'delete_municipality') {
    $id = (int)($_POST['id'] ?? 0);
    $check = $conn->prepare('SELECT COUNT(*) as count FROM barangays WHERE municipality_id=?');
    $check->bind_param('i', $id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    $check->close();
    
    if ($result['count'] > 0) {
      flash('warning', 'Cannot delete municipality with existing barangays.');
    } else {
      $stmt = $conn->prepare('DELETE FROM municipalities WHERE id=?');
      $stmt->bind_param('i', $id);
      if ($stmt->execute()) {
        flash('success', 'Municipality deleted successfully.');
      } else {
        flash('danger', 'Failed to delete municipality: ' . $conn->error);
      }
      $stmt->close();
    }
    header('Location: /manage-locations.php');
    exit;
  }

  // BARANGAY ACTIONS
  if ($action === 'add_barangay') {
    $name = trim($_POST['name'] ?? '');
    $municipality_id = (int)($_POST['municipality_id'] ?? 0);
    if ($name === '' || $municipality_id <= 0) {
      flash('danger', 'Barangay name and municipality are required.');
    } else {
      $stmt = $conn->prepare('INSERT INTO barangays (name, municipality_id) VALUES (?, ?)');
      $stmt->bind_param('si', $name, $municipality_id);
      if ($stmt->execute()) {
        flash('success', 'Barangay added successfully.');
      } else {
        flash('danger', 'Failed to add barangay: ' . $conn->error);
      }
      $stmt->close();
    }
    header('Location: /manage-locations.php');
    exit;
  }

  if ($action === 'edit_barangay') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $municipality_id = (int)($_POST['municipality_id'] ?? 0);
    if ($id <= 0 || $name === '' || $municipality_id <= 0) {
      flash('danger', 'Invalid data.');
    } else {
      $stmt = $conn->prepare('UPDATE barangays SET name=?, municipality_id=? WHERE id=?');
      $stmt->bind_param('sii', $name, $municipality_id, $id);
      if ($stmt->execute()) {
        flash('success', 'Barangay updated successfully.');
      } else {
        flash('danger', 'Failed to update barangay: ' . $conn->error);
      }
      $stmt->close();
    }
    header('Location: /manage-locations.php');
    exit;
  }

  if ($action === 'delete_barangay') {
    $id = (int)($_POST['id'] ?? 0);
    $check = $conn->prepare('SELECT COUNT(*) as count FROM users WHERE barangay_id=?');
    $check->bind_param('i', $id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    $check->close();
    
    if ($result['count'] > 0) {
      flash('warning', 'Cannot delete barangay with registered users.');
    } else {
      $stmt = $conn->prepare('DELETE FROM barangays WHERE id=?');
      $stmt->bind_param('i', $id);
      if ($stmt->execute()) {
        flash('success', 'Barangay deleted successfully.');
      } else {
        flash('danger', 'Failed to delete barangay: ' . $conn->error);
      }
      $stmt->close();
    }
    header('Location: /manage-locations.php');
    exit;
  }
}

// Fetch data with hierarchy
$regions = $conn->query("
  SELECT r.*, 
         (SELECT COUNT(*) FROM provinces WHERE region_id = r.id) as province_count
  FROM regions r 
  ORDER BY r.name ASC
");

$provinces = $conn->query("
  SELECT p.*, r.name as region_name,
         (SELECT COUNT(*) FROM municipalities WHERE province_id = p.id) as municipality_count
  FROM provinces p
  JOIN regions r ON p.region_id = r.id
  ORDER BY r.name ASC, p.name ASC
");

$municipalities = $conn->query("
  SELECT m.*, p.name as province_name, r.name as region_name,
         (SELECT COUNT(*) FROM barangays WHERE municipality_id = m.id) as barangay_count,
         (SELECT COUNT(*) FROM users WHERE municipality_id = m.id) as user_count
  FROM municipalities m
  LEFT JOIN provinces p ON m.province_id = p.id
  LEFT JOIN regions r ON m.region_id = r.id
  ORDER BY r.name ASC, p.name ASC, m.name ASC
");

$barangays = $conn->query("
  SELECT b.*, m.name as municipality_name, p.name as province_name, r.name as region_name,
         (SELECT COUNT(*) FROM users WHERE barangay_id = b.id) as user_count
  FROM barangays b
  JOIN municipalities m ON b.municipality_id = m.id
  LEFT JOIN provinces p ON m.province_id = p.id
  LEFT JOIN regions r ON m.region_id = r.id
  ORDER BY r.name ASC, p.name ASC, m.name ASC, b.name ASC
");

// Fetch lists for dropdowns
$regions_list = [];
$result = $conn->query("SELECT id, name FROM regions ORDER BY name ASC");
while ($r = $result->fetch_assoc()) $regions_list[] = $r;

$provinces_list = [];
$result = $conn->query("SELECT id, name, region_id FROM provinces ORDER BY name ASC");
while ($p = $result->fetch_assoc()) $provinces_list[] = $p;

$municipalities_list = [];
$result = $conn->query("SELECT id, name, province_id FROM municipalities ORDER BY name ASC");
while ($m = $result->fetch_assoc()) $municipalities_list[] = $m;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Locations - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    body { background-color: #f8f9fa; }
    .hierarchy-card { margin-bottom: 1.5rem; }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="/dashboards/adminDashboard.php">
        <i class="bi bi-geo-alt-fill me-2"></i>Manage Locations
      </a>
      <div class="d-flex">
        <a class="nav-link text-white" href="/dashboards/adminDashboard.php">
          <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['type']) ?> alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Regions Section -->
    <div class="card shadow-sm hierarchy-card">
      <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-globe me-2"></i>Regions (Default)</h5>
      </div>
      <div class="card-body">
        <input type="text" id="regionSearch" class="form-control mb-3" placeholder="Search regions...">
        <?php if ($regions && $regions->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Region Name</th>
                  <th>Code</th>
                  <th>Provinces</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="regionTableBody">
                <?php while ($r = $regions->fetch_assoc()): ?>
                  <tr>
                    <td><?= $r['id'] ?></td>
                    <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                    <td><?= htmlspecialchars($r['code']) ?></td>
                    <td><span class="badge bg-info"><?= $r['province_count'] ?></span></td>
                    <td>
                      <span class="text-muted">Default Region</span>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-muted text-center">No regions found.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Provinces Section -->
    <div class="card shadow-sm hierarchy-card">
      <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-map me-2"></i>Provinces</h5>
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addProvinceModal">
          <i class="bi bi-plus-circle me-1"></i>Add Province
        </button>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <input type="text" id="provinceSearch" class="form-control" placeholder="Search provinces...">
          </div>
          <div class="col-md-6">
            <select id="provinceRegionFilter" class="form-select">
              <option value="">All Regions</option>
              <?php foreach ($regions_list as $r): ?>
                <option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <?php if ($provinces && $provinces->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Province</th>
                  <th>Region</th>
                  <th>Municipalities</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="provinceTableBody">
                <?php while ($p = $provinces->fetch_assoc()): ?>
                  <tr data-region="<?= htmlspecialchars($p['region_name']) ?>">
                    <td><?= $p['id'] ?></td>
                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                    <td><?= htmlspecialchars($p['region_name']) ?></td>
                    <td><span class="badge bg-info"><?= $p['municipality_count'] ?></span></td>
                    <td>
                      <button class="btn btn-sm btn-dark" onclick='editProvince(<?= $p['id'] ?>, "<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>", <?= $p['region_id'] ?>)'>
                        <i class="bi bi-pencil"></i> Edit
                      </button>
                      <button class="btn btn-sm btn-danger" onclick='deleteProvince(<?= $p['id'] ?>, "<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>")'>
                        <i class="bi bi-trash"></i> Delete
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-muted text-center">No provinces found.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Municipalities Section -->
    <div class="card shadow-sm hierarchy-card">
      <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-building me-2"></i>Cities / Municipalities</h5>
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addMunicipalityModal">
          <i class="bi bi-plus-circle me-1"></i>Add Municipality
        </button>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-4">
            <input type="text" id="municipalitySearch" class="form-control" placeholder="Search municipalities...">
          </div>
          <div class="col-md-4">
            <select id="municipalityRegionFilter" class="form-select">
              <option value="">All Regions</option>
              <?php foreach ($regions_list as $r): ?>
                <option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <select id="municipalityProvinceFilter" class="form-select">
              <option value="">All Provinces</option>
              <?php foreach ($provinces_list as $p): ?>
                <option value="<?= htmlspecialchars($p['name']) ?>" data-region-id="<?= $p['region_id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <?php if ($municipalities && $municipalities->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Municipality/City</th>
                  <th>Province</th>
                  <th>Region</th>
                  <th>Barangays</th>
                  <th>Users</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="municipalityTableBody">
                <?php while ($m = $municipalities->fetch_assoc()): ?>
                  <tr data-region="<?= htmlspecialchars($m['region_name'] ?? '') ?>" data-province="<?= htmlspecialchars($m['province_name'] ?? '') ?>">
                    <td><?= $m['id'] ?></td>
                    <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                    <td><?= htmlspecialchars($m['province_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($m['region_name'] ?? 'N/A') ?></td>
                    <td><span class="badge bg-info"><?= $m['barangay_count'] ?></span></td>
                    <td><span class="badge bg-secondary"><?= $m['user_count'] ?></span></td>
                    <td>
                      <button class="btn btn-sm btn-dark" onclick='editMunicipality(<?= $m['id'] ?>, "<?= htmlspecialchars($m['name'], ENT_QUOTES) ?>", <?= $m['province_id'] ?? 0 ?>, <?= $m['region_id'] ?? 0 ?>)'>
                        <i class="bi bi-pencil"></i> Edit
                      </button>
                      <button class="btn btn-sm btn-danger" onclick='deleteMunicipality(<?= $m['id'] ?>, "<?= htmlspecialchars($m['name'], ENT_QUOTES) ?>")'>
                        <i class="bi bi-trash"></i> Delete
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-muted text-center">No municipalities found.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Barangays Section -->
    <div class="card shadow-sm hierarchy-card">
      <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-geo-fill me-2"></i>Barangays</h5>
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addBarangayModal">
          <i class="bi bi-plus-circle me-1"></i>Add Barangay
        </button>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-3">
            <input type="text" id="barangaySearch" class="form-control" placeholder="Search barangays...">
          </div>
          <div class="col-md-3">
            <select id="barangayRegionFilter" class="form-select">
              <option value="">All Regions</option>
              <?php foreach ($regions_list as $r): ?>
                <option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <select id="barangayProvinceFilter" class="form-select">
              <option value="">All Provinces</option>
              <?php foreach ($provinces_list as $p): ?>
                <option value="<?= htmlspecialchars($p['name']) ?>" data-region-id="<?= $p['region_id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <select id="barangayMunicipalityFilter" class="form-select">
              <option value="">All Municipalities</option>
              <?php foreach ($municipalities_list as $m): ?>
                <option value="<?= htmlspecialchars($m['name']) ?>" data-province-id="<?= $m['province_id'] ?? 0 ?>"><?= htmlspecialchars($m['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <?php if ($barangays && $barangays->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Barangay</th>
                  <th>Municipality</th>
                  <th>Province</th>
                  <th>Region</th>
                  <th>Users</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="barangayTableBody">
                <?php while ($b = $barangays->fetch_assoc()): ?>
                  <tr data-region="<?= htmlspecialchars($b['region_name'] ?? '') ?>" data-province="<?= htmlspecialchars($b['province_name'] ?? '') ?>" data-municipality="<?= htmlspecialchars($b['municipality_name']) ?>">
                    <td><?= $b['id'] ?></td>
                    <td><strong><?= htmlspecialchars($b['name']) ?></strong></td>
                    <td><?= htmlspecialchars($b['municipality_name']) ?></td>
                    <td><?= htmlspecialchars($b['province_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($b['region_name'] ?? 'N/A') ?></td>
                    <td><span class="badge bg-secondary"><?= $b['user_count'] ?></span></td>
                    <td>
                      <button class="btn btn-sm btn-dark" onclick='editBarangay(<?= $b['id'] ?>, "<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>", <?= $b['municipality_id'] ?>)'>
                        <i class="bi bi-pencil"></i> Edit
                      </button>
                      <button class="btn btn-sm btn-danger" onclick='deleteBarangay(<?= $b['id'] ?>, "<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>")'>
                        <i class="bi bi-trash"></i> Delete
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-muted text-center">No barangays found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Modals for Add/Edit operations -->
  <!-- Add Region Modal -->
  <div class="modal fade" id="addRegionModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Region</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post" action="/manage-locations.php">
          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="add_region">
            <div class="mb-3">
              <label class="form-label">Region Name</label>
              <input type="text" name="name" class="form-control" placeholder="e.g., Region VII - Central Visayas" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Code</label>
              <input type="text" name="code" class="form-control" placeholder="e.g., VII">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">Add Region</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Region Modal -->
  <div class="modal fade" id="editRegionModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Region</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post" action="/manage-locations.php">
          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="edit_region">
            <input type="hidden" name="id" id="editRegionId">
            <div class="mb-3">
              <label class="form-label">Region Name</label>
              <input type="text" name="name" id="editRegionName" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Code</label>
              <input type="text" name="code" id="editRegionCode" class="form-control">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Add Province Modal -->
  <div class="modal fade" id="addProvinceModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Province</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post" action="/manage-locations.php">
          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="add_province">
            <div class="mb-3">
              <label class="form-label">Province Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Region</label>
              <select name="region_id" class="form-select" required>
                <option value="">Select Region</option>
                <?php foreach ($regions_list as $r): ?>
                  <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">Add Province</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Province Modal -->
  <div class="modal fade" id="editProvinceModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Province</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post" action="/manage-locations.php">
          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="edit_province">
            <input type="hidden" name="id" id="editProvinceId">
            <div class="mb-3">
              <label class="form-label">Province Name</label>
              <input type="text" name="name" id="editProvinceName" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Region</label>
              <select name="region_id" id="editProvinceRegionId" class="form-select" required>
                <option value="">Select Region</option>
                <?php foreach ($regions_list as $r): ?>
                  <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Add Municipality Modal -->
  <div class="modal fade" id="addMunicipalityModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Municipality/City</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post" action="/manage-locations.php">
          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="add_municipality">
            <div class="mb-3">
              <label class="form-label">Municipality/City Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Region</label>
              <select name="region_id" id="addMunRegion" class="form-select" onchange="filterProvincesByRegion('addMunRegion', 'addMunProvince')" required>
                <option value="">Select Region</option>
                <?php foreach ($regions_list as $r): ?>
                  <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Province</label>
              <select name="province_id" id="addMunProvince" class="form-select" required disabled>
                <option value="">Select Region first</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">Add Municipality</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Municipality Modal -->
  <div class="modal fade" id="editMunicipalityModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Municipality/City</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post" action="/manage-locations.php">
          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="edit_municipality">
            <input type="hidden" name="id" id="editMunicipalityId">
            <div class="mb-3">
              <label class="form-label">Municipality/City Name</label>
              <input type="text" name="name" id="editMunicipalityName" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Region</label>
              <select name="region_id" id="editMunRegion" class="form-select" onchange="filterProvincesByRegion('editMunRegion', 'editMunProvince')" required>
                <option value="">Select Region</option>
                <?php foreach ($regions_list as $r): ?>
                  <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Province</label>
              <select name="province_id" id="editMunProvince" class="form-select" required>
                <option value="">Select Province</option>
                <?php foreach ($provinces_list as $p): ?>
                  <option value="<?= $p['id'] ?>" data-region="<?= $p['region_id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Add Barangay Modal -->
  <div class="modal fade" id="addBarangayModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Barangay</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post" action="/manage-locations.php">
          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="add_barangay">
            <div class="mb-3">
              <label class="form-label">Barangay Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Municipality/City</label>
              <select name="municipality_id" class="form-select" required>
                <option value="">Select Municipality</option>
                <?php foreach ($municipalities_list as $m): ?>
                  <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">Add Barangay</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Barangay Modal -->
  <div class="modal fade" id="editBarangayModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Barangay</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post" action="/manage-locations.php">
          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="edit_barangay">
            <input type="hidden" name="id" id="editBarangayId">
            <div class="mb-3">
              <label class="form-label">Barangay Name</label>
              <input type="text" name="name" id="editBarangayName" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Municipality/City</label>
              <select name="municipality_id" id="editBarangayMunicipalityId" class="form-select" required>
                <option value="">Select Municipality</option>
                <?php foreach ($municipalities_list as $m): ?>
                  <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Hidden delete forms -->
  <form id="deleteRegionForm" method="post" action="/manage-locations.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="delete_region">
    <input type="hidden" name="id" id="deleteRegionId">
  </form>

  <form id="deleteProvinceForm" method="post" action="/manage-locations.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="delete_province">
    <input type="hidden" name="id" id="deleteProvinceId">
  </form>

  <form id="deleteMunicipalityForm" method="post" action="/manage-locations.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="delete_municipality">
    <input type="hidden" name="id" id="deleteMunicipalityId">
  </form>

  <form id="deleteBarangayForm" method="post" action="/manage-locations.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="delete_barangay">
    <input type="hidden" name="id" id="deleteBarangayId">
  </form>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const provincesData = <?= json_encode($provinces_list) ?>;

    // Region search filter (simple search only)
    document.getElementById('regionSearch')?.addEventListener('input', function(e) {
      const search = e.target.value.toLowerCase();
      const rows = document.querySelectorAll('#regionTableBody tr');
      rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
      });
    });

    // Province filters
    const provinceSearch = document.getElementById('provinceSearch');
    const provinceRegionFilter = document.getElementById('provinceRegionFilter');

    function filterProvinces() {
      const search = provinceSearch?.value.toLowerCase() || '';
      const selectedRegion = provinceRegionFilter?.value.toLowerCase() || '';
      const rows = document.querySelectorAll('#provinceTableBody tr');
      
      rows.forEach(row => {
        const region = row.dataset.region?.toLowerCase() || '';
        const text = row.textContent.toLowerCase();
        
        const matchesSearch = text.includes(search);
        const matchesRegion = !selectedRegion || region === selectedRegion;
        
        row.style.display = (matchesSearch && matchesRegion) ? '' : 'none';
      });
    }

    provinceSearch?.addEventListener('input', filterProvinces);
    provinceRegionFilter?.addEventListener('change', filterProvinces);

    // Municipality filters
    const municipalitySearch = document.getElementById('municipalitySearch');
    const municipalityRegionFilter = document.getElementById('municipalityRegionFilter');
    const municipalityProvinceFilter = document.getElementById('municipalityProvinceFilter');

    function filterMunicipalities() {
      const search = municipalitySearch?.value.toLowerCase() || '';
      const selectedRegion = municipalityRegionFilter?.value.toLowerCase() || '';
      const selectedProvince = municipalityProvinceFilter?.value.toLowerCase() || '';
      const rows = document.querySelectorAll('#municipalityTableBody tr');
      
      rows.forEach(row => {
        const region = row.dataset.region?.toLowerCase() || '';
        const province = row.dataset.province?.toLowerCase() || '';
        const text = row.textContent.toLowerCase();
        
        const matchesSearch = text.includes(search);
        const matchesRegion = !selectedRegion || region === selectedRegion;
        const matchesProvince = !selectedProvince || province === selectedProvince;
        
        row.style.display = (matchesSearch && matchesRegion && matchesProvince) ? '' : 'none';
      });
    }

    municipalitySearch?.addEventListener('input', filterMunicipalities);
    municipalityRegionFilter?.addEventListener('change', filterMunicipalities);
    municipalityProvinceFilter?.addEventListener('change', filterMunicipalities);

    // Barangay filters
    const barangaySearch = document.getElementById('barangaySearch');
    const barangayRegionFilter = document.getElementById('barangayRegionFilter');
    const barangayProvinceFilter = document.getElementById('barangayProvinceFilter');
    const barangayMunicipalityFilter = document.getElementById('barangayMunicipalityFilter');

    function filterBarangays() {
      const search = barangaySearch?.value.toLowerCase() || '';
      const selectedRegion = barangayRegionFilter?.value.toLowerCase() || '';
      const selectedProvince = barangayProvinceFilter?.value.toLowerCase() || '';
      const selectedMunicipality = barangayMunicipalityFilter?.value.toLowerCase() || '';
      const rows = document.querySelectorAll('#barangayTableBody tr');
      
      rows.forEach(row => {
        const region = row.dataset.region?.toLowerCase() || '';
        const province = row.dataset.province?.toLowerCase() || '';
        const municipality = row.dataset.municipality?.toLowerCase() || '';
        const text = row.textContent.toLowerCase();
        
        const matchesSearch = text.includes(search);
        const matchesRegion = !selectedRegion || region === selectedRegion;
        const matchesProvince = !selectedProvince || province === selectedProvince;
        const matchesMunicipality = !selectedMunicipality || municipality === selectedMunicipality;
        
        row.style.display = (matchesSearch && matchesRegion && matchesProvince && matchesMunicipality) ? '' : 'none';
      });
    }

    barangaySearch?.addEventListener('input', filterBarangays);
    barangayRegionFilter?.addEventListener('change', filterBarangays);
    barangayProvinceFilter?.addEventListener('change', filterBarangays);
    barangayMunicipalityFilter?.addEventListener('change', filterBarangays);

    // Filter provinces by region
    function filterProvincesByRegion(regionSelectId, provinceSelectId) {
      const regionId = document.getElementById(regionSelectId).value;
      const provinceSelect = document.getElementById(provinceSelectId);
      
      provinceSelect.innerHTML = '<option value="">Select Province</option>';
      
      if (regionId) {
        const filtered = provincesData.filter(p => p.region_id == regionId);
        filtered.forEach(p => {
          provinceSelect.innerHTML += `<option value="${p.id}">${p.name}</option>`;
        });
        provinceSelect.disabled = false;
      } else {
        provinceSelect.disabled = true;
      }
    }

    // Edit functions
    function editRegion(id, name, code) {
      document.getElementById('editRegionId').value = id;
      document.getElementById('editRegionName').value = name;
      document.getElementById('editRegionCode').value = code;
      new bootstrap.Modal(document.getElementById('editRegionModal')).show();
    }

    function editProvince(id, name, regionId) {
      document.getElementById('editProvinceId').value = id;
      document.getElementById('editProvinceName').value = name;
      document.getElementById('editProvinceRegionId').value = regionId;
      new bootstrap.Modal(document.getElementById('editProvinceModal')).show();
    }

    function editMunicipality(id, name, provinceId, regionId) {
      document.getElementById('editMunicipalityId').value = id;
      document.getElementById('editMunicipalityName').value = name;
      document.getElementById('editMunRegion').value = regionId;
      filterProvincesByRegion('editMunRegion', 'editMunProvince');
      setTimeout(() => {
        document.getElementById('editMunProvince').value = provinceId;
      }, 50);
      new bootstrap.Modal(document.getElementById('editMunicipalityModal')).show();
    }

    function editBarangay(id, name, municipalityId) {
      document.getElementById('editBarangayId').value = id;
      document.getElementById('editBarangayName').value = name;
      document.getElementById('editBarangayMunicipalityId').value = municipalityId;
      new bootstrap.Modal(document.getElementById('editBarangayModal')).show();
    }

    // Delete functions
    function deleteRegion(id, name) {
      if (confirm(`Delete ${name}? This will fail if provinces exist.`)) {
        document.getElementById('deleteRegionId').value = id;
        document.getElementById('deleteRegionForm').submit();
      }
    }

    function deleteProvince(id, name) {
      if (confirm(`Delete ${name}? This will fail if municipalities exist.`)) {
        document.getElementById('deleteProvinceId').value = id;
        document.getElementById('deleteProvinceForm').submit();
      }
    }

    function deleteMunicipality(id, name) {
      if (confirm(`Delete ${name}? This will fail if barangays exist.`)) {
        document.getElementById('deleteMunicipalityId').value = id;
        document.getElementById('deleteMunicipalityForm').submit();
      }
    }

    function deleteBarangay(id, name) {
      if (confirm(`Delete ${name}? This will fail if users exist.`)) {
        document.getElementById('deleteBarangayId').value = id;
        document.getElementById('deleteBarangayForm').submit();
      }
    }
  </script>

  <!-- Footer -->
  <footer class="bg-dark text-white text-center py-3 mt-5">
    <div class="container">
      <p class="mb-0">&copy; <?php echo date('Y'); ?> Barangay Management System. All rights reserved.</p>
    </div>
  </footer>
</body>
</html>
