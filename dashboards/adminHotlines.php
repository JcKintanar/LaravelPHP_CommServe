<?php
session_start();
require_once __DIR__ . '/../userAccounts/config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header('Location: /pages/loginPage.php');
  exit;
}
$user_id = (int)$_SESSION['user_id'];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function flashSet($type,$msg){ $_SESSION['flash']=['type'=>$type,'msg'=>$msg]; }
function flashGet(){ $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }

// Ensure table and columns for hierarchical targeting
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
$columns_to_add = [
  "scope_level VARCHAR(50) DEFAULT 'BARANGAY'",
  "target_id INT",
  "barangay VARCHAR(100)",
  "barangay_id INT",
  "cityMunicipality VARCHAR(100)",
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
  if ($check && $check->num_rows == 0) { $conn->query("ALTER TABLE emergency_hotlines ADD COLUMN $column"); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($name === '' || $number === '') { flashSet('danger','Name and number are required.'); }
    else {
      $scope_level = trim($_POST['scope_level'] ?? 'ALL');
      $target_id = null;
      $region = '';$province='';$city='';$brgy='';
      $region_id = null;$province_id=null;$municipality_id=null;$barangay_id=null;
      if ($scope_level === 'REGION' && isset($_POST['region_id'])) {
        $region_id = (int)$_POST['region_id']; $region = trim($_POST['region'] ?? ''); $target_id = $region_id;
      } elseif ($scope_level === 'PROVINCE' && isset($_POST['province_id'])) {
        $province_id = (int)$_POST['province_id']; $province = trim($_POST['province'] ?? '');
        $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null; $region = trim($_POST['region'] ?? '');
        $target_id = $province_id;
      } elseif ($scope_level === 'MUNICIPALITY' && isset($_POST['municipality_id'])) {
        $municipality_id = (int)$_POST['municipality_id']; $city = trim($_POST['city'] ?? '');
        $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : null; $province = trim($_POST['province'] ?? '');
        $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null; $region = trim($_POST['region'] ?? '');
        $target_id = $municipality_id;
      } elseif ($scope_level === 'BARANGAY' && isset($_POST['barangay_id'])) {
        $barangay_id = (int)$_POST['barangay_id']; $brgy = trim($_POST['barangay'] ?? '');
        $municipality_id = isset($_POST['municipality_id']) ? (int)$_POST['municipality_id'] : null; $city = trim($_POST['city'] ?? '');
        $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : null; $province = trim($_POST['province'] ?? '');
        $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null; $region = trim($_POST['region'] ?? '');
        $target_id = $barangay_id;
      } else { $scope_level = 'ALL'; }

      $stmt = $conn->prepare('INSERT INTO emergency_hotlines (name, number, description, scope_level, target_id, barangay, barangay_id, cityMunicipality, municipality_id, province, province_id, region, region_id, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
      $stmt->bind_param('ssssisisisisii', $name, $number, $desc, $scope_level, $target_id, $brgy, $barangay_id, $city, $municipality_id, $province, $province_id, $region, $region_id, $user_id);
      if ($stmt->execute()) { flashSet('success','Hotline added.'); } else { flashSet('danger','Failed to add hotline.'); }
      $stmt->close();
    }
    header('Location: /dashboards/adminHotlines.php'); exit;
  } elseif ($action === 'edit') {
    $id = (int)($_POST['id'] ?? 0); $name = trim($_POST['name'] ?? ''); $number = trim($_POST['number'] ?? ''); $desc = trim($_POST['description'] ?? '');
    if ($id <= 0 || $name === '' || $number === '') { flashSet('danger','Invalid edit data.'); }
    else {
      $scope_level = trim($_POST['scope_level'] ?? 'ALL');
      $target_id = null;
      $region = '';$province='';$city='';$brgy='';
      $region_id = null;$province_id=null;$municipality_id=null;$barangay_id=null;
      if ($scope_level === 'REGION' && isset($_POST['region_id'])) {
        $region_id = (int)$_POST['region_id']; $region = trim($_POST['region'] ?? ''); $target_id = $region_id;
      } elseif ($scope_level === 'PROVINCE' && isset($_POST['province_id'])) {
        $province_id = (int)$_POST['province_id']; $province = trim($_POST['province'] ?? '');
        $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null; $region = trim($_POST['region'] ?? '');
        $target_id = $province_id;
      } elseif ($scope_level === 'MUNICIPALITY' && isset($_POST['municipality_id'])) {
        $municipality_id = (int)$_POST['municipality_id']; $city = trim($_POST['city'] ?? '');
        $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : null; $province = trim($_POST['province'] ?? '');
        $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null; $region = trim($_POST['region'] ?? '');
        $target_id = $municipality_id;
      } elseif ($scope_level === 'BARANGAY' && isset($_POST['barangay_id'])) {
        $barangay_id = (int)$_POST['barangay_id']; $brgy = trim($_POST['barangay'] ?? '');
        $municipality_id = isset($_POST['municipality_id']) ? (int)$_POST['municipality_id'] : null; $city = trim($_POST['city'] ?? '');
        $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : null; $province = trim($_POST['province'] ?? '');
        $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null; $region = trim($_POST['region'] ?? '');
        $target_id = $barangay_id;
      } else { $scope_level = 'ALL'; }

      $stmt = $conn->prepare('UPDATE emergency_hotlines SET name=?, number=?, description=?, scope_level=?, target_id=?, barangay=?, barangay_id=?, cityMunicipality=?, municipality_id=?, province=?, province_id=?, region=?, region_id=? WHERE id=?');
      $stmt->bind_param('ssssisisisisii', $name, $number, $desc, $scope_level, $target_id, $brgy, $barangay_id, $city, $municipality_id, $province, $province_id, $region, $region_id, $id);
      if ($stmt->execute()) { flashSet('success','Hotline updated.'); } else { flashSet('danger','Failed to update hotline.'); }
      $stmt->close();
    }
    header('Location: /dashboards/adminHotlines.php'); exit;
  } elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { flashSet('danger','Invalid delete.'); }
    else { $stmt = $conn->prepare('DELETE FROM emergency_hotlines WHERE id=?'); $stmt->bind_param('i', $id); if ($stmt->execute()) { flashSet('success','Hotline deleted.'); } else { flashSet('danger','Failed to delete hotline.'); } $stmt->close(); }
    header('Location: /dashboards/adminHotlines.php'); exit;
  }
}

$hotlines = $conn->query('SELECT id, name, number, description, scope_level, createdAt FROM emergency_hotlines ORDER BY createdAt DESC');
$flash = flashGet();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Hotlines</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color:#f8f9fa; color:#212529; }
    /* Use Bootstrap dark navbar to match other pages */
    .card-header { background-color:#f1f3f5; }
    .card { background-color:#ffffff; border-color:#dee2e6; }
    .form-control, .form-select { background-color:#ffffff; color:#212529; border-color:#ced4da; }
    .btn-dark { background-color:#212529; }
    .table-dark { background-color:#343a40; }
    .fixed-bottom-footer { position:fixed; left:0; bottom:0; width:100%; }
    /* Fit modal nicely on wide screens */
    .modal-dialog { max-width: 95vw; }
    .modal-body { padding-top: 0.75rem; }
    .modal-header { border-bottom: none; padding-bottom: 0.5rem; }
    .modal-footer { border-top: none; }
  </style>
  <script>
    function onScopeChange(){ const s=document.getElementById('scope_level').value; const sel=document.getElementById('scope_selector'); const show=(id,vis)=>{document.getElementById(id).style.display=vis?'block':'none';}; sel.style.display='none'; show('region_selector',false); show('province_selector',false); show('municipality_selector',false); show('barangay_selector',false); if(s==='ALL')return; sel.style.display='block'; if(s==='REGION'){show('region_selector',true);} else if(s==='PROVINCE'){show('region_selector',true);show('province_selector',true);} else if(s==='MUNICIPALITY'){show('region_selector',true);show('province_selector',true);show('municipality_selector',true);} else if(s==='BARANGAY'){show('region_selector',true);show('province_selector',true);show('municipality_selector',true);show('barangay_selector',true);} }
    async function loadRegions(){ const r=await fetch('/api/get-regions.php'); const d=await r.json(); const sel=document.getElementById('region_id'); if(d.success){ sel.innerHTML='<option value="">Select Region</option>'; d.regions.forEach(x=>{ const o=document.createElement('option'); o.value=x.id; o.textContent=x.name; o.dataset.name=x.name; sel.appendChild(o); }); sel.addEventListener('change',e=>{ document.getElementById('region').value=e.target.options[e.target.selectedIndex].dataset.name||''; loadProvinces(sel.value); }); } }
    async function loadProvinces(regionId){ const r=await fetch('/api/get-provinces.php?region_id='+regionId); const d=await r.json(); const sel=document.getElementById('province_id'); if(d.success){ sel.innerHTML='<option value="">Select Province</option>'; d.provinces.forEach(x=>{ const o=document.createElement('option'); o.value=x.id; o.textContent=x.name; o.dataset.name=x.name; sel.appendChild(o); }); sel.disabled=false; sel.addEventListener('change',e=>{ document.getElementById('province').value=e.target.options[e.target.selectedIndex].dataset.name||''; loadMunicipalities(sel.value); }); } }
    async function loadMunicipalities(provinceId){ const r=await fetch('/api/get-municipalities.php?province_id='+provinceId); const d=await r.json(); const sel=document.getElementById('municipality_id'); if(d.success){ sel.innerHTML='<option value="">Select City/Municipality</option>'; d.municipalities.forEach(x=>{ const o=document.createElement('option'); o.value=x.id; o.textContent=x.name; o.dataset.name=x.name; sel.appendChild(o); }); sel.disabled=false; sel.addEventListener('change',e=>{ document.getElementById('city').value=e.target.options[e.target.selectedIndex].dataset.name||''; loadBarangays(sel.value); }); } }
    async function loadBarangays(municipalityId){ const r=await fetch('/api/get-barangays.php?municipality_id='+municipalityId); const d=await r.json(); const sel=document.getElementById('barangay_id'); if(d.success){ sel.innerHTML='<option value="">Select Barangay</option>'; d.barangays.forEach(x=>{ const o=document.createElement('option'); o.value=x.id; o.textContent=x.name; o.dataset.name=x.name; sel.appendChild(o); }); sel.disabled=false; sel.addEventListener('change',e=>{ document.getElementById('barangay').value=e.target.options[e.target.selectedIndex].dataset.name||''; }); } }
    document.addEventListener('DOMContentLoaded',()=>{ loadRegions(); document.getElementById('scope_level').addEventListener('change',onScopeChange); });
  </script>
  <script>
    function onScopeChangeEdit(id){ const s=document.getElementById('scope_level_edit_'+id).value; const sel=document.getElementById('scope_selector_edit_'+id); const show=(suffix,vis)=>{ const el=document.getElementById(suffix+'_edit_'+id); if (el) el.style.display=vis?'block':'none'; }; sel.style.display='none'; show('region_selector',false); show('province_selector',false); show('municipality_selector',false); show('barangay_selector',false); if(s==='ALL')return; sel.style.display='block'; if(s==='REGION'){show('region_selector',true);} else if(s==='PROVINCE'){show('region_selector',true);show('province_selector',true);} else if(s==='MUNICIPALITY'){show('region_selector',true);show('province_selector',true);show('municipality_selector',true);} else if(s==='BARANGAY'){show('region_selector',true);show('province_selector',true);show('municipality_selector',true);show('barangay_selector',true);} }
    async function loadRegionsEdit(id){ const r=await fetch('/api/get-regions.php'); const d=await r.json(); const sel=document.getElementById('region_id_edit_'+id); if(d.success){ sel.innerHTML='<option value="">Select Region</option>'; d.regions.forEach(x=>{ const o=document.createElement('option'); o.value=x.id; o.textContent=x.name; o.dataset.name=x.name; sel.appendChild(o); }); sel.addEventListener('change',e=>{ document.getElementById('region_edit_'+id).value=e.target.options[e.target.selectedIndex].dataset.name||''; loadProvincesEdit(id, sel.value); }); } }
    async function loadProvincesEdit(id, regionId){ const r=await fetch('/api/get-provinces.php?region_id='+regionId); const d=await r.json(); const sel=document.getElementById('province_id_edit_'+id); if(d.success){ sel.innerHTML='<option value="">Select Province</option>'; d.provinces.forEach(x=>{ const o=document.createElement('option'); o.value=x.id; o.textContent=x.name; o.dataset.name=x.name; sel.appendChild(o); }); sel.disabled=false; sel.addEventListener('change',e=>{ document.getElementById('province_edit_'+id).value=e.target.options[e.target.selectedIndex].dataset.name||''; loadMunicipalitiesEdit(id, sel.value); }); } }
    async function loadMunicipalitiesEdit(id, provinceId){ const r=await fetch('/api/get-municipalities.php?province_id='+provinceId); const d=await r.json(); const sel=document.getElementById('municipality_id_edit_'+id); if(d.success){ sel.innerHTML='<option value="">Select City/Municipality</option>'; d.municipalities.forEach(x=>{ const o=document.createElement('option'); o.value=x.id; o.textContent=x.name; o.dataset.name=x.name; sel.appendChild(o); }); sel.disabled=false; sel.addEventListener('change',e=>{ document.getElementById('city_edit_'+id).value=e.target.options[e.target.selectedIndex].dataset.name||''; loadBarangaysEdit(id, sel.value); }); } }
    async function loadBarangaysEdit(id, municipalityId){ const r=await fetch('/api/get-barangays.php?municipality_id='+municipalityId); const d=await r.json(); const sel=document.getElementById('barangay_id_edit_'+id); if(d.success){ sel.innerHTML='<option value="">Select Barangay</option>'; d.barangays.forEach(x=>{ const o=document.createElement('option'); o.value=x.id; o.textContent=x.name; o.dataset.name=x.name; sel.appendChild(o); }); sel.disabled=false; sel.addEventListener('change',e=>{ document.getElementById('barangay_edit_'+id).value=e.target.options[e.target.selectedIndex].dataset.name||''; }); } }
    document.addEventListener('DOMContentLoaded',()=>{
      const modals = document.querySelectorAll('.modal');
      modals.forEach(m => {
        m.addEventListener('shown.bs.modal', () => {
          const id = m.id.replace('editModal','');
          const sel = document.getElementById('scope_level_edit_'+id);
          if (sel) { onScopeChangeEdit(id); loadRegionsEdit(id); }
        });
      });
    });
  </script>
  </head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/dashboards/adminHotlines.php"><i class="bi bi-telephone-fill me-2"></i>Admin Hotlines</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navHotlines">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="navHotlines" class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="/dashboards/adminDashboard.php"><i class="bi bi-arrow-left"></i> Back to Dashboard</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="container-fluid mb-5 pb-5">
  <?php if ($flash): ?><div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div><?php endif; ?>

  <div class="card shadow-sm mb-4">
    <div class="card-header text-white">Add Hotline</div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <input type="hidden" name="action" value="add">
        <div class="col-md-4"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Number</label><input type="text" name="number" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Description</label><input type="text" name="description" class="form-control"></div>
        <div class="col-md-12"><label class="form-label">Broadcast Scope</label>
          <select name="scope_level" id="scope_level" class="form-select" required>
            <option value="ALL">🌍 All Locations</option>
            <option value="REGION">📍 Specific Region</option>
            <option value="PROVINCE">🏛️ Specific Province</option>
            <option value="MUNICIPALITY">🏙️ Specific City/Municipality</option>
            <option value="BARANGAY">🏘️ Specific Barangay</option>
          </select>
        </div>
        <div id="scope_selector" class="col-md-12" style="display:none;">
          <div class="row g-3">
            <div class="col-md-3" id="region_selector" style="display:none;">
              <label class="form-label">Region</label>
              <select name="region_id" id="region_id" class="form-select"><option value="">Select Region</option></select>
              <input type="hidden" name="region" id="region">
            </div>
            <div class="col-md-3" id="province_selector" style="display:none;">
              <label class="form-label">Province</label>
              <select name="province_id" id="province_id" class="form-select" disabled><option value="">Select Region first</option></select>
              <input type="hidden" name="province" id="province">
            </div>
            <div class="col-md-3" id="municipality_selector" style="display:none;">
              <label class="form-label">City/Municipality</label>
              <select name="municipality_id" id="municipality_id" class="form-select" disabled><option value="">Select Province first</option></select>
              <input type="hidden" name="city" id="city">
            </div>
            <div class="col-md-3" id="barangay_selector" style="display:none;">
              <label class="form-label">Barangay</label>
              <select name="barangay_id" id="barangay_id" class="form-select" disabled><option value="">Select Municipality first</option></select>
              <input type="hidden" name="barangay" id="barangay">
            </div>
          </div>
        </div>
        <div class="col-12 text-end"><button class="btn btn-dark" type="submit">Add Hotline</button></div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header text-white">Existing Hotlines</div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-dark"><tr><th>#</th><th>Name</th><th>Number</th><th>Description</th><th>Scope</th><th>Created</th><th>Actions</th></tr></thead>
          <tbody>
            <?php $i=1; while($h = $hotlines->fetch_assoc()): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= h($h['name']) ?></td>
                <td><?= h($h['number']) ?></td>
                <td><?= h($h['description']) ?></td>
                <td><span class="badge bg-secondary"><?= h($h['scope_level']) ?></span></td>
                <td><small class="text-muted"><?= h($h['createdAt']) ?></small></td>
                <td>
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= (int)$h['id'] ?>"><i class="bi bi-pencil"></i> Edit</button>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
                  </form>
                  <div class="modal fade" id="editModal<?= (int)$h['id'] ?>" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Edit Hotline</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                      <form method="post">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                        <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?= h($h['name']) ?>" required></div>
                        <div class="mb-3"><label class="form-label">Number</label><input type="text" name="number" class="form-control" value="<?= h($h['number']) ?>" required></div>
                        <div class="mb-3"><label class="form-label">Description</label><input type="text" name="description" class="form-control" value="<?= h($h['description']) ?>"></div>
                        <div class="mb-3">
                          <label class="form-label">Broadcast Scope</label>
                          <?php $currentScope = h($h['scope_level']); ?>
                          <select name="scope_level" id="scope_level_edit_<?= (int)$h['id'] ?>" class="form-select" required onchange="onScopeChangeEdit(<?= (int)$h['id'] ?>)">
                            <option value="ALL" <?= $currentScope==='ALL'?'selected':'' ?>>🌍 All Locations</option>
                            <option value="REGION" <?= $currentScope==='REGION'?'selected':'' ?>>📍 Specific Region</option>
                            <option value="PROVINCE" <?= $currentScope==='PROVINCE'?'selected':'' ?>>🏛️ Specific Province</option>
                            <option value="MUNICIPALITY" <?= $currentScope==='MUNICIPALITY'?'selected':'' ?>>🏙️ Specific City/Municipality</option>
                            <option value="BARANGAY" <?= $currentScope==='BARANGAY'?'selected':'' ?>>🏘️ Specific Barangay</option>
                          </select>
                        </div>
                        <div id="scope_selector_edit_<?= (int)$h['id'] ?>" style="display:none;">
                          <div class="row g-3">
                            <div class="col-md-3" id="region_selector_edit_<?= (int)$h['id'] ?>" style="display:none;">
                              <label class="form-label">Region</label>
                              <select name="region_id" id="region_id_edit_<?= (int)$h['id'] ?>" class="form-select"><option value="">Select Region</option></select>
                              <input type="hidden" name="region" id="region_edit_<?= (int)$h['id'] ?>">
                            </div>
                            <div class="col-md-3" id="province_selector_edit_<?= (int)$h['id'] ?>" style="display:none;">
                              <label class="form-label">Province</label>
                              <select name="province_id" id="province_id_edit_<?= (int)$h['id'] ?>" class="form-select" disabled><option value="">Select Region first</option></select>
                              <input type="hidden" name="province" id="province_edit_<?= (int)$h['id'] ?>">
                            </div>
                            <div class="col-md-3" id="municipality_selector_edit_<?= (int)$h['id'] ?>" style="display:none;">
                              <label class="form-label">City/Municipality</label>
                              <select name="municipality_id" id="municipality_id_edit_<?= (int)$h['id'] ?>" class="form-select" disabled><option value="">Select Province first</option></select>
                              <input type="hidden" name="city" id="city_edit_<?= (int)$h['id'] ?>">
                            </div>
                            <div class="col-md-3" id="barangay_selector_edit_<?= (int)$h['id'] ?>" style="display:none;">
                              <label class="form-label">Barangay</label>
                              <select name="barangay_id" id="barangay_id_edit_<?= (int)$h['id'] ?>" class="form-select" disabled><option value="">Select Municipality first</option></select>
                              <input type="hidden" name="barangay" id="barangay_edit_<?= (int)$h['id'] ?>">
                            </div>
                          </div>
                        </div>
                        <div class="text-end"><button type="submit" class="btn btn-dark">Save Changes</button></div>
                      </form>
                    </div>
                  </div></div></div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<footer class="text-white text-center py-4 bg-dark fixed-bottom-footer"><p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>