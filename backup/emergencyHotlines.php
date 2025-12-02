<?php
// Guard: admin or official
session_start();
require_once __DIR__ . '/userAccounts/config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin','official'], true)) {
  header('Location: /pages/loginPage.php');
  exit;
}

// Fetch barangay and city (raw for DB, escaped for UI)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT barangay, barangay_id, cityMunicipality, municipality_id, province, province_id, region, region_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$barangayRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$barangayRaw = $barangayRow['barangay'] ?? 'Barangay';
$cityRaw = $barangayRow['cityMunicipality'] ?? '';
$user_role = $_SESSION['role'] ?? '';
$user_barangay_id = $barangayRow['barangay_id'] ?? null;
$user_municipality_id = $barangayRow['municipality_id'] ?? null;
$user_province_id = $barangayRow['province_id'] ?? null;
$user_region_id = $barangayRow['region_id'] ?? null;
$barangayEsc = htmlspecialchars($barangayRaw, ENT_QUOTES, 'UTF-8');
$cityEsc = htmlspecialchars($cityRaw, ENT_QUOTES, 'UTF-8');

// Ensure table exists and add hierarchical targeting columns
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
  "province_id INT",
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

// Simple flash via session
if (!isset($_SESSION['flash'])) $_SESSION['flash'] = null;
function flashSet($type, $msg){ $_SESSION['flash'] = ['type'=>$type,'msg'=>$msg]; }
function flashShow(){ if(!empty($_SESSION['flash'])){ $f=$_SESSION['flash']; echo '<div class="alert alert-'.$f['type'].'">'.htmlspecialchars($f['msg']).'</div>'; $_SESSION['flash']=null; } }

// Handle add/delete via PRG
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($name === '' || $number === '') {
      flashSet('danger','Name and number are required.');
    } else {
      // Officials restricted to their barangay; Admins can choose scope
      $scope_level = $user_role === 'admin' ? trim($_POST['scope_level'] ?? 'ALL') : 'BARANGAY';
      $target_id = null;
      $region = '';$province='';$city='';$brgy='';
      $region_id = null;$province_id=null;$municipality_id=null;$barangay_id=null;

      if ($user_role === 'official') {
        $brgy = $barangayRaw; $barangay_id = $user_barangay_id;
        $city = $cityRaw; $municipality_id = $user_municipality_id;
        $province = $barangayRow['province'] ?? ''; $province_id = $user_province_id;
        $region = $barangayRow['region'] ?? ''; $region_id = $user_region_id;
        $target_id = $barangay_id;
        $scope_level = 'BARANGAY';
      } else {
        // Admin scope selection
        if ($scope_level === 'REGION' && isset($_POST['region_id'])) {
          $region_id = (int)$_POST['region_id'];
          $region = trim($_POST['region'] ?? '');
          $target_id = $region_id;
        } elseif ($scope_level === 'PROVINCE' && isset($_POST['province_id'])) {
          $province_id = (int)$_POST['province_id'];
          $province = trim($_POST['province'] ?? '');
          $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null;
          $region = trim($_POST['region'] ?? '');
          $target_id = $province_id;
        } elseif ($scope_level === 'MUNICIPALITY' && isset($_POST['municipality_id'])) {
          $municipality_id = (int)$_POST['municipality_id'];
          $city = trim($_POST['city'] ?? '');
          $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : null;
          $province = trim($_POST['province'] ?? '');
          $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null;
          $region = trim($_POST['region'] ?? '');
          $target_id = $municipality_id;
        } elseif ($scope_level === 'BARANGAY' && isset($_POST['barangay_id'])) {
          $barangay_id = (int)$_POST['barangay_id'];
          $brgy = trim($_POST['barangay'] ?? '');
          $municipality_id = isset($_POST['municipality_id']) ? (int)$_POST['municipality_id'] : null;
          $city = trim($_POST['city'] ?? '');
          $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : null;
          $province = trim($_POST['province'] ?? '');
          $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null;
          $region = trim($_POST['region'] ?? '');
          $target_id = $barangay_id;
        }
      }

      $stmt = $conn->prepare('INSERT INTO emergency_hotlines (name, number, description, scope_level, target_id, barangay, barangay_id, cityMunicipality, municipality_id, province, province_id, region, region_id, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
      $stmt->bind_param('ssssisssissisi', $name, $number, $desc, $scope_level, $target_id, $brgy, $barangay_id, $city, $municipality_id, $province, $province_id, $region, $region_id, $user_id);
      if ($stmt->execute()) { flashSet('success','Hotline added.'); } else { flashSet('danger','Failed to add hotline.'); }
      $stmt->close();
    }
    header('Location: /emergencyHotlines.php');
    exit;
  }
  if ($action === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($id > 0 && $name !== '' && $number !== '') {
    // Officials restricted to their barangay; Admins edit freely within scope
    if ($user_role === 'official') {
      $stmt = $conn->prepare('UPDATE emergency_hotlines SET name=?, number=?, description=? WHERE id=? AND barangay_id=?');
      $stmt->bind_param('sssii', $name, $number, $desc, $id, $user_barangay_id);
    } else {
      $stmt = $conn->prepare('UPDATE emergency_hotlines SET name=?, number=?, description=? WHERE id=?');
      $stmt->bind_param('sssi', $name, $number, $desc, $id);
    }
      if ($stmt->execute()) { flashSet('success','Hotline updated.'); } else { flashSet('danger','Failed to update hotline.'); }
      $stmt->close();
    }
    header('Location: /emergencyHotlines.php');
    exit;
  }
  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
    if ($user_role === 'official') {
      $stmt = $conn->prepare('DELETE FROM emergency_hotlines WHERE id=? AND barangay_id=?');
      $stmt->bind_param('ii', $id, $user_barangay_id);
    } else {
      $stmt = $conn->prepare('DELETE FROM emergency_hotlines WHERE id=?');
      $stmt->bind_param('i', $id);
    }
      if ($stmt->execute()) { flashSet('success','Hotline deleted.'); } else { flashSet('danger','Failed to delete hotline.'); }
      $stmt->close();
    }
    header('Location: /emergencyHotlines.php');
    exit;
  }
}

// Fetch hotlines
if ($user_role === 'official') {
  $stmt = $conn->prepare('SELECT id, name, number, description, createdAt FROM emergency_hotlines WHERE barangay_id = ? ORDER BY createdAt DESC');
  $stmt->bind_param('i', $user_barangay_id);
  $stmt->execute();
  $hotlines = $stmt->get_result();
  $stmt->close();
} else {
  $hotlines = $conn->query('SELECT id, name, number, description, createdAt FROM emergency_hotlines ORDER BY createdAt DESC');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Emergency Hotlines - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    footer.fixed-bottom-footer { position: fixed; left:0; bottom:0; width:100%; z-index:999; }
    body { padding-bottom: 80px; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/dashboards/officialDashboard.php"><i class="bi bi-briefcase me-2"></i>Barangay <?= $barangay ?> Official</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="/dashboards/officialDashboard.php"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container my-5">
  <h2 class="mb-4">Emergency Hotlines</h2>
  <?php flashShow(); ?>

  <div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
      <i class="bi bi-telephone-fill me-2"></i>Add Hotline
    </div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <input type="hidden" name="action" value="add">
        <div class="col-md-4">
          <label class="form-label">Name</label>
          <input type="text" name="name" class="form-control" placeholder="e.g., Police" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Number</label>
          <input type="text" name="number" class="form-control" placeholder="e.g., 911 or 123-4567" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Description (optional)</label>
          <input type="text" name="description" class="form-control" placeholder="e.g., 24/7 hotline">
        </div>
        <?php if ($user_role === 'admin'): ?>
        <div class="col-md-12">
          <label class="form-label"><i class="bi bi-broadcast me-2"></i>Broadcast Scope</label>
          <select name="scope_level" id="add_scope_level" class="form-select" required>
            <option value="ALL">🌍 All Locations</option>
            <option value="REGION">📍 Specific Region</option>
            <option value="PROVINCE">🏛️ Specific Province</option>
            <option value="MUNICIPALITY">🏙️ Specific City/Municipality</option>
            <option value="BARANGAY">🏘️ Specific Barangay</option>
          </select>
        </div>
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
              <input type="hidden" name="city" id="add_city">
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
        <?php else: ?>
        <div class="col-md-12">
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Posting to: Barangay <?= $barangayEsc ?>, <?= $cityEsc ?>
          </div>
        </div>
        <?php endif; ?>
        <div class="col-12 text-end">
          <button type="submit" class="btn btn-dark">Add Hotline</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-dark text-white">Existing Hotlines</div>
    <div class="card-body">
      <?php if ($hotlines && $hotlines->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-dark"><tr><th>#</th><th>Name</th><th>Number</th><th>Description</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
              <?php 
                $i=1; 
                $edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
                while($h = $hotlines->fetch_assoc()): 
                  if ($edit_id === (int)$h['id']) { // Edit mode row
              ?>
                <tr class="table-warning">
                  <form method="post">
                    <td><?= $i++ ?></td>
                    <td><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($h['name']) ?>" required></td>
                    <td><input type="text" name="number" class="form-control" value="<?= htmlspecialchars($h['number']) ?>" required></td>
                    <td><input type="text" name="description" class="form-control" value="<?= htmlspecialchars($h['description'] ?? '') ?>"></td>
                    <td><?= htmlspecialchars(date('M d, Y', strtotime($h['createdAt']))) ?></td>
                    <td>
                      <input type="hidden" name="action" value="edit">
                      <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-save"></i> Save</button>
                      <a href="/emergencyHotlines.php" class="btn btn-sm btn-secondary">Cancel</a>
                    </td>
                  </form>
                </tr>
              <?php 
                  } else { // Normal row
              ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><?= htmlspecialchars($h['name']) ?></td>
                  <td><?= htmlspecialchars($h['number']) ?></td>
                  <td><?= htmlspecialchars($h['description'] ?? '') ?></td>
                  <td><?= htmlspecialchars(date('M d, Y', strtotime($h['createdAt']))) ?></td>
                  <td>
                    <a href="/emergencyHotlines.php?edit=<?= (int)$h['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
                    </form>
                  </td>
                </tr>
              <?php 
                  }
                endwhile; 
              ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted mb-0">No hotlines added yet.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<footer class="text-white text-center py-4 bg-dark fixed-bottom-footer">
  <p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const addScope = document.getElementById('add_scope_level');
  if (addScope) {
    addScope.addEventListener('change', function(){ handleScopeChange('add', this.value); });
  }
  loadRegions('add');
});

function handleScopeChange(mode, scope) {
  const prefix = mode + '_';
  const selector = document.getElementById(prefix + 'scope_selector');
  const regionDiv = document.getElementById(prefix + 'region_selector');
  const provinceDiv = document.getElementById(prefix + 'province_selector');
  const municipalityDiv = document.getElementById(prefix + 'municipality_selector');
  const barangayDiv = document.getElementById(prefix + 'barangay_selector');
  if (!selector) return;
  selector.style.display = 'none'; regionDiv.style.display='none'; provinceDiv.style.display='none'; municipalityDiv.style.display='none'; barangayDiv.style.display='none';
  if (scope === 'ALL') return;
  selector.style.display = 'block';
  if (scope === 'REGION') { regionDiv.style.display='block'; }
  else if (scope === 'PROVINCE') { regionDiv.style.display='block'; provinceDiv.style.display='block'; }
  else if (scope === 'MUNICIPALITY') { regionDiv.style.display='block'; provinceDiv.style.display='block'; municipalityDiv.style.display='block'; }
  else if (scope === 'BARANGAY') { regionDiv.style.display='block'; provinceDiv.style.display='block'; municipalityDiv.style.display='block'; barangayDiv.style.display='block'; }
}

function loadRegions(mode, selectedRegion = 0, selectedProvince = 0, selectedMunicipality = 0, selectedBarangay = 0) {
  const prefix = mode + '_';
  const rsel = document.getElementById(prefix+'region_id'); if (!rsel) return;
  fetch('/api/get-regions.php').then(r=>r.json()).then(data=>{
    if (data.success) {
      rsel.innerHTML = '<option value="">Select Region</option>';
      data.regions.forEach(region=>{
        const opt = document.createElement('option'); opt.value = region.id; opt.textContent = region.name; opt.dataset.name = region.name; rsel.appendChild(opt);
      });
      rsel.addEventListener('change', function(e){
        document.getElementById(prefix+'region').value = e.target.options[e.target.selectedIndex].dataset.name || '';
        loadProvinces(mode, this.value);
      });
    }
  });
}
function loadProvinces(mode, regionId) {
  const prefix = mode + '_'; const psel = document.getElementById(prefix+'province_id'); if (!psel) return;
  fetch('/api/get-provinces.php?region_id='+regionId).then(r=>r.json()).then(data=>{
    if (data.success) {
      psel.innerHTML = '<option value="">Select Province</option>';
      data.provinces.forEach(p=>{ const opt=document.createElement('option'); opt.value=p.id; opt.textContent=p.name; opt.dataset.name=p.name; psel.appendChild(opt); });
      psel.disabled=false;
      psel.addEventListener('change', function(e){ document.getElementById(prefix+'province').value = e.target.options[e.target.selectedIndex].dataset.name || ''; loadMunicipalities(mode, this.value); });
    }
  });
}
function loadMunicipalities(mode, provinceId) {
  const prefix = mode + '_'; const msel = document.getElementById(prefix+'municipality_id'); if (!msel) return;
  fetch('/api/get-municipalities.php?province_id='+provinceId).then(r=>r.json()).then(data=>{
    if (data.success) {
      msel.innerHTML = '<option value="">Select City/Municipality</option>';
      data.municipalities.forEach(m=>{ const opt=document.createElement('option'); opt.value=m.id; opt.textContent=m.name; opt.dataset.name=m.name; msel.appendChild(opt); });
      msel.disabled=false;
      msel.addEventListener('change', function(e){ document.getElementById(prefix+'city').value = e.target.options[e.target.selectedIndex].dataset.name || ''; loadBarangays(mode, this.value); });
    }
  });
}
function loadBarangays(mode, municipalityId) {
  const prefix = mode + '_'; const bsel = document.getElementById(prefix+'barangay_id'); if (!bsel) return;
  fetch('/api/get-barangays.php?municipality_id='+municipalityId).then(r=>r.json()).then(data=>{
    if (data.success) {
      bsel.innerHTML = '<option value="">Select Barangay</option>';
      data.barangays.forEach(b=>{ const opt=document.createElement('option'); opt.value=b.id; opt.textContent=b.name; opt.dataset.name=b.name; bsel.appendChild(opt); });
      bsel.disabled=false;
      bsel.addEventListener('change', function(e){ document.getElementById(prefix+'barangay').value = e.target.options[e.target.selectedIndex].dataset.name || ''; });
    }
  });
}
</script>
</body>
</html>
