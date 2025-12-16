<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';

// Admin/Official guard
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'official'], true)) {
  header('Location: /pages/loginPage.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'official';
$stmt_brgy = $conn->prepare("SELECT barangay, barangay_id, cityMunicipality, municipality_id, province, province_id, region, region_id FROM users WHERE id = ?");
$stmt_brgy->bind_param("i", $user_id);
$stmt_brgy->execute();
$brgy_result = $stmt_brgy->get_result();
$brgy_data = $brgy_result->fetch_assoc();
$stmt_brgy->close();
$barangay = htmlspecialchars($brgy_data['barangay'] ?? 'Barangay', ENT_QUOTES, 'UTF-8');
$user_barangay = $brgy_data['barangay'] ?? '';
$user_barangay_id = $brgy_data['barangay_id'] ?? null;
$user_municipality_id = $brgy_data['municipality_id'] ?? null;
$user_province_id = $brgy_data['province_id'] ?? null;
$user_region_id = $brgy_data['region_id'] ?? null;

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

function flash($type, $msg) {
  $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// Ensure emergency_hotlines table has scope columns
$conn->query("CREATE TABLE IF NOT EXISTS emergency_hotlines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  number VARCHAR(30) NOT NULL,
  description VARCHAR(255) NULL,
  scope_level VARCHAR(50) DEFAULT 'BARANGAY',
  target_id INT NULL,
  barangay VARCHAR(100),
  barangay_id INT NULL,
  cityMunicipality VARCHAR(100),
  municipality_id INT NULL,
  province VARCHAR(100),
  province_id INT NULL,
  region VARCHAR(100),
  region_id INT NULL,
  created_by INT NULL,
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Add missing columns if they don't exist
$columns_to_add = [
  "scope_level VARCHAR(50) DEFAULT 'BARANGAY'",
  "target_id INT",
  "barangay_id INT",
  "municipality_id INT",
  "province VARCHAR(100)",
  "province_id INT",
  "region VARCHAR(100)",
  "region_id INT",
  "created_by INT"
];

foreach ($columns_to_add as $column) {
  $col_name = explode(' ', $column)[0];
  $check = $conn->query("SHOW COLUMNS FROM emergency_hotlines LIKE '$col_name'");
  if ($check && $check->num_rows == 0) {
    $conn->query("ALTER TABLE emergency_hotlines ADD COLUMN $column");
  }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf_token'] ?? '';
  if (!hash_equals($_SESSION['csrf_token'], $token)) {
    flash('danger', 'Invalid CSRF token.');
    header('Location: /emergencyHotlines.php');
    exit;
  }

  $action = $_POST['action'] ?? '';
  
  if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '' || $number === '') {
      flash('danger', 'Name and number are required.');
      header('Location: /emergencyHotlines.php');
      exit;
    }

    // Officials can only post to their barangay
    if ($user_role === 'official') {
      $scope_level = 'BARANGAY';
      $target_id = $user_barangay_id;
      $barangay_id = $user_barangay_id;
      $municipality_id = $user_municipality_id;
      $province_id = $user_province_id;
      $region_id = $user_region_id;
      $brgy = $user_barangay;
      $city = $brgy_data['cityMunicipality'] ?? '';
      $province = $brgy_data['province'] ?? '';
      $region = $brgy_data['region'] ?? '';
    } else {
      // Admin can choose scope
      $scope_level = trim($_POST['scope_level'] ?? 'ALL');
      $target_id = (int)($_POST['target_id'] ?? 0);
      $barangay_id = (int)($_POST['barangay_id'] ?? 0) ?: null;
      $municipality_id = (int)($_POST['municipality_id'] ?? 0) ?: null;
      $province_id = (int)($_POST['province_id'] ?? 0) ?: null;
      $region_id = (int)($_POST['region_id'] ?? 0) ?: null;
      $brgy = trim($_POST['barangay'] ?? '');
      $city = trim($_POST['cityMunicipality'] ?? '');
      $province = trim($_POST['province'] ?? '');
      $region = trim($_POST['region'] ?? '');
    }

    $stmt = $conn->prepare('INSERT INTO emergency_hotlines (name, number, description, scope_level, target_id, barangay, barangay_id, cityMunicipality, municipality_id, province, province_id, region, region_id, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->bind_param('ssssissississi', $name, $number, $description, $scope_level, $target_id, $brgy, $barangay_id, $city, $municipality_id, $province, $province_id, $region, $region_id, $user_id);
    if ($stmt->execute()) {
      flash('success', 'Emergency hotline added successfully.');
    } else {
      flash('danger', 'Failed to add hotline: ' . $conn->error);
    }
    $stmt->close();
    header('Location: /emergencyHotlines.php');
    exit;
  }

  if ($action === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($id <= 0 || $name === '' || $number === '') {
      flash('danger', 'Invalid data.');
      header('Location: /emergencyHotlines.php');
      exit;
    }

    // Officials can only edit for their barangay
    if ($user_role === 'official') {
      $scope_level = 'BARANGAY';
      $target_id = $user_barangay_id;
      $barangay_id = $user_barangay_id;
      $municipality_id = $user_municipality_id;
      $province_id = $user_province_id;
      $region_id = $user_region_id;
      $brgy = $user_barangay;
      $city = $brgy_data['cityMunicipality'] ?? '';
      $province = $brgy_data['province'] ?? '';
      $region = $brgy_data['region'] ?? '';
    } else {
      // Admin can choose scope
      $scope_level = trim($_POST['scope_level'] ?? 'ALL');
      $target_id = (int)($_POST['target_id'] ?? 0);
      $barangay_id = (int)($_POST['barangay_id'] ?? 0) ?: null;
      $municipality_id = (int)($_POST['municipality_id'] ?? 0) ?: null;
      $province_id = (int)($_POST['province_id'] ?? 0) ?: null;
      $region_id = (int)($_POST['region_id'] ?? 0) ?: null;
      $brgy = trim($_POST['barangay'] ?? '');
      $city = trim($_POST['cityMunicipality'] ?? '');
      $province = trim($_POST['province'] ?? '');
      $region = trim($_POST['region'] ?? '');
    }

    $stmt = $conn->prepare('UPDATE emergency_hotlines SET name=?, number=?, description=?, scope_level=?, target_id=?, barangay=?, barangay_id=?, cityMunicipality=?, municipality_id=?, province=?, province_id=?, region=?, region_id=? WHERE id=?');
    $stmt->bind_param('ssssissississi', $name, $number, $description, $scope_level, $target_id, $brgy, $barangay_id, $city, $municipality_id, $province, $province_id, $region, $region_id, $id);
    if ($stmt->execute()) {
      flash('success', 'Emergency hotline updated successfully.');
    } else {
      flash('danger', 'Failed to update hotline: ' . $conn->error);
    }
    $stmt->close();
    header('Location: /emergencyHotlines.php');
    exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      flash('danger', 'Invalid hotline ID.');
      header('Location: /emergencyHotlines.php');
      exit;
    }
    $stmt = $conn->prepare('DELETE FROM emergency_hotlines WHERE id=?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
      flash('success', 'Emergency hotline deleted successfully.');
    } else {
      flash('danger', 'Failed to delete hotline: ' . $conn->error);
    }
    $stmt->close();
    header('Location: /emergencyHotlines.php');
    exit;
  }
}

// Fetch hotlines based on user role and scope
if ($user_role === 'admin') {
  // Admins see all hotlines
  $hotlines = $conn->query('SELECT id, name, number, description, scope_level, barangay, cityMunicipality, province, region, createdAt FROM emergency_hotlines ORDER BY name ASC');
} else {
  // Officials see:
  // 1. Hotlines with scope_level = 'ALL'
  // 2. Hotlines for their region
  // 3. Hotlines for their province
  // 4. Hotlines for their municipality
  // 5. Hotlines for their barangay
  $stmt = $conn->prepare("
    SELECT id, name, number, description, scope_level, barangay, cityMunicipality, province, region, createdAt 
    FROM emergency_hotlines 
    WHERE scope_level = 'ALL'
       OR (scope_level = 'REGION' AND region_id = ?)
       OR (scope_level = 'PROVINCE' AND province_id = ?)
       OR (scope_level = 'MUNICIPALITY' AND municipality_id = ?)
       OR (scope_level = 'BARANGAY' AND barangay_id = ?)
    ORDER BY name ASC
  ");
  $stmt->bind_param("iiii", $user_region_id, $user_province_id, $user_municipality_id, $user_barangay_id);
  $stmt->execute();
  $hotlines = $stmt->get_result();
  $stmt->close();
}

$edit_hotline = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  if ($eid > 0) {
    $st = $conn->prepare('SELECT id, name, number, description, scope_level, target_id, barangay, barangay_id, cityMunicipality, municipality_id, province, province_id, region, region_id FROM emergency_hotlines WHERE id=?');
    $st->bind_param('i', $eid);
    $st->execute();
    $res = $st->get_result();
    $edit_hotline = $res->fetch_assoc() ?: null;
    $st->close();
  }
}

// Get regions, provinces, municipalities, barangays for dropdowns (admin only)
$regions_list = [];
$provinces_list = [];
$municipalities_list = [];
$barangays_list = [];

if ($user_role === 'admin') {
  $result = $conn->query("SELECT id, name, code FROM regions ORDER BY name ASC");
  while ($r = $result->fetch_assoc()) $regions_list[] = $r;

  $result = $conn->query("SELECT id, name, region_id FROM provinces ORDER BY name ASC");
  while ($p = $result->fetch_assoc()) $provinces_list[] = $p;

  $result = $conn->query("SELECT id, name, province_id FROM municipalities ORDER BY name ASC");
  while ($m = $result->fetch_assoc()) $municipalities_list[] = $m;

  $result = $conn->query("SELECT id, name, municipality_id FROM barangays ORDER BY name ASC");
  while ($b = $result->fetch_assoc()) $barangays_list[] = $b;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Emergency Hotlines - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    body { background-color: #f8f9fa; }
    .btn-black { background-color: #000; color: #fff; }
    .btn-black:hover { background-color: #333; color: #fff; }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="<?= ($_SESSION['role'] ?? '') === 'admin' ? '/dashboards/adminDashboard.php' : '/dashboards/officialDashboard.php' ?>"><i class="bi bi-telephone-fill me-2"></i>Barangay <?= $barangay ?></a>
      <div class="d-flex">
        <a class="nav-link text-white" href="<?= ($_SESSION['role'] ?? '') === 'admin' ? '/dashboards/adminDashboard.php' : '/dashboards/officialDashboard.php' ?>"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
      </div>
    </div>
  </nav>

  <div class="container py-5">
    <h2 class="text-center mb-4"><i class="bi bi-telephone-fill me-2"></i>Manage Emergency Hotlines</h2>

    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['type']) ?>">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
      <div class="card-header bg-dark text-white">Add Emergency Hotline</div>
      <div class="card-body">
        <form method="post" action="/emergencyHotlines.php" class="row g-3" id="addForm">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="action" value="add">
          
          <div class="col-md-6">
            <label class="form-label">Name/Department <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g., Barangay Hall, Fire Station" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
            <input type="text" name="number" class="form-control" placeholder="e.g., 0912-345-6789" required>
          </div>
          <div class="col-md-12">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-control" placeholder="e.g., Emergency Response, Medical Assistance">
          </div>
          
          <?php if ($user_role === 'admin'): ?>
          <div class="col-md-12">
            <label class="form-label"><i class="bi bi-broadcast me-2"></i>Broadcast Scope</label>
            <select name="scope_level" id="add_scope_level" class="form-select" required>
              <option value="ALL">🌍 All Locations (Nationwide)</option>
              <option value="REGION">📍 Specific Region</option>
              <option value="PROVINCE">🏛️ Specific Province</option>
              <option value="MUNICIPALITY">🏙️ Specific City/Municipality</option>
              <option value="BARANGAY">🏘️ Specific Barangay</option>
            </select>
          </div>
          <?php else: ?>
          <div class="col-md-12">
            <div class="alert alert-info">
              <i class="bi bi-info-circle me-2"></i>
              <strong>Posting to:</strong> Barangay <?= htmlspecialchars($brgy_data['barangay'] ?? 'N/A') ?>, 
              <?= htmlspecialchars($brgy_data['cityMunicipality'] ?? 'N/A') ?>
            </div>
          </div>
          <?php endif; ?>
          <div id="add_scope_selector" class="col-md-12" style="display:none;">
            <div class="row g-3">
              <div class="col-md-3" id="add_region_selector" style="display:none;">
                <label class="form-label">Select Region</label>
                <select name="region_id" id="add_region_id" class="form-select">
                  <option value="">Choose Region</option>
                </select>
                <input type="hidden" name="region" id="add_region">
              </div>
              <div class="col-md-3" id="add_province_selector" style="display:none;">
                <label class="form-label">Select Province</label>
                <select name="province_id" id="add_province_id" class="form-select" disabled>
                  <option value="">Select Region first</option>
                </select>
                <input type="hidden" name="province" id="add_province">
              </div>
              <div class="col-md-3" id="add_municipality_selector" style="display:none;">
                <label class="form-label">Select Municipality/City</label>
                <select name="municipality_id" id="add_municipality_id" class="form-select" disabled>
                  <option value="">Select Province first</option>
                </select>
                <input type="hidden" name="cityMunicipality" id="add_city">
              </div>
              <div class="col-md-3" id="add_barangay_selector" style="display:none;">
                <label class="form-label">Select Barangay</label>
                <select name="barangay_id" id="add_barangay_id" class="form-select" disabled>
                  <option value="">Select Municipality first</option>
                </select>
                <input type="hidden" name="barangay" id="add_barangay">
              </div>
            </div>
          </div>
          
          <div class="col-12 text-end">
            <button type="submit" class="btn btn-black">Save Hotline</button>
          </div>
        </form>
      </div>
    </div>

    <?php if ($edit_hotline): ?>
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">Edit Emergency Hotline</div>
        <div class="card-body">
          <form method="post" action="/emergencyHotlines.php" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= (int)$edit_hotline['id'] ?>">
            
            <div class="col-md-6">
              <label class="form-label">Name/Department <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($edit_hotline['name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Contact Number <span class="text-danger">*</span></label>
              <input type="text" name="number" class="form-control" value="<?= htmlspecialchars($edit_hotline['number']) ?>" required>
            </div>
            <div class="col-md-12">
              <label class="form-label">Description</label>
              <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($edit_hotline['description'] ?? '') ?>">
            </div>
            
            <?php if ($user_role === 'admin'): ?>
            <div class="col-md-12">
              <label class="form-label"><i class="bi bi-broadcast me-2"></i>Broadcast Scope</label>
              <select name="scope_level" id="edit_scope_level" class="form-select" required>
                <option value="ALL" <?= ($edit_hotline['scope_level'] ?? 'ALL') === 'ALL' ? 'selected' : '' ?>>🌍 All Locations (Nationwide)</option>
                <option value="REGION" <?= ($edit_hotline['scope_level'] ?? '') === 'REGION' ? 'selected' : '' ?>>📍 Specific Region</option>
                <option value="PROVINCE" <?= ($edit_hotline['scope_level'] ?? '') === 'PROVINCE' ? 'selected' : '' ?>>🏛️ Specific Province</option>
                <option value="MUNICIPALITY" <?= ($edit_hotline['scope_level'] ?? '') === 'MUNICIPALITY' ? 'selected' : '' ?>>🏙️ Specific City/Municipality</option>
                <option value="BARANGAY" <?= ($edit_hotline['scope_level'] ?? '') === 'BARANGAY' ? 'selected' : '' ?>>🏘️ Specific Barangay</option>
              </select>
            </div>
            <?php else: ?>
            <div class="col-md-12">
              <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Posting to:</strong> Barangay <?= htmlspecialchars($brgy_data['barangay'] ?? 'N/A') ?>, 
                <?= htmlspecialchars($brgy_data['cityMunicipality'] ?? 'N/A') ?>
              </div>
            </div>
            <?php endif; ?>
            
            <div class="col-12 text-end">
              <a href="/emergencyHotlines.php" class="btn btn-secondary">Cancel</a>
              <button type="submit" class="btn btn-black">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="card shadow-sm">
      <div class="card-header bg-dark text-white">
        <h5 class="mb-0">All Emergency Hotlines</h5>
      </div>
      <div class="card-body">
        <?php if ($hotlines && $hotlines->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Number</th>
                  <th>Description</th>
                  <th>Barangay</th>
                  <th>Municipality/City</th>
                  <th>Added</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($hot = $hotlines->fetch_assoc()): ?>
                  <tr>
                    <td><?= (int)$hot['id'] ?></td>
                    <td><strong><?= htmlspecialchars($hot['name']) ?></strong></td>
                    <td>
                      <a href="tel:<?= htmlspecialchars($hot['number']) ?>" class="text-decoration-none">
                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($hot['number']) ?>
                      </a>
                    </td>
                    <td><?= htmlspecialchars($hot['description'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($hot['barangay']) ?></td>
                    <td><?= htmlspecialchars($hot['cityMunicipality']) ?></td>
                    <td><?= date('M d, Y', strtotime($hot['createdAt'])) ?></td>
                    <td>
                      <a class="btn btn-sm btn-black me-1" href="/emergencyHotlines.php?edit=<?= (int)$hot['id'] ?>">
                        <i class="bi bi-pencil-square"></i>
                      </a>
                      <form method="post" action="/emergencyHotlines.php" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$hot['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this hotline?')">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-muted text-center">No emergency hotlines yet. Add one above!</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <footer class="text-white text-center py-4 bg-dark mt-5">
    <p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Location data for cascading dropdowns
    const regionsData = <?= json_encode($regions_list) ?>;
    const provincesData = <?= json_encode($provinces_list) ?>;
    const municipalitiesData = <?= json_encode($municipalities_list) ?>;
    const barangaysData = <?= json_encode($barangays_list) ?>;

    // Add form scope toggle
    document.getElementById('add_scope_level')?.addEventListener('change', function() {
      const level = this.value;
      const scopeSelector = document.getElementById('add_scope_selector');
      const regionSelector = document.getElementById('add_region_selector');
      const provinceSelector = document.getElementById('add_province_selector');
      const municipalitySelector = document.getElementById('add_municipality_selector');
      const barangaySelector = document.getElementById('add_barangay_selector');

      // Hide all by default
      scopeSelector.style.display = 'none';
      regionSelector.style.display = 'none';
      provinceSelector.style.display = 'none';
      municipalitySelector.style.display = 'none';
      barangaySelector.style.display = 'none';

      // Show based on level
      if (level !== 'ALL') {
        scopeSelector.style.display = 'block';
        
        if (level === 'REGION' || level === 'PROVINCE' || level === 'MUNICIPALITY' || level === 'BARANGAY') {
          regionSelector.style.display = 'block';
          const regionSelect = document.getElementById('add_region_id');
          regionSelect.innerHTML = '<option value="">Choose Region</option>';
          regionsData.forEach(r => {
            regionSelect.innerHTML += `<option value="${r.id}" data-name="${r.name}">${r.name}</option>`;
          });
        }
        if (level === 'PROVINCE' || level === 'MUNICIPALITY' || level === 'BARANGAY') {
          provinceSelector.style.display = 'block';
        }
        if (level === 'MUNICIPALITY' || level === 'BARANGAY') {
          municipalitySelector.style.display = 'block';
        }
        if (level === 'BARANGAY') {
          barangaySelector.style.display = 'block';
        }
      }
    });

    // Add form region selection
    document.getElementById('add_region_id')?.addEventListener('change', function() {
      const regionId = this.value;
      const regionName = this.options[this.selectedIndex]?.getAttribute('data-name') || '';
      document.getElementById('add_region').value = regionName;
      
      const provinceSelect = document.getElementById('add_province_id');
      provinceSelect.innerHTML = '<option value="">Choose Province</option>';
      provinceSelect.disabled = !regionId;
      
      if (regionId) {
        provincesData.filter(p => p.region_id == regionId).forEach(p => {
          provinceSelect.innerHTML += `<option value="${p.id}" data-name="${p.name}">${p.name}</option>`;
        });
      }
      
      document.getElementById('add_municipality_id').innerHTML = '<option value="">Select Province first</option>';
      document.getElementById('add_municipality_id').disabled = true;
      document.getElementById('add_barangay_id').innerHTML = '<option value="">Select Municipality first</option>';
      document.getElementById('add_barangay_id').disabled = true;
    });

    // Add form province selection
    document.getElementById('add_province_id')?.addEventListener('change', function() {
      const provinceId = this.value;
      const provinceName = this.options[this.selectedIndex]?.getAttribute('data-name') || '';
      document.getElementById('add_province').value = provinceName;
      
      const municipalitySelect = document.getElementById('add_municipality_id');
      municipalitySelect.innerHTML = '<option value="">Choose Municipality/City</option>';
      municipalitySelect.disabled = !provinceId;
      
      if (provinceId) {
        municipalitiesData.filter(m => m.province_id == provinceId).forEach(m => {
          municipalitySelect.innerHTML += `<option value="${m.id}" data-name="${m.name}">${m.name}</option>`;
        });
      }
      
      document.getElementById('add_barangay_id').innerHTML = '<option value="">Select Municipality first</option>';
      document.getElementById('add_barangay_id').disabled = true;
    });

    // Add form municipality selection
    document.getElementById('add_municipality_id')?.addEventListener('change', function() {
      const municipalityId = this.value;
      const municipalityName = this.options[this.selectedIndex]?.getAttribute('data-name') || '';
      document.getElementById('add_city').value = municipalityName;
      
      const barangaySelect = document.getElementById('add_barangay_id');
      barangaySelect.innerHTML = '<option value="">Choose Barangay</option>';
      barangaySelect.disabled = !municipalityId;
      
      if (municipalityId) {
        barangaysData.filter(b => b.municipality_id == municipalityId).forEach(b => {
          barangaySelect.innerHTML += `<option value="${b.id}" data-name="${b.name}">${b.name}</option>`;
        });
      }
    });

    // Add form barangay selection
    document.getElementById('add_barangay_id')?.addEventListener('change', function() {
      const barangayName = this.options[this.selectedIndex]?.getAttribute('data-name') || '';
      document.getElementById('add_barangay').value = barangayName;
    });
  </script>
</body>
</html>
