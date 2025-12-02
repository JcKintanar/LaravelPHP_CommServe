<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';

// Admin/Official guard
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'official'], true)) {
  header('Location: /pages/loginPage.php');
  exit;
}

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

function flash($type, $msg) {
  $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
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
    $brgy = trim($_POST['barangay'] ?? '');
    $city = trim($_POST['cityMunicipality'] ?? '');

    if ($name === '' || $number === '') {
      flash('danger', 'Name and number are required.');
      header('Location: /emergencyHotlines.php');
      exit;
    }

    $stmt = $conn->prepare('INSERT INTO emergency_hotlines (name, number, description, barangay, cityMunicipality) VALUES (?,?,?,?,?)');
    $stmt->bind_param('sssss', $name, $number, $description, $brgy, $city);
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
    $brgy = trim($_POST['barangay'] ?? '');
    $city = trim($_POST['cityMunicipality'] ?? '');

    if ($id <= 0 || $name === '' || $number === '') {
      flash('danger', 'Invalid data.');
      header('Location: /emergencyHotlines.php');
      exit;
    }

    $stmt = $conn->prepare('UPDATE emergency_hotlines SET name=?, number=?, description=?, barangay=?, cityMunicipality=? WHERE id=?');
    $stmt->bind_param('sssssi', $name, $number, $description, $brgy, $city, $id);
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

$hotlines = $conn->query('SELECT id, name, number, description, barangay, cityMunicipality, createdAt FROM emergency_hotlines ORDER BY name ASC');

$edit_hotline = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  if ($eid > 0) {
    $st = $conn->prepare('SELECT id, name, number, description, barangay, cityMunicipality FROM emergency_hotlines WHERE id=?');
    $st->bind_param('i', $eid);
    $st->execute();
    $res = $st->get_result();
    $edit_hotline = $res->fetch_assoc() ?: null;
    $st->close();
  }
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
      <a class="navbar-brand fw-bold" href="/dashboards/adminDashboard.php"><i class="bi bi-telephone-fill me-2"></i>Barangay <?= $barangay ?></a>
      <div class="d-flex">
        <a class="nav-link text-white" href="/dashboards/adminDashboard.php"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
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
        <form method="post" action="/emergencyHotlines.php" class="row g-3">
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
          <div class="col-md-6">
            <label class="form-label">Barangay</label>
            <input type="text" name="barangay" class="form-control" value="<?= $barangay ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Municipality/City</label>
            <input type="text" name="cityMunicipality" class="form-control">
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
            <div class="col-md-6">
              <label class="form-label">Barangay</label>
              <input type="text" name="barangay" class="form-control" value="<?= htmlspecialchars($edit_hotline['barangay'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Municipality/City</label>
              <input type="text" name="cityMunicipality" class="form-control" value="<?= htmlspecialchars($edit_hotline['cityMunicipality'] ?? '') ?>">
            </div>
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
</body>
</html>
