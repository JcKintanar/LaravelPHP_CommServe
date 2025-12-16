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

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS document_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  document_type VARCHAR(100) NOT NULL,
  full_name VARCHAR(255) NOT NULL,
  date_of_birth DATE NOT NULL,
  civil_status ENUM('Single', 'Married', 'Widowed', 'Divorced', 'Separated') NOT NULL,
  sitio_address VARCHAR(255) NOT NULL,
  years_of_residency INT NOT NULL,
  valid_id_path VARCHAR(500),
  purpose TEXT NOT NULL,
  status ENUM('pending', 'processing', 'ready', 'released', 'rejected') DEFAULT 'pending',
  remarks TEXT,
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Handle POST - Update request status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf_token'] ?? '';
  if (!hash_equals($_SESSION['csrf_token'], $token)) {
    flash('danger', 'Invalid CSRF token.');
    header('Location: /manage-requests.php');
    exit;
  }

  $action = $_POST['action'] ?? '';
  
  if ($action === 'update_status') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($request_id <= 0 || !in_array($new_status, ['pending', 'processing', 'ready', 'released', 'rejected'])) {
      flash('danger', 'Invalid data.');
      header('Location: /manage-requests.php');
      exit;
    }

    // Handle PDF upload
    $uploaded_pdf_path = null;
    if (isset($_FILES['uploaded_pdf']) && $_FILES['uploaded_pdf']['error'] === UPLOAD_ERR_OK) {
      $upload_dir = __DIR__ . '/uploads/certificates/';
      if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
      }
      
      $file_ext = strtolower(pathinfo($_FILES['uploaded_pdf']['name'], PATHINFO_EXTENSION));
      
      if ($file_ext !== 'pdf') {
        flash('danger', 'Invalid file type. Only PDF files are allowed.');
        header('Location: /manage-requests.php');
        exit;
      }
      
      if ($_FILES['uploaded_pdf']['size'] > 10 * 1024 * 1024) { // 10MB limit
        flash('danger', 'File size exceeds 10MB limit.');
        header('Location: /manage-requests.php');
        exit;
      }
      
      $file_name = 'certificate_' . $request_id . '_' . time() . '.pdf';
      $file_path = $upload_dir . $file_name;
      
      if (move_uploaded_file($_FILES['uploaded_pdf']['tmp_name'], $file_path)) {
        $uploaded_pdf_path = 'uploads/certificates/' . $file_name;
      } else {
        flash('danger', 'Failed to upload PDF file.');
        header('Location: /manage-requests.php');
        exit;
      }
    }

    // Update database
    if ($uploaded_pdf_path) {
      $stmt = $conn->prepare('UPDATE document_requests SET status=?, remarks=?, uploaded_pdf_path=?, updated_at=NOW() WHERE id=?');
      $stmt->bind_param('sssi', $new_status, $remarks, $uploaded_pdf_path, $request_id);
    } else {
      $stmt = $conn->prepare('UPDATE document_requests SET status=?, remarks=?, updated_at=NOW() WHERE id=?');
      $stmt->bind_param('ssi', $new_status, $remarks, $request_id);
    }
    
    if ($stmt->execute()) {
      flash('success', 'Request status updated successfully.' . ($uploaded_pdf_path ? ' PDF uploaded.' : ''));
    } else {
      flash('danger', 'Failed to update status: ' . $conn->error);
    }
    $stmt->close();
    header('Location: /manage-requests.php');
    exit;
  }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$where_clause = '';
if ($filter !== 'all') {
  $where_clause = " WHERE dr.status = '" . $conn->real_escape_string($filter) . "'";
}

// Get all document requests with user info
$query = "SELECT dr.id, dr.user_id, dr.document_type, dr.full_name, dr.date_of_birth, dr.civil_status,
          dr.sitio_address, dr.years_of_residency, dr.purpose, dr.valid_id_path, dr.uploaded_pdf_path,
          dr.status, dr.remarks, dr.requested_at, dr.updated_at,
          u.firstName, u.lastName, u.middleName, u.email, u.phoneNumber, u.barangay, u.cityMunicipality
          FROM document_requests dr
          JOIN users u ON dr.user_id = u.id
          $where_clause
          ORDER BY 
            CASE dr.status
              WHEN 'pending' THEN 1
              WHEN 'processing' THEN 2
              WHEN 'ready' THEN 3
              WHEN 'released' THEN 4
              WHEN 'rejected' THEN 5
            END,
            dr.requested_at DESC";
$requests = $conn->query($query);

// Get counts for each status
$counts = [
  'all' => $conn->query("SELECT COUNT(*) as c FROM document_requests")->fetch_assoc()['c'] ?? 0,
  'pending' => $conn->query("SELECT COUNT(*) as c FROM document_requests WHERE status='pending'")->fetch_assoc()['c'] ?? 0,
  'processing' => $conn->query("SELECT COUNT(*) as c FROM document_requests WHERE status='processing'")->fetch_assoc()['c'] ?? 0,
  'ready' => $conn->query("SELECT COUNT(*) as c FROM document_requests WHERE status='ready'")->fetch_assoc()['c'] ?? 0,
  'released' => $conn->query("SELECT COUNT(*) as c FROM document_requests WHERE status='released'")->fetch_assoc()['c'] ?? 0,
  'rejected' => $conn->query("SELECT COUNT(*) as c FROM document_requests WHERE status='rejected'")->fetch_assoc()['c'] ?? 0,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Document Requests - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    body { background-color: #f8f9fa; }
    .btn-black { background-color: #000; color: #fff; }
    .btn-black:hover { background-color: #333; color: #fff; }
    .filter-btn {
      border-radius: 20px;
      padding: 0.5rem 1.25rem;
      margin: 0.25rem;
    }
    .filter-btn.active {
      background-color: #000 !important;
      color: #fff !important;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="<?= ($_SESSION['role'] ?? '') === 'admin' ? '/dashboards/adminDashboard.php' : '/dashboards/officialDashboard.php' ?>">
        <i class="bi bi-file-earmark-text-fill me-2"></i>Barangay <?= $barangay ?>
      </a>
      <div class="d-flex">
        <a class="nav-link text-white" href="<?= ($_SESSION['role'] ?? '') === 'admin' ? '/dashboards/adminDashboard.php' : '/dashboards/officialDashboard.php' ?>">
          <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
      </div>
    </div>
  </nav>

  <div class="container py-5">
    <h2 class="text-center mb-4"><i class="bi bi-file-earmark-text-fill me-2"></i>Manage Document Requests</h2>

    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['type']) ?> alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-2">
        <div class="card text-center">
          <div class="card-body">
            <h3 class="mb-0"><?= $counts['all'] ?></h3>
            <small class="text-muted">Total</small>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card text-center bg-secondary text-white">
          <div class="card-body">
            <h3 class="mb-0"><?= $counts['pending'] ?></h3>
            <small>Pending</small>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card text-center bg-primary text-white">
          <div class="card-body">
            <h3 class="mb-0"><?= $counts['processing'] ?></h3>
            <small>Processing</small>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card text-center bg-success text-white">
          <div class="card-body">
            <h3 class="mb-0"><?= $counts['ready'] ?></h3>
            <small>Ready</small>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card text-center bg-info text-white">
          <div class="card-body">
            <h3 class="mb-0"><?= $counts['released'] ?></h3>
            <small>Released</small>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card text-center bg-danger text-white">
          <div class="card-body">
            <h3 class="mb-0"><?= $counts['rejected'] ?></h3>
            <small>Rejected</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Buttons -->
    <div class="text-center mb-4">
      <a href="/manage-requests.php?filter=all" class="btn filter-btn <?= $filter === 'all' ? 'active btn-dark' : 'btn-outline-dark' ?>">
        All (<?= $counts['all'] ?>)
      </a>
      <a href="/manage-requests.php?filter=pending" class="btn filter-btn <?= $filter === 'pending' ? 'active btn-dark' : 'btn-outline-secondary' ?>">
        Pending (<?= $counts['pending'] ?>)
      </a>
      <a href="/manage-requests.php?filter=processing" class="btn filter-btn <?= $filter === 'processing' ? 'active btn-dark' : 'btn-outline-primary' ?>">
        Processing (<?= $counts['processing'] ?>)
      </a>
      <a href="/manage-requests.php?filter=ready" class="btn filter-btn <?= $filter === 'ready' ? 'active btn-dark' : 'btn-outline-success' ?>">
        Ready (<?= $counts['ready'] ?>)
      </a>
      <a href="/manage-requests.php?filter=released" class="btn filter-btn <?= $filter === 'released' ? 'active btn-dark' : 'btn-outline-info' ?>">
        Released (<?= $counts['released'] ?>)
      </a>
      <a href="/manage-requests.php?filter=rejected" class="btn filter-btn <?= $filter === 'rejected' ? 'active btn-dark' : 'btn-outline-danger' ?>">
        Rejected (<?= $counts['rejected'] ?>)
      </a>
    </div>

    <!-- Requests Table -->
    <div class="card shadow-sm">
      <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Document Requests</h5>
      </div>
      <div class="card-body">
        <?php if ($requests && $requests->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Resident</th>
                  <th>Contact</th>
                  <th>Document Type</th>
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
                    <td><?= htmlspecialchars($req['full_name']) ?></td>
                    <td>
                      <small>
                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($req['email']) ?><br>
                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($req['phoneNumber']) ?>
                      </small>
                    </td>
                    <td><strong><?= htmlspecialchars($req['document_type']) ?></strong></td>
                    <td><?= htmlspecialchars(substr($req['purpose'], 0, 50)) . (strlen($req['purpose']) > 50 ? '...' : '') ?></td>
                    <td>
                      <?php
                        $status = $req['status'];
                        $badge_class = 'secondary';
                        switch ($status) {
                          case 'pending': $badge_class = 'secondary'; break;
                          case 'processing': $badge_class = 'primary'; break;
                          case 'ready': $badge_class = 'success'; break;
                          case 'released': $badge_class = 'info'; break;
                          case 'rejected': $badge_class = 'danger'; break;
                        }
                      ?>
                      <span class="badge bg-<?= $badge_class ?>"><?= strtoupper($status) ?></span>
                    </td>
                    <td><small><?= date('M d, Y', strtotime($req['requested_at'])) ?></small></td>
                    <td>
                      <button class="btn btn-sm btn-black" data-bs-toggle="modal" data-bs-target="#updateModal<?= $req['id'] ?>">
                        <i class="bi bi-pencil-square"></i> Update
                      </button>
                    </td>
                  </tr>

                  <!-- Update Status Modal -->
                  <div class="modal fade" id="updateModal<?= $req['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">
                        <div class="modal-header bg-dark text-white">
                          <h5 class="modal-title">Update Request #<?= str_pad($req['id'], 5, '0', STR_PAD_LEFT) ?></h5>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="post" action="/manage-requests.php" enctype="multipart/form-data">
                          <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            
                            <div class="mb-3 p-3 bg-light rounded">
                              <h6 class="fw-bold mb-2">Request Details</h6>
                              <div class="row">
                                <div class="col-6"><strong>Full Name:</strong> <?= htmlspecialchars($req['full_name']) ?></div>
                                <div class="col-6"><strong>Document:</strong> <?= htmlspecialchars($req['document_type']) ?></div>
                                <div class="col-6"><strong>Date of Birth:</strong> <?= date('M d, Y', strtotime($req['date_of_birth'])) ?></div>
                                <div class="col-6"><strong>Civil Status:</strong> <?= htmlspecialchars($req['civil_status']) ?></div>
                                <div class="col-6"><strong>Address:</strong> <?= htmlspecialchars($req['sitio_address']) ?></div>
                                <div class="col-6"><strong>Years of Residency:</strong> <?= $req['years_of_residency'] ?> years</div>
                                <div class="col-12 mt-2"><strong>Purpose:</strong> <?= htmlspecialchars($req['purpose']) ?></div>
                                <?php if ($req['valid_id_path']): ?>
                                  <div class="col-12 mt-2">
                                    <strong>Valid ID:</strong> 
                                    <a href="/view-file.php?file=<?= urlencode($req['valid_id_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                      <i class="bi bi-eye me-1"></i>View ID
                                    </a>
                                  </div>
                                <?php endif; ?>
                              </div>
                            </div>
                            
                            <div class="mb-3">
                              <label class="form-label fw-bold">Update Status</label>
                              <select name="status" class="form-select" required>
                                <option value="pending" <?= $req['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $req['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="ready" <?= $req['status'] === 'ready' ? 'selected' : '' ?>>Ready for Pickup</option>
                                <option value="released" <?= $req['status'] === 'released' ? 'selected' : '' ?>>Released</option>
                                <option value="rejected" <?= $req['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                              </select>
                            </div>
                            
                            <div class="mb-3">
                              <label class="form-label fw-bold">Remarks/Notes</label>
                              <textarea name="remarks" class="form-control" rows="3" placeholder="Add any notes or instructions for the resident"><?= htmlspecialchars($req['remarks'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                              <label class="form-label fw-bold">Upload Certificate PDF (Optional)</label>
                              <?php if ($req['uploaded_pdf_path']): ?>
                                <div class="alert alert-info mb-2">
                                  <i class="bi bi-file-pdf me-2"></i>Current file: 
                                  <a href="/<?= htmlspecialchars($req['uploaded_pdf_path']) ?>" target="_blank">View PDF</a>
                                </div>
                              <?php endif; ?>
                              <input type="file" name="uploaded_pdf" class="form-control" accept=".pdf">
                              <small class="text-muted">Upload a PDF certificate for the resident to download. Max 10MB. Will replace existing file if any.</small>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-black">Save Changes</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
            <p class="text-muted mt-3 mb-0">No document requests found.</p>
          </div>
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