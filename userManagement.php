<?php
// Pure PHP version (no JavaScript). Server-side CRUD with PRG and CSRF protection.
session_start();
require_once 'userAccounts/config.php';

// Admin guard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header('Location: /pages/loginPage.php');
  exit;
}

// Get barangay for navbar
$user_id = $_SESSION['user_id'];
$stmt_brgy = $conn->prepare("SELECT barangay FROM users WHERE id = ?");
$stmt_brgy->bind_param("i", $user_id);
$stmt_brgy->execute();
$brgy_result = $stmt_brgy->get_result();
$brgy_data = $brgy_result->fetch_assoc();
$stmt_brgy->close();
$barangay = htmlspecialchars($brgy_data['barangay'] ?? 'Barangay', ENT_QUOTES, 'UTF-8');

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Flash helper
function flash($type, $msg) {
  $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// Validate role
function normalize_role($role) {
  $allowed = ['user','official','admin'];
  return in_array($role, $allowed, true) ? $role : 'user';
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf_token'] ?? '';
  if (!hash_equals($_SESSION['csrf_token'], $token)) {
    flash('danger', 'Invalid CSRF token.');
    header('Location: /UserManagement.php');
    exit;
  }

  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName  = trim($_POST['lastName'] ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $barangay  = trim($_POST['barangay'] ?? '');
    $city      = trim($_POST['city'] ?? '');
    $region    = trim($_POST['region'] ?? '');
    $province  = trim($_POST['province'] ?? '');
    $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null;
    $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : null;
    $municipality_id = isset($_POST['municipality_id']) ? (int)$_POST['municipality_id'] : null;
    $barangay_id = isset($_POST['barangay_id']) ? (int)$_POST['barangay_id'] : null;
    $role      = normalize_role(trim($_POST['role'] ?? 'user'));

    if ($firstName === '' || $lastName === '' || $username === '' || $email === '') {
      flash('danger', 'First name, Last name, Username, and Email are required.');
      header('Location: /UserManagement.php');
      exit;
    }

    // Ensure unique username
    $chk = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $chk->bind_param('s', $username);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
      flash('danger', 'Username already exists.');
      header('Location: /UserManagement.php');
      exit;
    }
    $chk->close();

    if ($password === '') {
      $password = '123';
    }
    $password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (firstName, lastName, middleName, username, email, phoneNumber, barangay, barangay_id, cityMunicipality, municipality_id, region, region_id, province, province_id, role, password) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->bind_param('sssssssisissisis', $firstName, $lastName, $middleName, $username, $email, $phoneNumber, $barangay, $barangay_id, $city, $municipality_id, $region, $region_id, $province, $province_id, $role, $password);
    if ($stmt->execute()) {
      flash('success', 'User added successfully. Default password:123');
    } else {
      flash('danger', 'Failed to add user: ' . $conn->error);
    }
    $stmt->close();
    header('Location: /UserManagement.php');
    exit;
  }

  if ($action === 'edit') {
    $id        = (int)($_POST['id'] ?? 0);
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName  = trim($_POST['lastName'] ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $barangay  = trim($_POST['barangay'] ?? '');
    $city      = trim($_POST['city'] ?? '');
    $region    = trim($_POST['region'] ?? '');
    $province  = trim($_POST['province'] ?? '');
    $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null;
    $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : null;
    $municipality_id = isset($_POST['municipality_id']) ? (int)$_POST['municipality_id'] : null;
    $barangay_id = isset($_POST['barangay_id']) ? (int)$_POST['barangay_id'] : null;
    $role      = normalize_role(trim($_POST['role'] ?? 'user'));
    $password  = trim($_POST['password'] ?? '');

    if ($id <= 0) {
      flash('danger', 'Invalid user ID.');
      header('Location: /UserManagement.php');
      exit;
    }

    // Ensure username unique (excluding current)
    $chk = $conn->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
    $chk->bind_param('si', $username, $id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
      flash('danger', 'Username already in use by another account.');
      header('Location: /UserManagement.php?edit=' . $id);
      exit;
    }
    $chk->close();

    if ($password !== '') {
      $password_hashed = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare('UPDATE users SET firstName=?, lastName=?, middleName=?, username=?, email=?, phoneNumber=?, barangay=?, barangay_id=?, cityMunicipality=?, municipality_id=?, region=?, region_id=?, province=?, province_id=?, role=?, password=? WHERE id=?');
      $stmt->bind_param('sssssssisissisisi', $firstName, $lastName, $middleName, $username, $email, $phoneNumber, $barangay, $barangay_id, $city, $municipality_id, $region, $region_id, $province, $province_id, $role, $password_hashed, $id);
    } else {
      $stmt = $conn->prepare('UPDATE users SET firstName=?, lastName=?, middleName=?, username=?, email=?, phoneNumber=?, barangay=?, barangay_id=?, cityMunicipality=?, municipality_id=?, region=?, region_id=?, province=?, province_id=?, role=? WHERE id=?');
      $stmt->bind_param('sssssssisississi', $firstName, $lastName, $middleName, $username, $email, $phoneNumber, $barangay, $barangay_id, $city, $municipality_id, $region, $region_id, $province, $province_id, $role, $id);
    }
    if ($stmt->execute()) {
      flash('success', 'User updated successfully.');
    } else {
      flash('danger', 'Failed to update user: ' . $conn->error);
    }
    $stmt->close();
    header('Location: /UserManagement.php');
    exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      flash('danger', 'Invalid user ID.');
      header('Location: /UserManagement.php');
      exit;
    }
    if ($id === (int)$_SESSION['user_id']) {
      flash('danger', 'You cannot delete your own account.');
      header('Location: /UserManagement.php');
      exit;
    }
    $stmt = $conn->prepare('DELETE FROM users WHERE id=?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
      flash('success', 'User deleted successfully.');
    } else {
      flash('danger', 'Failed to delete user: ' . $conn->error);
    }
    $stmt->close();
    header('Location: /UserManagement.php');
    exit;
  }
}

// Load users for table
$users_result = $conn->query('SELECT id, firstName, lastName, middleName, username, email, phoneNumber, barangay, barangay_id, cityMunicipality, municipality_id, region, region_id, province, province_id, role, createdAt FROM users ORDER BY createdAt DESC');

// If editing, fetch user
$edit_user = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  if ($eid > 0) {
    $st = $conn->prepare('SELECT id, firstName, lastName, middleName, username, email, phoneNumber, barangay, barangay_id, cityMunicipality, municipality_id, region, region_id, province, province_id, role FROM users WHERE id=?');
    $st->bind_param('i', $eid);
    $st->execute();
    $res = $st->get_result();
    $edit_user = $res->fetch_assoc() ?: null;
    $st->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    body { background-color: #f8f9fa; color: #000; }
    .table-dark th { background-color: #000 !important; color: #fff !important; }
    .btn-black { background-color: #000; color: #fff; }
    .btn-black:hover { background-color: #333; color: #fff; }
    .badge { background-color: #000; }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="/dashboards/adminDashboard.php"><i class="bi bi-shield-check me-2"></i>Barangay <?= $barangay ?> Admin</a>
      <div class="d-flex">
        <a class="nav-link text-white" href="/dashboards/adminDashboard.php"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
      </div>
    </div>
  </nav>

  <div class="container py-5">
    <h2 class="text-center mb-4">User Management - CommServe</h2>

    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['type']) ?>">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Add User Form -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-dark text-white">Add User</div>
      <div class="card-body">
        <form method="post" action="/UserManagement.php" class="row g-3">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="action" value="add">
          <div class="col-md-4">
            <label class="form-label">Last Name</label>
            <input type="text" name="lastName" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">First Name</label>
            <input type="text" name="firstName" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Middle Name</label>
            <input type="text" name="middleName" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Password</label>
            <input type="text" name="password" class="form-control" placeholder="Leave blank for default (123)">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phoneNumber" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Region</label>
            <select name="region_id" id="add_region_id" class="form-select" required>
              <option value="">Select Region</option>
            </select>
            <input type="hidden" name="region" id="add_region">
          </div>
          <div class="col-md-6">
            <label class="form-label">Province</label>
            <select name="province_id" id="add_province_id" class="form-select" required disabled>
              <option value="">Select Region first</option>
            </select>
            <input type="hidden" name="province" id="add_province">
          </div>
          <div class="col-md-6">
            <label class="form-label">Municipality/City</label>
            <select name="municipality_id" id="add_municipality_id" class="form-select" required disabled>
              <option value="">Select Province first</option>
            </select>
            <input type="hidden" name="city" id="add_city">
          </div>
          <div class="col-md-6">
            <label class="form-label">Barangay</label>
            <select name="barangay_id" id="add_barangay_id" class="form-select" required disabled>
              <option value="">Select Municipality first</option>
            </select>
            <input type="hidden" name="barangay" id="add_barangay">
          </div>
          <div class="col-md-6">
            <label class="form-label">Role</label>
            <select class="form-select" name="role">
              <option value="user">User</option>
              <option value="official">Official</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="col-12">
            <div class="alert alert-info py-2 mb-0">
              <i class="bi bi-info-circle me-2"></i>Default password will be set to <strong>123</strong>.
            </div>
          </div>
          <div class="col-12 text-end">
            <button type="submit" class="btn btn-black">Save User</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit User Form (visible when ?edit=ID) -->
    <?php if ($edit_user): ?>
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">Edit User: <?= htmlspecialchars($edit_user['username']) ?></div>
        <div class="card-body">
          <form method="post" action="/UserManagement.php" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= (int)$edit_user['id'] ?>">
            <div class="col-md-6">
              <label class="form-label">First Name</label>
              <input type="text" name="firstName" class="form-control" value="<?= htmlspecialchars($edit_user['firstName']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Last Name</label>
              <input type="text" name="lastName" class="form-control" value="<?= htmlspecialchars($edit_user['lastName']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($edit_user['username']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Password</label>
              <input type="text" name="password" class="form-control" placeholder="Leave blank to keep current password">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit_user['email']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone Number</label>
              <input type="text" name="phoneNumber" class="form-control" value="<?= htmlspecialchars($edit_user['phoneNumber']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Middle Name</label>
              <input type="text" name="middleName" class="form-control" value="<?= htmlspecialchars($edit_user['middleName'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Region</label>
              <select name="region_id" id="edit_region_id" class="form-select" required>
                <option value="">Select Region</option>
              </select>
              <input type="hidden" name="region" id="edit_region" value="<?= htmlspecialchars($edit_user['region'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Province</label>
              <select name="province_id" id="edit_province_id" class="form-select" required>
                <option value="">Select Region first</option>
              </select>
              <input type="hidden" name="province" id="edit_province" value="<?= htmlspecialchars($edit_user['province'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Municipality/City</label>
              <select name="municipality_id" id="edit_municipality_id" class="form-select" required>
                <option value="">Select Province first</option>
              </select>
              <input type="hidden" name="city" id="edit_city" value="<?= htmlspecialchars($edit_user['cityMunicipality'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Barangay</label>
              <select name="barangay_id" id="edit_barangay_id" class="form-select" required>
                <option value="">Select Municipality first</option>
              </select>
              <input type="hidden" name="barangay" id="edit_barangay" value="<?= htmlspecialchars($edit_user['barangay'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Role</label>
              <select class="form-select" name="role">
                <option value="user" <?= $edit_user['role']==='user'?'selected':''; ?>>User</option>
                <option value="official" <?= $edit_user['role']==='official'?'selected':''; ?>>Official</option>
                <option value="admin" <?= $edit_user['role']==='admin'?'selected':''; ?>>Admin</option>
              </select>
            </div>
            <div class="col-12 text-end">
              <a href="/UserManagement.php" class="btn btn-secondary">Cancel</a>
              <button type="submit" class="btn btn-black">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <!-- User Table -->
    <div class="card shadow-sm">
      <div class="card-body">
        <table class="table table-hover align-middle" id="userTable">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Phone Number</th>
              <th>Region</th>
              <th>Province</th>
              <th>Barangay</th>
              <th>Municipality/City</th>
              <th>Role</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php $i=1; while ($user = $users_result->fetch_assoc()): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($user['firstName'] . ' ' . $user['lastName']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['phoneNumber']) ?></td>
                <td><?= htmlspecialchars($user['region'] ?? '') ?></td>
                <td><?= htmlspecialchars($user['province'] ?? '') ?></td>
                <td><?= htmlspecialchars($user['barangay']) ?></td>
                <td><?= htmlspecialchars($user['cityMunicipality']) ?></td>
                <td><span class="badge"><?= htmlspecialchars(ucfirst($user['role'])) ?></span></td>
                <td class="d-flex gap-2">
                  <a class="btn btn-sm btn-black" href="/UserManagement.php?edit=<?= (int)$user['id'] ?>">
                    <i class="bi bi-pencil-square"></i> Edit
                  </a>
                  <form method="post" action="/UserManagement.php" onsubmit="return true;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">
                      <i class="bi bi-trash"></i> Delete
                    </button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <footer class="text-white text-center py-4 bg-dark mt-5">
    <p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p>
  </footer>
  
  <script>
  // Load regions on page load
  document.addEventListener('DOMContentLoaded', function() {
    loadRegions('add');
    <?php if ($edit_user): ?>
    loadRegions('edit', <?= (int)($edit_user['region_id'] ?? 0) ?>, <?= (int)($edit_user['province_id'] ?? 0) ?>, <?= (int)($edit_user['municipality_id'] ?? 0) ?>, <?= (int)($edit_user['barangay_id'] ?? 0) ?>);
    <?php endif; ?>
  });

  function loadRegions(mode, selectedRegion = 0, selectedProvince = 0, selectedMunicipality = 0, selectedBarangay = 0) {
    const prefix = mode + '_';
    fetch('/api/get-regions.php')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const select = document.getElementById(prefix + 'region_id');
          select.innerHTML = '<option value="">Select Region</option>';
          data.regions.forEach(region => {
            const option = document.createElement('option');
            option.value = region.id;
            option.textContent = region.name;
            option.dataset.name = region.name;
            if (region.id === selectedRegion) option.selected = true;
            select.appendChild(option);
          });
          select.addEventListener('change', function(e) { onRegionChange(e, mode); });
          if (selectedRegion) {
            loadProvinces(mode, selectedRegion, selectedProvince, selectedMunicipality, selectedBarangay);
          }
        }
      });
  }

  function onRegionChange(e, mode) {
    const regionId = e.target.value;
    const regionName = e.target.options[e.target.selectedIndex].dataset.name || '';
    document.getElementById(mode + '_region').value = regionName;
    const prefix = mode + '_';
    document.getElementById(prefix + 'province_id').innerHTML = '<option value="">Loading...</option>';
    document.getElementById(prefix + 'municipality_id').innerHTML = '<option value="">Select Province first</option>';
    document.getElementById(prefix + 'municipality_id').disabled = true;
    document.getElementById(prefix + 'barangay_id').innerHTML = '<option value="">Select Municipality first</option>';
    document.getElementById(prefix + 'barangay_id').disabled = true;
    if (regionId) loadProvinces(mode, regionId);
  }

  function loadProvinces(mode, regionId, selectedProvince = 0, selectedMunicipality = 0, selectedBarangay = 0) {
    const prefix = mode + '_';
    fetch('/api/get-provinces.php?region_id=' + regionId)
      .then(response => response.json())
      .then(data => {
        const select = document.getElementById(prefix + 'province_id');
        if (data.success) {
          select.innerHTML = '<option value="">Select Province</option>';
          data.provinces.forEach(province => {
            const option = document.createElement('option');
            option.value = province.id;
            option.textContent = province.name;
            option.dataset.name = province.name;
            if (province.id === selectedProvince) option.selected = true;
            select.appendChild(option);
          });
          select.disabled = false;
          select.addEventListener('change', function(e) { onProvinceChange(e, mode); });
          if (selectedProvince) {
            loadMunicipalities(mode, selectedProvince, selectedMunicipality, selectedBarangay);
          }
        }
      });
  }

  function onProvinceChange(e, mode) {
    const provinceId = e.target.value;
    const provinceName = e.target.options[e.target.selectedIndex].dataset.name || '';
    document.getElementById(mode + '_province').value = provinceName;
    const prefix = mode + '_';
    document.getElementById(prefix + 'municipality_id').innerHTML = '<option value="">Loading...</option>';
    document.getElementById(prefix + 'barangay_id').innerHTML = '<option value="">Select Municipality first</option>';
    document.getElementById(prefix + 'barangay_id').disabled = true;
    if (provinceId) loadMunicipalities(mode, provinceId);
  }

  function loadMunicipalities(mode, provinceId, selectedMunicipality = 0, selectedBarangay = 0) {
    const prefix = mode + '_';
    fetch('/api/get-municipalities.php?province_id=' + provinceId)
      .then(response => response.json())
      .then(data => {
        const select = document.getElementById(prefix + 'municipality_id');
        if (data.success) {
          select.innerHTML = '<option value="">Select City/Municipality</option>';
          data.municipalities.forEach(municipality => {
            const option = document.createElement('option');
            option.value = municipality.id;
            option.textContent = municipality.name;
            option.dataset.name = municipality.name;
            if (municipality.id === selectedMunicipality) option.selected = true;
            select.appendChild(option);
          });
          select.disabled = false;
          select.addEventListener('change', function(e) { onMunicipalityChange(e, mode); });
          if (selectedMunicipality) {
            loadBarangays(mode, selectedMunicipality, selectedBarangay);
          }
        }
      });
  }

  function onMunicipalityChange(e, mode) {
    const municipalityId = e.target.value;
    const municipalityName = e.target.options[e.target.selectedIndex].dataset.name || '';
    document.getElementById(mode + '_city').value = municipalityName;
    const prefix = mode + '_';
    document.getElementById(prefix + 'barangay_id').innerHTML = '<option value="">Loading...</option>';
    if (municipalityId) loadBarangays(mode, municipalityId);
  }

  function loadBarangays(mode, municipalityId, selectedBarangay = 0) {
    const prefix = mode + '_';
    fetch('/api/get-barangays.php?municipality_id=' + municipalityId)
      .then(response => response.json())
      .then(data => {
        const select = document.getElementById(prefix + 'barangay_id');
        if (data.success) {
          select.innerHTML = '<option value="">Select Barangay</option>';
          data.barangays.forEach(barangay => {
            const option = document.createElement('option');
            option.value = barangay.id;
            option.textContent = barangay.name;
            option.dataset.name = barangay.name;
            if (barangay.id === selectedBarangay) option.selected = true;
            select.appendChild(option);
          });
          select.disabled = false;
          select.addEventListener('change', function(e) { onBarangayChange(e, mode); });
        }
      });
  }

  function onBarangayChange(e, mode) {
    const barangayName = e.target.options[e.target.selectedIndex].dataset.name || '';
    document.getElementById(mode + '_barangay').value = barangayName;
  }
  </script>
</body>
</html>
