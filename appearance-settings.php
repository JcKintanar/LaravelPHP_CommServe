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

// Handle appearance change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_appearance'])) {
  $theme = $_POST['theme'] ?? 'light';
  $font_size = $_POST['font_size'] ?? 'medium';
  $compact_mode = isset($_POST['compact_mode']) ? 1 : 0;

  // Validate theme
  $allowed_themes = ['light', 'dark', 'auto'];
  if (!in_array($theme, $allowed_themes)) {
    $theme = 'light';
  }

  // Validate font size
  $allowed_font_sizes = ['small', 'medium', 'large'];
  if (!in_array($font_size, $allowed_font_sizes)) {
    $font_size = 'medium';
  }

  // Check if user preferences table exists, if not create it
  $conn->query("CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INT PRIMARY KEY,
    language VARCHAR(10) DEFAULT 'en',
    theme VARCHAR(20) DEFAULT 'light',
    font_size VARCHAR(10) DEFAULT 'medium',
    compact_mode TINYINT(1) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  )");

  // Insert or update appearance preferences
  $stmt = $conn->prepare("INSERT INTO user_preferences (user_id, theme, font_size, compact_mode) 
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE theme = ?, font_size = ?, compact_mode = ?");
  $stmt->bind_param('ississi', $user_id, $theme, $font_size, $compact_mode, $theme, $font_size, $compact_mode);

  if ($stmt->execute()) {
    $_SESSION['theme'] = $theme;
    $_SESSION['font_size'] = $font_size;
    $_SESSION['compact_mode'] = $compact_mode;
    flash('success', 'Appearance settings saved successfully!');
  } else {
    flash('danger', 'Failed to save appearance settings: ' . $conn->error);
  }
  $stmt->close();
  header('Location: /appearance-settings.php');
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

// Get current appearance preferences
$conn->query("CREATE TABLE IF NOT EXISTS user_preferences (
  user_id INT PRIMARY KEY,
  language VARCHAR(10) DEFAULT 'en',
  theme VARCHAR(20) DEFAULT 'light',
  font_size VARCHAR(10) DEFAULT 'medium',
  compact_mode TINYINT(1) DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$prefs_query = $conn->prepare("SELECT theme, font_size, compact_mode FROM user_preferences WHERE user_id = ?");
$prefs_query->bind_param("i", $user_id);
$prefs_query->execute();
$prefs_result = $prefs_query->get_result();
$prefs = $prefs_result->fetch_assoc();
$prefs_query->close();

$current_theme = $prefs['theme'] ?? $_SESSION['theme'] ?? 'light';
$current_font_size = $prefs['font_size'] ?? $_SESSION['font_size'] ?? 'medium';
$current_compact_mode = $prefs['compact_mode'] ?? $_SESSION['compact_mode'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Appearance Settings - CommServe</title>
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
    .theme-option {
      padding: 20px;
      border: 2px solid #dee2e6;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s;
      margin-bottom: 15px;
      position: relative;
    }
    .theme-option:hover {
      border-color: #6c757d;
      transform: translateY(-2px);
    }
    .theme-option.selected {
      border-color: #212529;
      background-color: #e9ecef;
    }
    .theme-option input[type="radio"] {
      width: 1.3em;
      height: 1.3em;
      cursor: pointer;
    }
    .theme-preview {
      width: 100%;
      height: 80px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 10px;
      border: 1px solid #dee2e6;
    }
    .theme-preview.light {
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      color: #212529;
    }
    .theme-preview.dark {
      background: linear-gradient(135deg, #212529 0%, #343a40 100%);
      color: #ffffff;
    }
    .theme-preview.auto {
      background: linear-gradient(90deg, #ffffff 0%, #ffffff 50%, #212529 50%, #212529 100%);
      color: #212529;
    }
    .font-size-option {
      padding: 15px;
      border: 2px solid #dee2e6;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s;
      text-align: center;
    }
    .font-size-option:hover {
      border-color: #6c757d;
    }
    .font-size-option.selected {
      border-color: #212529;
      background-color: #e9ecef;
    }
    .font-size-option input[type="radio"] {
      display: none;
    }
    .font-preview {
      font-weight: 600;
      margin-bottom: 5px;
    }
    .font-preview.small { font-size: 0.875rem; }
    .font-preview.medium { font-size: 1rem; }
    .font-preview.large { font-size: 1.25rem; }
    .form-switch .form-check-input {
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
      <i class="bi bi-palette-fill text-dark me-3" style="font-size: 2.5rem;"></i>
      <h2 class="mb-0">Appearance Settings</h2>
    </div>

    <?php if (!empty($flash_message)): ?>
      <div class="alert alert-<?= $flash_type ?> alert-dismissible fade show" role="alert">
        <i class="bi <?= $flash_type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
        <?= htmlspecialchars($flash_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST" action="/appearance-settings.php" id="appearanceForm">
      <!-- Theme Selection -->
      <div class="card settings-card mb-4">
        <div class="card-body p-4">
          <h5 class="card-title mb-3">
            <i class="bi bi-brightness-high me-2"></i>Theme
          </h5>
          <p class="text-muted mb-4">Choose how CommServe looks to you</p>
          
          <div class="row g-3">
            <!-- Light Theme -->
            <div class="col-md-4">
              <label for="theme_light" class="theme-option <?= $current_theme === 'light' ? 'selected' : '' ?>">
                <div class="theme-preview light">
                  <i class="bi bi-sun-fill" style="font-size: 2rem;"></i>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <h6 class="mb-0">Light</h6>
                    <small class="text-muted">Bright and clear</small>
                  </div>
                  <input type="radio" id="theme_light" name="theme" value="light" 
                    <?= $current_theme === 'light' ? 'checked' : '' ?> 
                    onchange="selectTheme(this)">
                </div>
              </label>
            </div>

            <!-- Dark Theme -->
            <div class="col-md-4">
              <label for="theme_dark" class="theme-option <?= $current_theme === 'dark' ? 'selected' : '' ?>">
                <div class="theme-preview dark">
                  <i class="bi bi-moon-stars-fill" style="font-size: 2rem;"></i>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <h6 class="mb-0">Dark</h6>
                    <small class="text-muted">Easy on the eyes</small>
                  </div>
                  <input type="radio" id="theme_dark" name="theme" value="dark" 
                    <?= $current_theme === 'dark' ? 'checked' : '' ?> 
                    onchange="selectTheme(this)">
                </div>
              </label>
            </div>

            <!-- Auto Theme -->
            <div class="col-md-4">
              <label for="theme_auto" class="theme-option <?= $current_theme === 'auto' ? 'selected' : '' ?>">
                <div class="theme-preview auto">
                  <i class="bi bi-circle-half" style="font-size: 2rem;"></i>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <h6 class="mb-0">Auto</h6>
                    <small class="text-muted">Matches system</small>
                  </div>
                  <input type="radio" id="theme_auto" name="theme" value="auto" 
                    <?= $current_theme === 'auto' ? 'checked' : '' ?> 
                    onchange="selectTheme(this)">
                </div>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Font Size -->
      <div class="card settings-card mb-4">
        <div class="card-body p-4">
          <h5 class="card-title mb-3">
            <i class="bi bi-fonts me-2"></i>Font Size
          </h5>
          <p class="text-muted mb-4">Adjust text size for better readability</p>
          
          <div class="row g-3">
            <!-- Small -->
            <div class="col-md-4">
              <label for="font_small" class="font-size-option <?= $current_font_size === 'small' ? 'selected' : '' ?>">
                <div class="font-preview small">Aa</div>
                <div>Small</div>
                <input type="radio" id="font_small" name="font_size" value="small" 
                  <?= $current_font_size === 'small' ? 'checked' : '' ?> 
                  onchange="selectFontSize(this)">
              </label>
            </div>

            <!-- Medium -->
            <div class="col-md-4">
              <label for="font_medium" class="font-size-option <?= $current_font_size === 'medium' ? 'selected' : '' ?>">
                <div class="font-preview medium">Aa</div>
                <div>Medium</div>
                <input type="radio" id="font_medium" name="font_size" value="medium" 
                  <?= $current_font_size === 'medium' ? 'checked' : '' ?> 
                  onchange="selectFontSize(this)">
              </label>
            </div>

            <!-- Large -->
            <div class="col-md-4">
              <label for="font_large" class="font-size-option <?= $current_font_size === 'large' ? 'selected' : '' ?>">
                <div class="font-preview large">Aa</div>
                <div>Large</div>
                <input type="radio" id="font_large" name="font_size" value="large" 
                  <?= $current_font_size === 'large' ? 'checked' : '' ?> 
                  onchange="selectFontSize(this)">
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Display Options -->
      <div class="card settings-card mb-4">
        <div class="card-body p-4">
          <h5 class="card-title mb-3">
            <i class="bi bi-sliders me-2"></i>Display Options
          </h5>
          
          <div class="d-flex justify-content-between align-items-center p-3 border rounded mb-3">
            <div>
              <h6 class="mb-1">Compact Mode</h6>
              <p class="text-muted mb-0 small">Reduce spacing between elements</p>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="compact_mode" name="compact_mode" 
                <?= $current_compact_mode ? 'checked' : '' ?>>
            </div>
          </div>

          <div class="alert alert-info mb-0">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Note:</strong> Theme and display changes will take effect after saving and may require a page refresh.
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex justify-content-between">
        <a href="/settings.php" class="btn btn-outline-secondary">
          <i class="bi bi-x-circle me-2"></i>Cancel
        </a>
        <button type="submit" name="save_appearance" class="btn btn-dark">
          <i class="bi bi-save me-2"></i>Save Appearance Settings
        </button>
      </div>
    </form>
  </div>

  <!-- Footer -->
  <footer class="text-white text-center py-4 bg-dark">
    <p class="mb-0">&copy; 2025 CommServe. All rights reserved.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function selectTheme(radio) {
      document.querySelectorAll('.theme-option').forEach(option => {
        option.classList.remove('selected');
      });
      radio.closest('.theme-option').classList.add('selected');
    }

    function selectFontSize(radio) {
      document.querySelectorAll('.font-size-option').forEach(option => {
        option.classList.remove('selected');
      });
      radio.closest('.font-size-option').classList.add('selected');
    }
  </script>
</body>
</html>
