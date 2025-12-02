<?php
// Guard: officials only
require_once __DIR__ . '/includes/officialCheck.php';
require_once __DIR__ . '/userAccounts/config.php';

// Fetch barangay and city (raw for DB, escaped for UI)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT barangay, cityMunicipality FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$barangayRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$barangayRaw = $barangayRow['barangay'] ?? 'Barangay';
$cityRaw = $barangayRow['cityMunicipality'] ?? '';
$barangayEsc = htmlspecialchars($barangayRaw, ENT_QUOTES, 'UTF-8');
$cityEsc = htmlspecialchars($cityRaw, ENT_QUOTES, 'UTF-8');

// Ensure table exists (id, name, barangay, number, description, createdAt)
$conn->query("CREATE TABLE IF NOT EXISTS emergency_hotlines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  barangay VARCHAR(100) NOT NULL,
  cityMunicipality VARCHAR(50) NOT NULL,
  number VARCHAR(30) NOT NULL,
  description VARCHAR(255) NULL,
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

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
  $stmt = $conn->prepare('INSERT INTO emergency_hotlines (name, barangay, cityMunicipality, number, description) VALUES (?,?,?,?,?)');
  $stmt->bind_param('sssss', $name, $barangayRaw, $cityRaw, $number, $desc);
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
  $stmt = $conn->prepare('UPDATE emergency_hotlines SET name=?, number=?, description=? WHERE id=? AND barangay=? AND cityMunicipality=?');
  $stmt->bind_param('sssiss', $name, $number, $desc, $id, $barangayRaw, $cityRaw);
      if ($stmt->execute()) { flashSet('success','Hotline updated.'); } else { flashSet('danger','Failed to update hotline.'); }
      $stmt->close();
    }
    header('Location: /emergencyHotlines.php');
    exit;
  }
  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
  $stmt = $conn->prepare('DELETE FROM emergency_hotlines WHERE id=? AND barangay=? AND cityMunicipality=?');
  $stmt->bind_param('iss', $id, $barangayRaw, $cityRaw);
      if ($stmt->execute()) { flashSet('success','Hotline deleted.'); } else { flashSet('danger','Failed to delete hotline.'); }
      $stmt->close();
    }
    header('Location: /emergencyHotlines.php');
    exit;
  }
}

// Fetch hotlines for this official's barangay only
$stmt = $conn->prepare('SELECT id, name, number, description, createdAt FROM emergency_hotlines WHERE barangay = ? AND cityMunicipality = ? ORDER BY createdAt DESC');
$stmt->bind_param('ss', $barangayRaw, $cityRaw);
$stmt->execute();
$hotlines = $stmt->get_result();
$stmt->close();
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
</body>
</html>
