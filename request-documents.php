<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';

// User guard - redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: /pages/loginPage.php');
  exit;
}

$user_id = $_SESSION['user_id'];

// Add new columns to users table if they don't exist
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS dateOfBirth DATE NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS civilStatus ENUM('Single', 'Married', 'Widowed', 'Divorced', 'Separated') NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS yearResidency INT NULL COMMENT 'Year the user started residing in the barangay'");

// Get user data
$stmt = $conn->prepare("SELECT id, lastName, firstName, middleName, barangay, cityMunicipality, username, role, dateOfBirth, civilStatus, yearResidency FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

$barangay = htmlspecialchars($user_data['barangay'] ?? 'Barangay', ENT_QUOTES, 'UTF-8');
$role = $user_data['role'] ?? 'user';

// Determine dashboard link based on role
switch ($role) {
  case 'admin':
    $dashboard_link = '/dashboards/adminDashboard.php';
    $navbar_icon = 'bi-shield-check';
    break;
  case 'official':
    $dashboard_link = '/dashboards/officialDashboard.php';
    $navbar_icon = 'bi-briefcase';
    break;
  default:
    $dashboard_link = '/dashboards/userDashboard.php';
    $navbar_icon = 'bi-house-fill';
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

function flash($type, $msg) {
  $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// Create document_requests table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS document_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  document_type VARCHAR(100) NOT NULL,
  full_name VARCHAR(255) NOT NULL,
  date_of_birth DATE NOT NULL,
  civil_status ENUM('Single', 'Married', 'Widowed', 'Divorced', 'Separated') NOT NULL,
  sitio_address VARCHAR(255) NOT NULL,
  years_of_residency INT NOT NULL,
  purpose TEXT NOT NULL,
  valid_id_path VARCHAR(500),
  status ENUM('pending', 'processing', 'ready', 'released', 'rejected') DEFAULT 'pending',
  remarks TEXT,
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Handle POST - Submit new request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf_token'] ?? '';
  if (!hash_equals($_SESSION['csrf_token'], $token)) {
    flash('danger', 'Invalid CSRF token.');
    header('Location: /request-documents.php');
    exit;
  }

  $document_type = trim($_POST['document_type'] ?? '');
  $full_name = trim($_POST['full_name'] ?? '');
  $date_of_birth = trim($_POST['date_of_birth'] ?? '');
  $civil_status = trim($_POST['civil_status'] ?? '');
  $sitio_address = trim($_POST['sitio_address'] ?? '');
  $years_of_residency = (int)($_POST['years_of_residency'] ?? 0);
  $purpose = trim($_POST['purpose'] ?? '');

  // Validation
  if (empty($document_type) || empty($full_name) || empty($date_of_birth) || 
      empty($civil_status) || empty($sitio_address) || $years_of_residency <= 0 || empty($purpose)) {
    flash('danger', 'Please fill in all required fields.');
    header('Location: /request-documents.php');
    exit;
  }

  // Handle file upload
  $valid_id_path = null;
  if (isset($_FILES['valid_id']) && $_FILES['valid_id']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/uploads/valid_ids/';
    if (!file_exists($upload_dir)) {
      mkdir($upload_dir, 0755, true);
    }
    
    $file_ext = strtolower(pathinfo($_FILES['valid_id']['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (!in_array($file_ext, $allowed_ext)) {
      flash('danger', 'Invalid file type. Only JPG, PNG, and PDF are allowed.');
      header('Location: /request-documents.php');
      exit;
    }
    
    if ($_FILES['valid_id']['size'] > 5 * 1024 * 1024) { // 5MB limit
      flash('danger', 'File size exceeds 5MB limit.');
      header('Location: /request-documents.php');
      exit;
    }
    
    $file_name = 'valid_id_' . $user_id . '_' . time() . '.' . $file_ext;
    $file_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['valid_id']['tmp_name'], $file_path)) {
      $valid_id_path = 'uploads/valid_ids/' . $file_name;
    } else {
      flash('danger', 'Failed to upload valid ID.');
      header('Location: /request-documents.php');
      exit;
    }
  } else {
    flash('danger', 'Please upload a valid ID for verification.');
    header('Location: /request-documents.php');
    exit;
  }

  $stmt = $conn->prepare('INSERT INTO document_requests (user_id, document_type, full_name, date_of_birth, civil_status, sitio_address, years_of_residency, purpose, valid_id_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt->bind_param('isssssiss', $user_id, $document_type, $full_name, $date_of_birth, $civil_status, $sitio_address, $years_of_residency, $purpose, $valid_id_path);
  
  if ($stmt->execute()) {
    flash('success', 'Document request submitted successfully! Please wait for barangay staff to process your request.');
  } else {
    flash('danger', 'Failed to submit request: ' . $conn->error);
  }
  $stmt->close();
  header('Location: /request-documents.php');
  exit;
}

// Get user's document requests
$stmt_requests = $conn->prepare("SELECT id, document_type, full_name, purpose, status, remarks, uploaded_pdf_path, requested_at, updated_at FROM document_requests WHERE user_id = ? ORDER BY requested_at DESC");
$stmt_requests->bind_param("i", $user_id);
$stmt_requests->execute();
$requests = $stmt_requests->get_result();
$stmt_requests->close();

$fullName = htmlspecialchars(trim($user_data['firstName'] . ' ' . ($user_data['middleName'] ?? '') . ' ' . $user_data['lastName']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Request Documents - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      padding-bottom: 80px;
    }
    .document-card {
      transition: transform 0.2s, box-shadow 0.2s;
      cursor: pointer;
      border: 2px solid transparent;
    }
    .document-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15) !important;
      border-color: #000;
    }
    .document-card.selected {
      border-color: #000;
      background-color: #f8f9fa;
    }
    .document-card input[type="radio"] {
      transform: scale(1.3);
    }
    .status-badge {
      font-size: 0.85rem;
      padding: 0.35rem 0.75rem;
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
        <i class="bi <?= $navbar_icon ?> me-2"></i>Barangay <?= $barangay ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="<?= $dashboard_link ?>">
              <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container py-5">
    <h2 class="text-center mb-2"><i class="bi bi-house-fill me-2"></i>Request Barangay Documents</h2>
    <p class="text-center text-muted mb-5">Submit your document requests and track their status</p>

    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['type']) ?> alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Request Form -->
    <div class="card shadow-sm mb-5">
      <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Submit New Request</h5>
      </div>
      <div class="card-body">
        <form method="post" action="/request-documents.php" id="requestForm" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          
          <div class="mb-4">
            <label class="form-label fw-bold">Select Document Type <span class="text-danger">*</span></label>
            <div class="row g-3">
              <!-- Barangay Clearance -->
              <div class="col-md-6">
                <div class="card document-card shadow-sm h-100" onclick="selectDocument('barangay_clearance', this)">
                  <div class="card-body">
                    <div class="d-flex align-items-start">
                      <input type="radio" name="document_type" value="Barangay Clearance" id="doc1" class="me-3 mt-1" required>
                      <div>
                        <h6 class="mb-2">
                          <i class="bi bi-file-earmark-check text-primary me-2"></i>
                          <label for="doc1" class="mb-0 fw-bold" style="cursor: pointer;">Barangay Clearance</label>
                        </h6>
                        <p class="text-muted small mb-0">Required for employment, ID applications, and other legal purposes</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Certificate of Residency -->
              <div class="col-md-6">
                <div class="card document-card shadow-sm h-100" onclick="selectDocument('cert_residency', this)">
                  <div class="card-body">
                    <div class="d-flex align-items-start">
                      <input type="radio" name="document_type" value="Certificate of Residency" id="doc2" class="me-3 mt-1" required>
                      <div>
                        <h6 class="mb-2">
                          <i class="bi bi-house-check text-success me-2"></i>
                          <label for="doc2" class="mb-0 fw-bold" style="cursor: pointer;">Certificate of Residency</label>
                        </h6>
                        <p class="text-muted small mb-0">Proof of address for various requirements and applications</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Certificate of Indigency -->
              <div class="col-md-6">
                <div class="card document-card shadow-sm h-100" onclick="selectDocument('cert_indigency', this)">
                  <div class="card-body">
                    <div class="d-flex align-items-start">
                      <input type="radio" name="document_type" value="Certificate of Indigency" id="doc3" class="me-3 mt-1" required>
                      <div>
                        <h6 class="mb-2">
                          <i class="bi bi-heart text-danger me-2"></i>
                          <label for="doc3" class="mb-0 fw-bold" style="cursor: pointer;">Certificate of Indigency</label>
                        </h6>
                        <p class="text-muted small mb-0">For financial assistance, medical aid, and scholarship applications</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Barangay Business Clearance -->
              <div class="col-md-6">
                <div class="card document-card shadow-sm h-100" onclick="selectDocument('business_clearance', this)">
                  <div class="card-body">
                    <div class="d-flex align-items-start">
                      <input type="radio" name="document_type" value="Barangay Business Clearance" id="doc4" class="me-3 mt-1" required>
                      <div>
                        <h6 class="mb-2">
                          <i class="bi bi-shop text-warning me-2"></i>
                          <label for="doc4" class="mb-0 fw-bold" style="cursor: pointer;">Barangay Business Clearance</label>
                        </h6>
                        <p class="text-muted small mb-0">Required for business registration and permit applications</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- First Time Job Seeker Certificate -->
              <div class="col-md-6">
                <div class="card document-card shadow-sm h-100" onclick="selectDocument('job_seeker', this)">
                  <div class="card-body">
                    <div class="d-flex align-items-start">
                      <input type="radio" name="document_type" value="First Time Job Seeker Certificate" id="doc5" class="me-3 mt-1" required>
                      <div>
                        <h6 class="mb-2">
                          <i class="bi bi-briefcase text-info me-2"></i>
                          <label for="doc5" class="mb-0 fw-bold" style="cursor: pointer;">First Time Job Seeker Certificate</label>
                        </h6>
                        <p class="text-muted small mb-0">Free documents for new graduates under RA 11261</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Personal Information Section -->
          <div class="mb-4">
            <h5 class="mb-3"><i class="bi bi-person-fill me-2"></i>Personal Information</h5>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control" 
                       value="<?= htmlspecialchars(trim(($user_data['firstName'] ?? '') . ' ' . ($user_data['middleName'] ?? '') . ' ' . ($user_data['lastName'] ?? ''))) ?>" 
                       readonly required>
                <small class="text-muted">This is your registered name in the system</small>
              </div>
              <div class="col-md-6">
                <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                <input type="date" name="date_of_birth" class="form-control" 
                       value="<?= htmlspecialchars($user_data['dateOfBirth'] ?? '') ?>" 
                       <?= !empty($user_data['dateOfBirth']) ? 'readonly' : '' ?> required>
                <?php if (!empty($user_data['dateOfBirth'])): ?>
                  <small class="text-muted">From your profile</small>
                <?php else: ?>
                  <small class="text-muted">Please enter your date of birth</small>
                <?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label">Civil Status <span class="text-danger">*</span></label>
                <select name="civil_status" class="form-select" 
                        <?= !empty($user_data['civilStatus']) ? 'disabled' : '' ?> required>
                  <option value="">Select Status</option>
                  <option value="Single" <?= ($user_data['civilStatus'] ?? '') === 'Single' ? 'selected' : '' ?>>Single</option>
                  <option value="Married" <?= ($user_data['civilStatus'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                  <option value="Widowed" <?= ($user_data['civilStatus'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                  <option value="Divorced" <?= ($user_data['civilStatus'] ?? '') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                  <option value="Separated" <?= ($user_data['civilStatus'] ?? '') === 'Separated' ? 'selected' : '' ?>>Separated</option>
                </select>
                <?php if (!empty($user_data['civilStatus'])): ?>
                  <input type="hidden" name="civil_status" value="<?= htmlspecialchars($user_data['civilStatus']) ?>">
                  <small class="text-muted">From your profile</small>
                <?php else: ?>
                  <small class="text-muted">Please select your civil status</small>
                <?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label">Years of Residency <span class="text-danger">*</span></label>
                <?php 
                $years_residing = '';
                if (!empty($user_data['yearResidency'])) {
                  $years_residing = date('Y') - (int)$user_data['yearResidency'];
                }
                ?>
                <input type="number" name="years_of_residency" class="form-control" min="1" 
                       value="<?= $years_residing ?>" 
                       <?= !empty($user_data['yearResidency']) ? 'readonly' : '' ?>
                       placeholder="e.g., 5" required>
                <?php if (!empty($user_data['yearResidency'])): ?>
                  <small class="text-muted">Residing since <?= htmlspecialchars($user_data['yearResidency']) ?> (<?= $years_residing ?> years)</small>
                <?php else: ?>
                  <small class="text-muted">How many years have you lived here?</small>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Address Verification Section -->
          <div class="mb-4">
            <h5 class="mb-3"><i class="bi bi-geo-alt-fill me-2"></i>Address Verification</h5>
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label">Sitio/Purok Address <span class="text-danger">*</span></label>
                <input type="text" name="sitio_address" class="form-control" placeholder="e.g., Purok 3, Sitio Maligaya" required>
                <small class="text-muted">Please provide your complete sitio/purok address to verify jurisdiction</small>
              </div>
            </div>
          </div>

          <!-- Purpose Section -->
          <div class="mb-4">
            <h5 class="mb-3"><i class="bi bi-chat-left-text-fill me-2"></i>Request Details</h5>
            <label class="form-label">Purpose of Request <span class="text-danger">*</span></label>
            <textarea name="purpose" class="form-control" rows="3" placeholder="Please specify the purpose (e.g., Job application at ABC Company, School enrollment at XYZ University, Business permit application)" required></textarea>
          </div>

          <!-- ID Upload Section -->
          <div class="mb-4">
            <h5 class="mb-3"><i class="bi bi-file-earmark-image me-2"></i>Valid ID Upload</h5>
            <label class="form-label">Upload Valid ID (for Address Verification) <span class="text-danger">*</span></label>
            <input type="file" name="valid_id" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
            <small class="text-muted">Accepted formats: JPG, PNG, PDF (Max 5MB). Must show your current address.</small>
          </div>

          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Note:</strong> All information will be verified by barangay staff. Processing time may vary depending on the document type and current queue. You will be notified once your document is ready for pickup or download.
          </div>

          <div class="text-end">
            <button type="submit" class="btn btn-dark btn-lg px-5">
              <i class="bi bi-send me-2"></i>Submit Request
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- My Requests -->
    <div class="card shadow-sm">
      <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>My Document Requests</h5>
      </div>
      <div class="card-body">
        <?php if ($requests && $requests->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Request ID</th>
                  <th>Document Type</th>
                  <th>Name</th>
                  <th>Purpose</th>
                  <th>Status</th>
                  <th>Requested</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($req = $requests->fetch_assoc()): ?>
                  <tr>
                    <td><strong>#<?= str_pad($req['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                    <td><?= htmlspecialchars($req['document_type']) ?></td>
                    <td><?= htmlspecialchars($req['full_name']) ?></td>
                    <td><?= htmlspecialchars($req['purpose'] ?: '-') ?></td>
                    <td>
                      <?php
                        $status = $req['status'];
                        $badge_class = 'secondary';
                        $icon = 'bi-clock';
                        switch ($status) {
                          case 'pending':
                            $badge_class = 'secondary';
                            $icon = 'bi-clock';
                            break;
                          case 'processing':
                            $badge_class = 'primary';
                            $icon = 'bi-arrow-repeat';
                            break;
                          case 'ready':
                            $badge_class = 'success';
                            $icon = 'bi-check-circle';
                            break;
                          case 'released':
                            $badge_class = 'info';
                            $icon = 'bi-check-all';
                            break;
                          case 'rejected':
                            $badge_class = 'danger';
                            $icon = 'bi-x-circle';
                            break;
                        }
                      ?>
                      <span class="badge bg-<?= $badge_class ?> status-badge">
                        <i class="bi <?= $icon ?> me-1"></i><?= strtoupper($status) ?>
                      </span>
                    </td>
                    <td><?= date('M d, Y h:i A', strtotime($req['requested_at'])) ?></td>
                    <td>
                      <?php if ($req['status'] === 'ready' && $req['uploaded_pdf_path']): ?>
                        <a href="/<?= htmlspecialchars($req['uploaded_pdf_path']) ?>" class="btn btn-sm btn-success" target="_blank">
                          <i class="bi bi-download me-1"></i>Download PDF
                        </a>
                      <?php elseif ($req['status'] === 'ready'): ?>
                        <a href="/download-certificate.php?id=<?= $req['id'] ?>" class="btn btn-sm btn-success" target="_blank">
                          <i class="bi bi-download me-1"></i>Generate PDF
                        </a>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
            <p class="text-muted mt-3 mb-0">No document requests yet. Submit your first request above!</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="text-white text-center py-4 bg-dark">
    <p class="mb-0">&copy; 2025 CommServe. All rights reserved.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function selectDocument(type, card) {
      // Remove selected class from all cards
      document.querySelectorAll('.document-card').forEach(c => c.classList.remove('selected'));
      
      // Add selected class to clicked card
      card.classList.add('selected');
      
      // Check the radio button
      card.querySelector('input[type="radio"]').checked = true;
    }
  </script>
</body>
</html>
