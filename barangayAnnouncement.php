<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';

// Admin/Official guard
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'official'], true)) {
  header('Location: /pages/loginPage.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';
$stmt_brgy = $conn->prepare("SELECT barangay, barangay_id, cityMunicipality, municipality_id, province, province_id, region, region_id FROM users WHERE id = ?");
$stmt_brgy->bind_param("i", $user_id);
$stmt_brgy->execute();
$brgy_result = $stmt_brgy->get_result();
$user_data = $brgy_result->fetch_assoc();
$stmt_brgy->close();
$barangay = htmlspecialchars($user_data['barangay'] ?? 'Barangay', ENT_QUOTES, 'UTF-8');
$user_barangay_id = $user_data['barangay_id'] ?? null;
$user_municipality_id = $user_data['municipality_id'] ?? null;
$user_province_id = $user_data['province_id'] ?? null;
$user_region_id = $user_data['region_id'] ?? null;

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

function flash($type, $msg) {
  $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// Create announcements table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  icon VARCHAR(100) DEFAULT 'bi-megaphone-fill',
  color VARCHAR(50) DEFAULT 'primary',
  image_path VARCHAR(255),
  scope_level VARCHAR(50) DEFAULT 'ALL',
  target_id INT,
  barangay VARCHAR(100),
  barangay_id INT,
  cityMunicipality VARCHAR(100),
  municipality_id INT,
  region VARCHAR(100),
  region_id INT,
  province VARCHAR(100),
  province_id INT,
  created_by INT,
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Add missing columns if they don't exist
$columns_to_add = [
  "barangay_id INT",
  "municipality_id INT", 
  "region VARCHAR(100)",
  "region_id INT",
  "province VARCHAR(100)",
  "province_id INT",
  "scope_level VARCHAR(50) DEFAULT 'ALL'",
  "target_id INT",
  "created_by INT"
];

foreach ($columns_to_add as $column) {
  $col_name = explode(' ', $column)[0];
  $check = $conn->query("SHOW COLUMNS FROM announcements LIKE '$col_name'");
  if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE announcements ADD COLUMN $column");
  }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf_token'] ?? '';
  if (!hash_equals($_SESSION['csrf_token'], $token)) {
    flash('danger', 'Invalid CSRF token.');
    header('Location: /barangayAnnouncement.php');
    exit;
  }

  $action = $_POST['action'] ?? '';
  
  if ($action === 'add') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-megaphone-fill');
    $color = trim($_POST['color'] ?? 'primary');
    
    // Officials can only post to their barangay
    if ($user_role === 'official') {
      $scope_level = 'BARANGAY';
      $target_id = $user_barangay_id;
      $barangay_id = $user_barangay_id;
      $brgy = $user_data['barangay'] ?? '';
      $municipality_id = $user_municipality_id;
      $city = $user_data['cityMunicipality'] ?? '';
      $province_id = $user_province_id;
      $province = $user_data['province'] ?? '';
      $region_id = $user_region_id;
      $region = $user_data['region'] ?? '';
    } else {
      // Admins can select scope
      $scope_level = trim($_POST['scope_level'] ?? 'ALL');
      $target_id = null;
      $brgy = '';
      $city = '';
      $region = '';
      $province = '';
      $region_id = null;
      $province_id = null;
      $municipality_id = null;
      $barangay_id = null;
    
    // Set target_id and location fields based on scope_level
    if ($scope_level === 'REGION' && isset($_POST['region_id'])) {
      $target_id = (int)$_POST['region_id'];
      $region_id = $target_id;
      $region = trim($_POST['region'] ?? '');
    } elseif ($scope_level === 'PROVINCE' && isset($_POST['province_id'])) {
      $target_id = (int)$_POST['province_id'];
      $province_id = $target_id;
      $province = trim($_POST['province'] ?? '');
      $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null;
      $region = trim($_POST['region'] ?? '');
    } elseif ($scope_level === 'MUNICIPALITY' && isset($_POST['municipality_id'])) {
      $target_id = (int)$_POST['municipality_id'];
      $municipality_id = $target_id;
      $city = trim($_POST['city'] ?? '');
      $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : null;
      $province = trim($_POST['province'] ?? '');
      $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null;
      $region = trim($_POST['region'] ?? '');
    } elseif ($scope_level === 'BARANGAY' && isset($_POST['barangay_id'])) {
      $target_id = (int)$_POST['barangay_id'];
      $barangay_id = $target_id;
      $brgy = trim($_POST['barangay'] ?? '');
      $municipality_id = isset($_POST['municipality_id']) ? (int)$_POST['municipality_id'] : null;
      $city = trim($_POST['city'] ?? '');
      $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : null;
      $province = trim($_POST['province'] ?? '');
      $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null;
      $region = trim($_POST['region'] ?? '');
    }
    }

    if ($title === '' || $content === '') {
      flash('danger', 'Title and content are required.');
      header('Location: /barangayAnnouncement.php');
      exit;
    }

    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
      $file_type = $_FILES['image']['type'];
      $file_size = $_FILES['image']['size'];
      
      if (!in_array($file_type, $allowed)) {
        flash('danger', 'Only JPG, PNG, and GIF images are allowed.');
        header('Location: /barangayAnnouncement.php');
        exit;
      }
      
      if ($file_size > 5 * 1024 * 1024) { // 5MB limit
        flash('danger', 'Image size must be less than 5MB.');
        header('Location: /barangayAnnouncement.php');
        exit;
      }
      
      $upload_dir = __DIR__ . '/uploads/announcements/';
      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
      }
      
      $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
      $filename = 'announcement_' . time() . '_' . uniqid() . '.' . $ext;
      $target_path = $upload_dir . $filename;
      
      if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
        $image_path = 'uploads/announcements/' . $filename;
      } else {
        flash('danger', 'Failed to upload image.');
        header('Location: /barangayAnnouncement.php');
        exit;
      }
    }

    $stmt = $conn->prepare('INSERT INTO announcements (title, content, icon, color, image_path, scope_level, target_id, barangay, barangay_id, cityMunicipality, municipality_id, region, region_id, province, province_id, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->bind_param('ssssssisissisisi', $title, $content, $icon, $color, $image_path, $scope_level, $target_id, $brgy, $barangay_id, $city, $municipality_id, $region, $region_id, $province, $province_id, $user_id);
    if ($stmt->execute()) {
      flash('success', 'Announcement added successfully.');
    } else {
      flash('danger', 'Failed to add announcement: ' . $conn->error);
    }
    $stmt->close();
    header('Location: /barangayAnnouncement.php');
    exit;
  }

  if ($action === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-megaphone-fill');
    $color = trim($_POST['color'] ?? 'primary');
    
    // Officials can only edit announcements for their barangay
    if ($user_role === 'official') {
      $scope_level = 'BARANGAY';
      $target_id = $user_barangay_id;
      $barangay_id = $user_barangay_id;
      $brgy = $user_data['barangay'] ?? '';
      $municipality_id = $user_municipality_id;
      $city = $user_data['cityMunicipality'] ?? '';
      $province_id = $user_province_id;
      $province = $user_data['province'] ?? '';
      $region_id = $user_region_id;
      $region = $user_data['region'] ?? '';
    } else {
      // Admins can modify scope
      $scope_level = trim($_POST['scope_level'] ?? 'ALL');
      $target_id = null;
      $brgy = '';
      $city = '';
      $region = '';
      $province = '';
      $region_id = null;
      $province_id = null;
      $municipality_id = null;
      $barangay_id = null;
    
    // Set target_id and location fields based on scope_level
    if ($scope_level === 'REGION' && isset($_POST['region_id'])) {
      $target_id = (int)$_POST['region_id'];
      $region_id = $target_id;
      $region = trim($_POST['region'] ?? '');
    } elseif ($scope_level === 'PROVINCE' && isset($_POST['province_id'])) {
      $target_id = (int)$_POST['province_id'];
      $province_id = $target_id;
      $province = trim($_POST['province'] ?? '');
      $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null;
      $region = trim($_POST['region'] ?? '');
    } elseif ($scope_level === 'MUNICIPALITY' && isset($_POST['municipality_id'])) {
      $target_id = (int)$_POST['municipality_id'];
      $municipality_id = $target_id;
      $city = trim($_POST['city'] ?? '');
      $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : null;
      $province = trim($_POST['province'] ?? '');
      $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null;
      $region = trim($_POST['region'] ?? '');
    } elseif ($scope_level === 'BARANGAY' && isset($_POST['barangay_id'])) {
      $target_id = (int)$_POST['barangay_id'];
      $barangay_id = $target_id;
      $brgy = trim($_POST['barangay'] ?? '');
      $municipality_id = isset($_POST['municipality_id']) ? (int)$_POST['municipality_id'] : null;
      $city = trim($_POST['city'] ?? '');
      $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : null;
      $province = trim($_POST['province'] ?? '');
      $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null;
      $region = trim($_POST['region'] ?? '');
    }
    }

    if ($id <= 0 || $title === '' || $content === '') {
      flash('danger', 'Invalid data.');
      header('Location: /barangayAnnouncement.php');
      exit;
    }

    // Get current image path
    $stmt_img = $conn->prepare('SELECT image_path FROM announcements WHERE id=?');
    $stmt_img->bind_param('i', $id);
    $stmt_img->execute();
    $result_img = $stmt_img->get_result();
    $current_data = $result_img->fetch_assoc();
    $old_image = $current_data['image_path'] ?? null;
    $stmt_img->close();

    $image_path = $old_image; // Keep existing image by default

    // Handle remove image checkbox
    if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
      if ($old_image && file_exists(__DIR__ . '/' . $old_image)) {
        unlink(__DIR__ . '/' . $old_image);
      }
      $image_path = null;
    }
    // Handle new image upload
    elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
      $file_type = $_FILES['image']['type'];
      $file_size = $_FILES['image']['size'];
      
      if (!in_array($file_type, $allowed)) {
        flash('danger', 'Only JPG, PNG, and GIF images are allowed.');
        header('Location: /barangayAnnouncement.php');
        exit;
      }
      
      if ($file_size > 5 * 1024 * 1024) { // 5MB limit
        flash('danger', 'Image size must be less than 5MB.');
        header('Location: /barangayAnnouncement.php');
        exit;
      }
      
      $upload_dir = __DIR__ . '/uploads/announcements/';
      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
      }
      
      // Delete old image if exists
      if ($old_image && file_exists(__DIR__ . '/' . $old_image)) {
        unlink(__DIR__ . '/' . $old_image);
      }
      
      $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
      $filename = 'announcement_' . time() . '_' . uniqid() . '.' . $ext;
      $target_path = $upload_dir . $filename;
      
      if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
        $image_path = 'uploads/announcements/' . $filename;
      } else {
        flash('danger', 'Failed to upload image.');
        header('Location: /barangayAnnouncement.php');
        exit;
      }
    }

    $stmt = $conn->prepare('UPDATE announcements SET title=?, content=?, icon=?, color=?, image_path=?, scope_level=?, target_id=?, barangay=?, barangay_id=?, cityMunicipality=?, municipality_id=?, region=?, region_id=?, province=?, province_id=? WHERE id=?');
    $stmt->bind_param('ssssssississisii', $title, $content, $icon, $color, $image_path, $scope_level, $target_id, $brgy, $barangay_id, $city, $municipality_id, $region, $region_id, $province, $province_id, $id);
    if ($stmt->execute()) {
      flash('success', 'Announcement updated successfully.');
    } else {
      flash('danger', 'Failed to update announcement: ' . $conn->error);
    }
    $stmt->close();
    header('Location: /barangayAnnouncement.php');
    exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      flash('danger', 'Invalid announcement ID.');
      header('Location: /barangayAnnouncement.php');
      exit;
    }
    
    // Get image path before deleting record
    $stmt_img = $conn->prepare('SELECT image_path FROM announcements WHERE id=?');
    $stmt_img->bind_param('i', $id);
    $stmt_img->execute();
    $result_img = $stmt_img->get_result();
    $img_data = $result_img->fetch_assoc();
    $image_path = $img_data['image_path'] ?? null;
    $stmt_img->close();
    
    // Delete the announcement
    $stmt = $conn->prepare('DELETE FROM announcements WHERE id=?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
      // Delete image file if exists
      if ($image_path && file_exists(__DIR__ . '/' . $image_path)) {
        unlink(__DIR__ . '/' . $image_path);
      }
      flash('success', 'Announcement deleted successfully.');
    } else {
      flash('danger', 'Failed to delete announcement: ' . $conn->error);
    }
    $stmt->close();
    header('Location: /barangayAnnouncement.php');
    exit;
  }
}

// Filter announcements based on role
if ($user_role === 'official') {
  // Officials see only their barangay announcements
  $stmt_ann = $conn->prepare('SELECT id, title, content, icon, color, image_path, scope_level, target_id, barangay, barangay_id, cityMunicipality, municipality_id, region, region_id, province, province_id, createdAt FROM announcements WHERE barangay_id = ? ORDER BY createdAt DESC');
  $stmt_ann->bind_param('i', $user_barangay_id);
  $stmt_ann->execute();
  $announcements = $stmt_ann->get_result();
} else {
  // Admins see all announcements
  $announcements = $conn->query('SELECT id, title, content, icon, color, image_path, scope_level, target_id, barangay, barangay_id, cityMunicipality, municipality_id, region, region_id, province, province_id, createdAt FROM announcements ORDER BY createdAt DESC');
}

$edit_announcement = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  if ($eid > 0) {
    $st = $conn->prepare('SELECT id, title, content, icon, color, image_path, scope_level, target_id, barangay, barangay_id, cityMunicipality, municipality_id, region, region_id, province, province_id FROM announcements WHERE id=?');
    $st->bind_param('i', $eid);
    $st->execute();
    $res = $st->get_result();
    $edit_announcement = $res->fetch_assoc() ?: null;
    $st->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Announcements - CommServe</title>
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
      <a class="navbar-brand fw-bold" href="<?= $user_role === 'admin' ? '/dashboards/adminDashboard.php' : '/dashboards/officialDashboard.php' ?>"><i class="bi bi-megaphone-fill me-2"></i>Barangay <?= $barangay ?></a>
      <div class="d-flex">
        <a class="nav-link text-white" href="<?= $user_role === 'admin' ? '/dashboards/adminDashboard.php' : '/dashboards/officialDashboard.php' ?>"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
      </div>
    </div>
  </nav>

  <div class="container py-5">
    <h2 class="text-center mb-4"><i class="bi bi-megaphone-fill me-2"></i>Manage Barangay Announcements</h2>

    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['type']) ?>">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
      <div class="card-header bg-dark text-white">Add Announcement</div>
      <div class="card-body">
        <form method="post" action="/barangayAnnouncement.php" class="row g-3" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="action" value="add">
          <div class="col-md-12">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="col-md-12">
            <label class="form-label">Content</label>
            <textarea name="content" class="form-control" rows="3" required></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">Icon</label>
            <select name="icon" class="form-select">
              <option value="bi-megaphone-fill">📢 Megaphone</option>
              <option value="bi-exclamation-circle-fill">⚠️ Exclamation</option>
              <option value="bi-shield-fill-check">🛡️ Shield</option>
              <option value="bi-people-fill">👥 People</option>
              <option value="bi-droplet-fill">💧 Water</option>
              <option value="bi-tree-fill">🌳 Tree</option>
              <option value="bi-lightning-fill">⚡ Lightning</option>
              <option value="bi-heart-fill">❤️ Heart</option>
              <option value="bi-calendar-event-fill">📅 Calendar</option>
              <option value="bi-flag-fill">🚩 Flag</option>
              <option value="bi-bell-fill">🔔 Bell</option>
              <option value="bi-info-circle-fill">ℹ️ Info</option>
              <option value="bi-check-circle-fill">✅ Check</option>
              <option value="bi-x-circle-fill">❌ X-Circle</option>
              <option value="bi-house-fill">🏠 House</option>
              <option value="bi-building-fill">🏢 Building</option>
              <option value="bi-car-front-fill">🚗 Car</option>
              <option value="bi-trash-fill">🗑️ Trash</option>
              <option value="bi-recycle">♻️ Recycle</option>
              <option value="bi-hospital-fill">🏥 Hospital</option>
              <option value="bi-mortarboard-fill">🎓 Education</option>
              <option value="bi-trophy-fill">🏆 Trophy</option>
              <option value="bi-sun-fill">☀️ Sun</option>
              <option value="bi-cloud-rain-fill">🌧️ Rain</option>
              <option value="bi-fire">🔥 Fire</option>
              <option value="bi-hammer">🔨 Construction</option>
              <option value="bi-wrench">🔧 Wrench</option>
              <option value="bi-wifi">📶 WiFi</option>
              <option value="bi-broadcast">📡 Broadcast</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Color</label>
            <select name="color" class="form-select">
              <option value="primary">Primary</option>
              <option value="danger">Danger</option>
              <option value="success">Success</option>
              <option value="warning">Warning</option>
              <option value="info">Info</option>
            </select>
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
              <strong>Posting to:</strong> Barangay <?= htmlspecialchars($user_data['barangay'] ?? 'N/A') ?>, 
              <?= htmlspecialchars($user_data['cityMunicipality'] ?? 'N/A') ?>
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
          <div class="col-md-12">
            <label class="form-label">Upload Image/Poster (Optional)</label>
            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/gif,image/jpg">
            <small class="text-muted">Max 5MB. JPG, PNG, or GIF only.</small>
          </div>
          <div class="col-12 text-end">
            <button type="submit" class="btn btn-black">Save Announcement</button>
          </div>
        </form>
      </div>
    </div>

    <?php if ($edit_announcement): ?>
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">Edit Announcement</div>
        <div class="card-body">
          <form method="post" action="/barangayAnnouncement.php" class="row g-3" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= (int)$edit_announcement['id'] ?>">
            <div class="col-md-12">
              <label class="form-label">Title</label>
              <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($edit_announcement['title']) ?>" required>
            </div>
            <div class="col-md-12">
              <label class="form-label">Content</label>
              <textarea name="content" class="form-control" rows="3" required><?= htmlspecialchars($edit_announcement['content']) ?></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Icon</label>
              <select name="icon" class="form-select">
                <option value="bi-megaphone-fill" <?= $edit_announcement['icon']==='bi-megaphone-fill'?'selected':'' ?>>📢 Megaphone</option>
                <option value="bi-exclamation-circle-fill" <?= $edit_announcement['icon']==='bi-exclamation-circle-fill'?'selected':'' ?>>⚠️ Exclamation</option>
                <option value="bi-shield-fill-check" <?= $edit_announcement['icon']==='bi-shield-fill-check'?'selected':'' ?>>🛡️ Shield</option>
                <option value="bi-people-fill" <?= $edit_announcement['icon']==='bi-people-fill'?'selected':'' ?>>👥 People</option>
                <option value="bi-droplet-fill" <?= $edit_announcement['icon']==='bi-droplet-fill'?'selected':'' ?>>💧 Water</option>
                <option value="bi-tree-fill" <?= $edit_announcement['icon']==='bi-tree-fill'?'selected':'' ?>>🌳 Tree</option>
                <option value="bi-lightning-fill" <?= $edit_announcement['icon']==='bi-lightning-fill'?'selected':'' ?>>⚡ Lightning</option>
                <option value="bi-heart-fill" <?= $edit_announcement['icon']==='bi-heart-fill'?'selected':'' ?>>❤️ Heart</option>
                <option value="bi-calendar-event-fill" <?= $edit_announcement['icon']==='bi-calendar-event-fill'?'selected':'' ?>>📅 Calendar</option>
                <option value="bi-flag-fill" <?= $edit_announcement['icon']==='bi-flag-fill'?'selected':'' ?>>🚩 Flag</option>
                <option value="bi-bell-fill" <?= $edit_announcement['icon']==='bi-bell-fill'?'selected':'' ?>>🔔 Bell</option>
                <option value="bi-info-circle-fill" <?= $edit_announcement['icon']==='bi-info-circle-fill'?'selected':'' ?>>ℹ️ Info</option>
                <option value="bi-check-circle-fill" <?= $edit_announcement['icon']==='bi-check-circle-fill'?'selected':'' ?>>✅ Check</option>
                <option value="bi-x-circle-fill" <?= $edit_announcement['icon']==='bi-x-circle-fill'?'selected':'' ?>>❌ X-Circle</option>
                <option value="bi-house-fill" <?= $edit_announcement['icon']==='bi-house-fill'?'selected':'' ?>>🏠 House</option>
                <option value="bi-building-fill" <?= $edit_announcement['icon']==='bi-building-fill'?'selected':'' ?>>🏢 Building</option>
                <option value="bi-car-front-fill" <?= $edit_announcement['icon']==='bi-car-front-fill'?'selected':'' ?>>🚗 Car</option>
                <option value="bi-trash-fill" <?= $edit_announcement['icon']==='bi-trash-fill'?'selected':'' ?>>🗑️ Trash</option>
                <option value="bi-recycle" <?= $edit_announcement['icon']==='bi-recycle'?'selected':'' ?>>♻️ Recycle</option>
                <option value="bi-hospital-fill" <?= $edit_announcement['icon']==='bi-hospital-fill'?'selected':'' ?>>🏥 Hospital</option>
                <option value="bi-mortarboard-fill" <?= $edit_announcement['icon']==='bi-mortarboard-fill'?'selected':'' ?>>🎓 Education</option>
                <option value="bi-trophy-fill" <?= $edit_announcement['icon']==='bi-trophy-fill'?'selected':'' ?>>🏆 Trophy</option>
                <option value="bi-sun-fill" <?= $edit_announcement['icon']==='bi-sun-fill'?'selected':'' ?>>☀️ Sun</option>
                <option value="bi-cloud-rain-fill" <?= $edit_announcement['icon']==='bi-cloud-rain-fill'?'selected':'' ?>>🌧️ Rain</option>
                <option value="bi-fire" <?= $edit_announcement['icon']==='bi-fire'?'selected':'' ?>>🔥 Fire</option>
                <option value="bi-hammer" <?= $edit_announcement['icon']==='bi-hammer'?'selected':'' ?>>🔨 Construction</option>
                <option value="bi-wrench" <?= $edit_announcement['icon']==='bi-wrench'?'selected':'' ?>>🔧 Wrench</option>
                <option value="bi-wifi" <?= $edit_announcement['icon']==='bi-wifi'?'selected':'' ?>>📶 WiFi</option>
                <option value="bi-broadcast" <?= $edit_announcement['icon']==='bi-broadcast'?'selected':'' ?>>📡 Broadcast</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Color</label>
              <select name="color" class="form-select">
                <option value="primary" <?= $edit_announcement['color']==='primary'?'selected':'' ?>>Primary</option>
                <option value="danger" <?= $edit_announcement['color']==='danger'?'selected':'' ?>>Danger</option>
                <option value="success" <?= $edit_announcement['color']==='success'?'selected':'' ?>>Success</option>
                <option value="warning" <?= $edit_announcement['color']==='warning'?'selected':'' ?>>Warning</option>
                <option value="info" <?= $edit_announcement['color']==='info'?'selected':'' ?>>Info</option>
              </select>
            </div>
            <?php if ($user_role === 'admin'): ?>
            <div class="col-md-12">
              <label class="form-label"><i class="bi bi-broadcast me-2"></i>Broadcast Scope</label>
              <select name="scope_level" id="edit_scope_level" class="form-select" required>
                <option value="ALL" <?= ($edit_announcement['scope_level'] ?? 'ALL') === 'ALL' ? 'selected' : '' ?>>🌍 All Locations (Nationwide)</option>
                <option value="REGION" <?= ($edit_announcement['scope_level'] ?? '') === 'REGION' ? 'selected' : '' ?>>📍 Specific Region</option>
                <option value="PROVINCE" <?= ($edit_announcement['scope_level'] ?? '') === 'PROVINCE' ? 'selected' : '' ?>>🏛️ Specific Province</option>
                <option value="MUNICIPALITY" <?= ($edit_announcement['scope_level'] ?? '') === 'MUNICIPALITY' ? 'selected' : '' ?>>🏙️ Specific City/Municipality</option>
                <option value="BARANGAY" <?= ($edit_announcement['scope_level'] ?? '') === 'BARANGAY' ? 'selected' : '' ?>>🏘️ Specific Barangay</option>
              </select>
            </div>
            <?php else: ?>
            <div class="col-md-12">
              <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Posting to:</strong> Barangay <?= htmlspecialchars($user_data['barangay'] ?? 'N/A') ?>, 
                <?= htmlspecialchars($user_data['cityMunicipality'] ?? 'N/A') ?>
              </div>
            </div>
            <?php endif; ?>
            <div id="edit_scope_selector" class="col-md-12">
              <div class="row g-3">
                <div class="col-md-3" id="edit_region_selector" style="display:none;">
                  <label class="form-label">Select Region</label>
                  <select name="region_id" id="edit_region_id" class="form-select">
                    <option value="">Choose Region</option>
                  </select>
                  <input type="hidden" name="region" id="edit_region" value="<?= htmlspecialchars($edit_announcement['region'] ?? '') ?>">
                </div>
                <div class="col-md-3" id="edit_province_selector" style="display:none;">
                  <label class="form-label">Select Province</label>
                  <select name="province_id" id="edit_province_id" class="form-select">
                    <option value="">Select Region first</option>
                  </select>
                  <input type="hidden" name="province" id="edit_province" value="<?= htmlspecialchars($edit_announcement['province'] ?? '') ?>">
                </div>
                <div class="col-md-3" id="edit_municipality_selector" style="display:none;">
                  <label class="form-label">Select Municipality/City</label>
                  <select name="municipality_id" id="edit_municipality_id" class="form-select">
                    <option value="">Select Province first</option>
                  </select>
                  <input type="hidden" name="city" id="edit_city" value="<?= htmlspecialchars($edit_announcement['cityMunicipality'] ?? '') ?>">
                </div>
                <div class="col-md-3" id="edit_barangay_selector" style="display:none;">
                  <label class="form-label">Select Barangay</label>
                  <select name="barangay_id" id="edit_barangay_id" class="form-select">
                    <option value="">Select Municipality first</option>
                  </select>
                  <input type="hidden" name="barangay" id="edit_barangay" value="<?= htmlspecialchars($edit_announcement['barangay'] ?? '') ?>">
                </div>
              </div>
            </div>
            <div class="col-md-12">
              <label class="form-label">Update Image/Poster (Optional)</label>
              <?php if (!empty($edit_announcement['image_path'])): ?>
                <div class="mb-2">
                  <img src="/<?= htmlspecialchars($edit_announcement['image_path']) ?>" alt="Current image" class="img-thumbnail" style="max-width: 400px; height: auto;">
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeImage">
                    <label class="form-check-label" for="removeImage">
                      Remove current image
                    </label>
                  </div>
                </div>
              <?php endif; ?>
              <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/gif,image/jpg">
              <small class="text-muted">Max 5MB. JPG, PNG, or GIF only. Leave empty to keep current image.</small>
            </div>
            <div class="col-12 text-end">
              <a href="/barangayAnnouncement.php" class="btn btn-secondary">Cancel</a>
              <button type="submit" class="btn btn-black">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="card shadow-sm">
      <div class="card-body">
        <div class="row g-4">
          <?php if ($announcements && $announcements->num_rows > 0): ?>
            <?php while ($ann = $announcements->fetch_assoc()): ?>
              <div class="col-12">
                <div class="card shadow-sm">
                  <?php if (!empty($ann['image_path'])): ?>
                    <img src="/<?= htmlspecialchars($ann['image_path']) ?>" class="card-img-top" alt="Announcement image" style="max-width: 600px; height: auto; display: block; margin: 0 auto;">
                  <?php endif; ?>
                  <div class="card-body">
                    <h5 class="card-title fw-bold">
                      <i class="bi <?= htmlspecialchars($ann['icon']) ?> text-<?= htmlspecialchars($ann['color']) ?> me-2"></i>
                      <?= htmlspecialchars($ann['title']) ?>
                    </h5>
                    <p class="card-text"><?= htmlspecialchars($ann['content']) ?></p>
                    <p class="text-muted small mb-1">
                      <i class="bi bi-calendar-event me-1"></i>
                      Posted: <?= date('M d, Y', strtotime($ann['createdAt'])) ?>
                    </p>
                    <p class="text-muted small mb-1">
                      <i class="bi bi-broadcast me-1"></i>
                      <strong>Broadcast Scope:</strong> 
                      <?php 
                      $scope = $ann['scope_level'] ?? 'ALL';
                      if ($scope === 'ALL') {
                        echo '🌍 Nationwide';
                      } elseif ($scope === 'REGION') {
                        echo '📍 Region ' . htmlspecialchars($ann['region'] ?? 'N/A');
                      } elseif ($scope === 'PROVINCE') {
                        echo '🏛️ ' . htmlspecialchars($ann['province'] ?? 'N/A') . ' Province';
                      } elseif ($scope === 'MUNICIPALITY') {
                        echo '🏙️ ' . htmlspecialchars($ann['cityMunicipality'] ?? 'N/A');
                      } elseif ($scope === 'BARANGAY') {
                        echo '🏘️ Barangay ' . htmlspecialchars($ann['barangay'] ?? 'N/A');
                      }
                      ?>
                    </p>
                    <?php if ($scope !== 'ALL' && (!empty($ann['region']) || !empty($ann['province']) || !empty($ann['cityMunicipality']) || !empty($ann['barangay']))): ?>
                      <p class="text-muted small mb-3">
                        <i class="bi bi-geo-alt-fill me-1"></i>
                        <?php 
                        $location_parts = [];
                        if (!empty($ann['barangay'])) $location_parts[] = 'Brgy. ' . htmlspecialchars($ann['barangay']);
                        if (!empty($ann['cityMunicipality'])) $location_parts[] = htmlspecialchars($ann['cityMunicipality']);
                        if (!empty($ann['province'])) $location_parts[] = htmlspecialchars($ann['province']);
                        if (!empty($ann['region'])) $location_parts[] = 'Region ' . htmlspecialchars($ann['region']);
                        echo implode(', ', $location_parts);
                        ?>
                      </p>
                    <?php else: ?>
                      <p class="text-muted small mb-3"></p>
                    <?php endif; ?>
                    <div class="d-flex gap-2">
                      <a class="btn btn-sm btn-black" href="/barangayAnnouncement.php?edit=<?= (int)$ann['id'] ?>">
                        <i class="bi bi-pencil-square"></i> Edit
                      </a>
                      <form method="post" action="/barangayAnnouncement.php" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$ann['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this announcement?')">
                          <i class="bi bi-trash"></i> Delete
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="col-12">
              <p class="text-muted text-center">No announcements yet. Add one above!</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <footer class="text-white text-center py-4 bg-dark mt-5">
    <p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    <?php if ($user_role === 'admin'): ?>
    loadRegions('add');
    
    // Setup scope level change handlers (admin only)
    const addScope = document.getElementById('add_scope_level');
    if (addScope) {
      addScope.addEventListener('change', function() {
        handleScopeChange('add', this.value);
      });
    }
    
    <?php if ($edit_announcement): ?>
    loadRegions('edit', <?= (int)($edit_announcement['region_id'] ?? 0) ?>, <?= (int)($edit_announcement['province_id'] ?? 0) ?>, <?= (int)($edit_announcement['municipality_id'] ?? 0) ?>, <?= (int)($edit_announcement['barangay_id'] ?? 0) ?>);
    const editScope = document.getElementById('edit_scope_level');
    if (editScope) {
      editScope.addEventListener('change', function() {
        handleScopeChange('edit', this.value);
      });
      // Trigger initial scope display for edit mode
      handleScopeChange('edit', '<?= $edit_announcement['scope_level'] ?? 'ALL' ?>');
    }
    <?php endif; ?>
    <?php endif; ?>
  });
  
  <?php if ($user_role === 'admin'): ?>
  function handleScopeChange(mode, scope) {
    const prefix = mode + '_';
    const selector = document.getElementById(prefix + 'scope_selector');
    const regionDiv = document.getElementById(prefix + 'region_selector');
    const provinceDiv = document.getElementById(prefix + 'province_selector');
    const municipalityDiv = document.getElementById(prefix + 'municipality_selector');
    const barangayDiv = document.getElementById(prefix + 'barangay_selector');
    
    // Hide all selectors first
    selector.style.display = 'none';
    regionDiv.style.display = 'none';
    provinceDiv.style.display = 'none';
    municipalityDiv.style.display = 'none';
    barangayDiv.style.display = 'none';
    
    if (scope === 'ALL') {
      return; // No selectors needed for nationwide
    }
    
    selector.style.display = 'block';
    
    if (scope === 'REGION') {
      regionDiv.style.display = 'block';
    } else if (scope === 'PROVINCE') {
      regionDiv.style.display = 'block';
      provinceDiv.style.display = 'block';
    } else if (scope === 'MUNICIPALITY') {
      regionDiv.style.display = 'block';
      provinceDiv.style.display = 'block';
      municipalityDiv.style.display = 'block';
    } else if (scope === 'BARANGAY') {
      regionDiv.style.display = 'block';
      provinceDiv.style.display = 'block';
      municipalityDiv.style.display = 'block';
      barangayDiv.style.display = 'block';
    }
  }
  <?php endif; ?>

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
          if (selectedRegion) loadProvinces(mode, selectedRegion, selectedProvince, selectedMunicipality, selectedBarangay);
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
          if (selectedProvince) loadMunicipalities(mode, selectedProvince, selectedMunicipality, selectedBarangay);
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
          if (selectedMunicipality) loadBarangays(mode, selectedMunicipality, selectedBarangay);
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