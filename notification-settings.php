<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';

// Guard - redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: /pages/loginPage.php');
  exit;
}

$user_id = $_SESSION['user_id'];

// Flash message helper
function flash($type, $message) {
  $_SESSION['flash_type'] = $type;
  $_SESSION['flash_message'] = $message;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_preferences'])) {
  $announcements = isset($_POST['announcements']) ? 1 : 0;
  $hotlines = isset($_POST['hotlines']) ? 1 : 0;
  $documents = isset($_POST['documents']) ? 1 : 0;
  $events = isset($_POST['events']) ? 1 : 0;
  $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
  $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;

  // Check if user preferences table exists, if not create it
  $conn->query("CREATE TABLE IF NOT EXISTS user_notification_preferences (
    user_id INT PRIMARY KEY,
    announcements TINYINT(1) DEFAULT 1,
    hotlines TINYINT(1) DEFAULT 1,
    documents TINYINT(1) DEFAULT 1,
    events TINYINT(1) DEFAULT 1,
    email_notifications TINYINT(1) DEFAULT 1,
    push_notifications TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  )");

  // Insert or update preferences
  $stmt = $conn->prepare("INSERT INTO user_notification_preferences 
    (user_id, announcements, hotlines, documents, events, email_notifications, push_notifications) 
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
    announcements=?, hotlines=?, documents=?, events=?, email_notifications=?, push_notifications=?");
  
  $stmt->bind_param('iiiiiiiiiiiii', 
    $user_id, $announcements, $hotlines, $documents, $events, $email_notifications, $push_notifications,
    $announcements, $hotlines, $documents, $events, $email_notifications, $push_notifications);

  if ($stmt->execute()) {
    flash('success', 'Notification preferences saved successfully!');
  } else {
    flash('danger', 'Failed to save preferences: ' . $conn->error);
  }
  $stmt->close();
  header('Location: /notification-settings.php');
  exit;
}

// Get flash messages
$flash_type = $_SESSION['flash_type'] ?? '';
$flash_message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_type'], $_SESSION['flash_message']);

// Get user data
$stmt = $conn->prepare("SELECT role, barangay FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$role = $user['role'] ?? 'user';
$barangay = htmlspecialchars($user['barangay'] ?? 'Barangay', ENT_QUOTES, 'UTF-8');

switch ($role) {
  case 'admin':
    $role_display = 'Barangay ' . $barangay . ' Admin';
    $dashboard_link = '/dashboards/adminDashboard.php';
    $navbar_icon = 'bi-shield-check';
    break;
  case 'official':
    $role_display = 'Barangay ' . $barangay . ' Official';
    $dashboard_link = '/dashboards/officialDashboard.php';
    $navbar_icon = 'bi-briefcase';
    break;
  default:
    $role_display = 'Barangay ' . $barangay;
    $dashboard_link = '/dashboards/userDashboard.php';
    $navbar_icon = 'bi-house-fill';
}

// Get current notification preferences
$conn->query("CREATE TABLE IF NOT EXISTS user_notification_preferences (
  user_id INT PRIMARY KEY,
  announcements TINYINT(1) DEFAULT 1,
  hotlines TINYINT(1) DEFAULT 1,
  documents TINYINT(1) DEFAULT 1,
  events TINYINT(1) DEFAULT 1,
  email_notifications TINYINT(1) DEFAULT 1,
  push_notifications TINYINT(1) DEFAULT 1,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$prefs_query = $conn->prepare("SELECT * FROM user_notification_preferences WHERE user_id = ?");
$prefs_query->bind_param("i", $user_id);
$prefs_query->execute();
$prefs_result = $prefs_query->get_result();
$prefs = $prefs_result->fetch_assoc();
$prefs_query->close();

// Default to all enabled if no preferences set
$announcements = $prefs['announcements'] ?? 1;
$hotlines = $prefs['hotlines'] ?? 1;
$documents = $prefs['documents'] ?? 1;
$events = $prefs['events'] ?? 1;
$email_notifications = $prefs['email_notifications'] ?? 1;
$push_notifications = $prefs['push_notifications'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notification Settings - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      padding-bottom: 80px;
    }
    .settings-container {
      max-width: 900px;
      margin: 50px auto;
    }
    .settings-card {
      border-radius: 15px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .notification-item {
      padding: 20px;
      border-bottom: 1px solid #dee2e6;
      transition: background-color 0.2s;
    }
    .notification-item:last-child {
      border-bottom: none;
    }
    .notification-item:hover {
      background-color: #f8f9fa;
    }
    .form-check-input {
      width: 3em;
      height: 1.5em;
      cursor: pointer;
    }
    footer {
      position: fixed;
      left: 0;
      bottom: 0;
      width: 100%;
      z-index: 999;
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="<?= $dashboard_link ?>">
        <i class="bi <?= $navbar_icon ?> me-2"></i><?= $role_display ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="/settings.php">
              <i class="bi bi-arrow-left me-1"></i>Back to Settings
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Settings Content -->
  <div class="container settings-container">
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-bell-fill text-dark me-3" style="font-size: 2.5rem;"></i>
      <h2 class="mb-0">Notification Preferences</h2>
    </div>

    <?php if (!empty($flash_message)): ?>
      <div class="alert alert-<?= $flash_type ?> alert-dismissible fade show" role="alert">
        <i class="bi <?= $flash_type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
        <?= htmlspecialchars($flash_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST" action="/notification-settings.php">
      <!-- Content Notifications -->
      <div class="card settings-card mb-4">
        <div class="card-body p-0">
          <div class="p-4 border-bottom">
            <h5 class="mb-1">Content Notifications</h5>
            <p class="text-muted mb-0 small">Choose which types of content you want to be notified about</p>
          </div>

          <div class="notification-item">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-1"><i class="bi bi-megaphone me-2 text-primary"></i>Announcements</h6>
                <p class="text-muted mb-0 small">Get notified when new announcements are posted</p>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="announcements" name="announcements" <?= $announcements ? 'checked' : '' ?>>
              </div>
            </div>
          </div>

          <div class="notification-item">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-1"><i class="bi bi-telephone me-2 text-danger"></i>Emergency Hotlines</h6>
                <p class="text-muted mb-0 small">Get notified about new or updated emergency hotlines</p>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="hotlines" name="hotlines" <?= $hotlines ? 'checked' : '' ?>>
              </div>
            </div>
          </div>

          <div class="notification-item">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-1"><i class="bi bi-file-earmark-text me-2 text-success"></i>Document Requests</h6>
                <p class="text-muted mb-0 small">Get notified about your document request status updates</p>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="documents" name="documents" <?= $documents ? 'checked' : '' ?>>
              </div>
            </div>
          </div>

          <div class="notification-item">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-1"><i class="bi bi-calendar-event me-2 text-warning"></i>Events & Activities</h6>
                <p class="text-muted mb-0 small">Get notified about upcoming barangay events and activities</p>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="events" name="events" <?= $events ? 'checked' : '' ?>>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Delivery Methods -->
      <div class="card settings-card mb-4">
        <div class="card-body p-0">
          <div class="p-4 border-bottom">
            <h5 class="mb-1">Notification Delivery</h5>
            <p class="text-muted mb-0 small">Choose how you want to receive notifications</p>
          </div>

          <div class="notification-item">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-1"><i class="bi bi-envelope me-2 text-info"></i>Email Notifications</h6>
                <p class="text-muted mb-0 small">Receive notifications via email</p>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" <?= $email_notifications ? 'checked' : '' ?>>
              </div>
            </div>
          </div>

          <div class="notification-item">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-1"><i class="bi bi-app-indicator me-2 text-secondary"></i>Push Notifications</h6>
                <p class="text-muted mb-0 small">Receive push notifications in your browser</p>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="push_notifications" name="push_notifications" <?= $push_notifications ? 'checked' : '' ?>>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="/settings.php" class="btn btn-outline-secondary">
          <i class="bi bi-x-circle me-2"></i>Cancel
        </a>
        <button type="submit" name="save_preferences" class="btn btn-dark">
          <i class="bi bi-save me-2"></i>Save Preferences
        </button>
      </div>
    </form>
  </div>

  <!-- Footer -->
  <footer class="text-white text-center py-4 bg-dark">
    <p class="mb-0">&copy; 2025 CommServe. All rights reserved.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
