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

// Handle language change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_language'])) {
  $language = $_POST['language'] ?? 'en';

  // Validate language
  $allowed_languages = ['en', 'ceb', 'tl'];
  if (!in_array($language, $allowed_languages)) {
    $language = 'en';
  }

  // Check if user preferences table exists, if not create it
  $conn->query("CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INT PRIMARY KEY,
    language VARCHAR(10) DEFAULT 'en',
    theme VARCHAR(20) DEFAULT 'light',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  )");

  // Insert or update language preference
  $stmt = $conn->prepare("INSERT INTO user_preferences (user_id, language) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE language = ?");
  $stmt->bind_param('iss', $user_id, $language, $language);

  if ($stmt->execute()) {
    $_SESSION['language'] = $language;
    flash('success', 'Language preference saved successfully!');
  } else {
    flash('danger', 'Failed to save language preference: ' . $conn->error);
  }
  $stmt->close();
  header('Location: /language-settings.php');
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

// Get current language preference
$conn->query("CREATE TABLE IF NOT EXISTS user_preferences (
  user_id INT PRIMARY KEY,
  language VARCHAR(10) DEFAULT 'en',
  theme VARCHAR(20) DEFAULT 'light',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$prefs_query = $conn->prepare("SELECT language FROM user_preferences WHERE user_id = ?");
$prefs_query->bind_param("i", $user_id);
$prefs_query->execute();
$prefs_result = $prefs_query->get_result();
$prefs = $prefs_result->fetch_assoc();
$prefs_query->close();

$current_language = $prefs['language'] ?? $_SESSION['language'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Language Settings - CommServe</title>
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
    .language-option {
      padding: 20px;
      border: 2px solid #dee2e6;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s;
      margin-bottom: 15px;
    }
    .language-option:hover {
      border-color: #6c757d;
      background-color: #f8f9fa;
    }
    .language-option.selected {
      border-color: #212529;
      background-color: #e9ecef;
    }
    .language-option input[type="radio"] {
      width: 1.5em;
      height: 1.5em;
      cursor: pointer;
    }
    .flag-icon {
      font-size: 3rem;
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
      <i class="bi bi-translate text-dark me-3" style="font-size: 2.5rem;"></i>
      <h2 class="mb-0">Language / Pinulongan / Wika</h2>
    </div>

    <?php if (!empty($flash_message)): ?>
      <div class="alert alert-<?= $flash_type ?> alert-dismissible fade show" role="alert">
        <i class="bi <?= $flash_type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
        <?= htmlspecialchars($flash_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="card settings-card mb-4">
      <div class="card-body p-4">
        <h5 class="card-title mb-4">Choose Your Preferred Language</h5>
        <p class="text-muted mb-4">Select the language you want to use throughout the application</p>
        
        <form method="POST" action="/language-settings.php" id="languageForm">
          <!-- English -->
          <label for="lang_en" class="language-option <?= $current_language === 'en' ? 'selected' : '' ?>">
            <div class="row align-items-center">
              <div class="col-auto">
                <input type="radio" id="lang_en" name="language" value="en" <?= $current_language === 'en' ? 'checked' : '' ?> onchange="selectLanguage(this)">
              </div>
              <div class="col-auto">
                <span class="flag-icon">🇬🇧</span>
              </div>
              <div class="col">
                <h5 class="mb-1">English</h5>
                <p class="text-muted mb-0">Default language for the system</p>
              </div>
              <?php if ($current_language === 'en'): ?>
                <div class="col-auto">
                  <span class="badge bg-dark">Current</span>
                </div>
              <?php endif; ?>
            </div>
          </label>

          <!-- Cebuano -->
          <label for="lang_ceb" class="language-option <?= $current_language === 'ceb' ? 'selected' : '' ?>">
            <div class="row align-items-center">
              <div class="col-auto">
                <input type="radio" id="lang_ceb" name="language" value="ceb" <?= $current_language === 'ceb' ? 'checked' : '' ?> onchange="selectLanguage(this)">
              </div>
              <div class="col-auto">
                <span class="flag-icon">🇵🇭</span>
              </div>
              <div class="col">
                <h5 class="mb-1">Cebuano (Binisaya)</h5>
                <p class="text-muted mb-0">Pinulongang Bisaya alang sa Visayas ug Mindanao</p>
              </div>
              <?php if ($current_language === 'ceb'): ?>
                <div class="col-auto">
                  <span class="badge bg-dark">Karon</span>
                </div>
              <?php endif; ?>
            </div>
          </label>

          <!-- Tagalog -->
          <label for="lang_tl" class="language-option <?= $current_language === 'tl' ? 'selected' : '' ?>">
            <div class="row align-items-center">
              <div class="col-auto">
                <input type="radio" id="lang_tl" name="language" value="tl" <?= $current_language === 'tl' ? 'checked' : '' ?> onchange="selectLanguage(this)">
              </div>
              <div class="col-auto">
                <span class="flag-icon">🇵🇭</span>
              </div>
              <div class="col">
                <h5 class="mb-1">Tagalog (Filipino)</h5>
                <p class="text-muted mb-0">Pambansang wika ng Pilipinas</p>
              </div>
              <?php if ($current_language === 'tl'): ?>
                <div class="col-auto">
                  <span class="badge bg-dark">Kasalukuyan</span>
                </div>
              <?php endif; ?>
            </div>
          </label>

          <div class="d-flex justify-content-between mt-4">
            <a href="/settings.php" class="btn btn-outline-secondary">
              <i class="bi bi-x-circle me-2"></i>Cancel
            </a>
            <button type="submit" name="save_language" class="btn btn-dark">
              <i class="bi bi-save me-2"></i>Save Language Preference
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Language Info -->
    <div class="card settings-card">
      <div class="card-body p-4">
        <h5 class="card-title mb-3">
          <i class="bi bi-info-circle me-2"></i>About Language Support
        </h5>
        <div class="row">
          <div class="col-md-4 mb-3">
            <div class="text-center">
              <i class="bi bi-globe text-primary" style="font-size: 2rem;"></i>
              <h6 class="mt-2">Multiple Languages</h6>
              <p class="text-muted small mb-0">Support for English, Cebuano, and Tagalog</p>
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="text-center">
              <i class="bi bi-person-check text-success" style="font-size: 2rem;"></i>
              <h6 class="mt-2">User Preference</h6>
              <p class="text-muted small mb-0">Your language choice is saved to your profile</p>
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="text-center">
              <i class="bi bi-arrow-repeat text-info" style="font-size: 2rem;"></i>
              <h6 class="mt-2">Easy Switching</h6>
              <p class="text-muted small mb-0">Change your language anytime from settings</p>
            </div>
          </div>
        </div>
        <div class="alert alert-info mt-3 mb-0">
          <i class="bi bi-info-circle-fill me-2"></i>
          <strong>Note:</strong> Full translation support is currently in development. Some parts of the system may still appear in English.
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="text-white text-center py-4 bg-dark">
    <p class="mb-0">&copy; 2025 CommServe. All rights reserved.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function selectLanguage(radio) {
      // Remove selected class from all options
      document.querySelectorAll('.language-option').forEach(option => {
        option.classList.remove('selected');
      });
      
      // Add selected class to chosen option
      radio.closest('.language-option').classList.add('selected');
    }
  </script>
</body>
</html>
