<?php
session_start();
require_once __DIR__ . '/../userAccounts/config.php';

// User guard - redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: /pages/loginPage.php');
  exit;
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $conn->prepare("SELECT id, lastName, firstName, middleName, barangay, cityMunicipality, username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

$user_id_display = htmlspecialchars($user_data['id'] ?? '', ENT_QUOTES, 'UTF-8');
$lastName = htmlspecialchars(strtoupper($user_data['lastName'] ?? ''), ENT_QUOTES, 'UTF-8');
$firstName = htmlspecialchars($user_data['firstName'] ?? '', ENT_QUOTES, 'UTF-8');
$middleName = htmlspecialchars($user_data['middleName'] ?? '', ENT_QUOTES, 'UTF-8');
$barangay = htmlspecialchars($user_data['barangay'] ?? 'Barangay', ENT_QUOTES, 'UTF-8');
$cityMunicipality = htmlspecialchars($user_data['cityMunicipality'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
$username = htmlspecialchars($user_data['username'] ?? 'Resident', ENT_QUOTES, 'UTF-8');

// Get announcements for this barangay
$stmt_ann = $conn->prepare("SELECT id, title, content, icon, color, image_path, createdAt FROM announcements WHERE barangay = ? OR barangay IS NULL OR barangay = '' ORDER BY createdAt DESC LIMIT 6");
$stmt_ann->bind_param("s", $user_data['barangay']);
$stmt_ann->execute();
$announcements = $stmt_ann->get_result();
$stmt_ann->close();

// Get emergency hotlines
$stmt_hot = $conn->prepare("SELECT id, name, number, description FROM emergency_hotlines WHERE barangay = ? OR barangay IS NULL OR barangay = '' ORDER BY name LIMIT 6");
$stmt_hot->bind_param("s", $user_data['barangay']);
$stmt_hot->execute();
$hotlines = $stmt_hot->get_result();
$stmt_hot->close();

// Get user's document requests
$stmt_docs = $conn->prepare("SELECT id, document_type, full_name, status, uploaded_pdf_path, requested_at, updated_at FROM document_requests WHERE user_id = ? ORDER BY requested_at DESC LIMIT 5");
$stmt_docs->bind_param("i", $user_id);
$stmt_docs->execute();
$doc_requests = $stmt_docs->get_result();
$stmt_docs->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resident Dashboard - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { padding-bottom: 80px; background-color: #f8f9fa; }
    @media (max-width: 576px) { body { padding-bottom: 100px; } }
    footer.fixed-bottom-footer { position: fixed; left: 0; bottom: 0; width: 100%; z-index: 999; }
    .service-card { transition: transform 0.2s, box-shadow 0.2s; }
    .service-card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15) !important; }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="/dashboards/userDashboard.php">
        <i class="bi bi-house-fill me-2"></i>Barangay <?= $barangay ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i><?= $user_id_display ?> | <?= $lastName ?>, <?= $firstName ?> <?= $middleName ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php if (($_SESSION['role'] ?? '') === 'official'): ?>
                <li><a class="dropdown-item" href="/dashboards/officialDashboard.php"><i class="bi bi-briefcase me-2"></i>Official Dashboard</a></li>
                <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="/userProfile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
              <li><a class="dropdown-item" href="/settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
              <li><a class="dropdown-item" href="/privacy-security.php"><i class="bi bi-shield-lock me-2"></i>Privacy & Security</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <section class="container my-5">
    <!-- Welcome Card -->
    <div class="card shadow-sm bg-dark text-white mb-4" style="border-radius: 2rem;">
      <div class="card-body text-center py-5">
        <h1 class="fw-bold mb-3" style="font-family: 'Montserrat', sans-serif; font-size: 2.5rem;">
          Welcome, <?= $firstName ?>!
        </h1>
        <p class="mb-0" style="font-size: 1.15rem; color: #ccc;">
          Access barangay services, announcements, and emergency assistance all in one place.
        </p>
      </div>
    </div>

    <!-- Location Info -->
    <div class="card shadow-sm bg-dark text-white mb-4" style="border-radius: 2rem;">
      <div class="card-body text-center py-2">
        <p class="mb-0" style="color: #fff;">
          <i class="bi bi-geo-alt-fill me-2"></i>
          Barangay: <strong><?= strtoupper($barangay) ?></strong> | 
          Municipality/City: <strong><?= strtoupper($cityMunicipality) ?></strong>
        </p>
      </div>
    </div>

    <!-- Services Section -->
    <h3 class="mb-4"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Quick Services</h3>
    <div class="row g-4 mb-5">
      <div class="col-md-3">
        <div class="card h-100 shadow-sm border-dark service-card">
          <div class="card-body text-center">
            <i class="bi bi-megaphone-fill" style="font-size:3.5rem;color:#000;"></i>
            <h5 class="mt-3">Announcements</h5>
            <p class="text-muted">View latest barangay announcements</p>
            <a href="#announcements" class="btn btn-dark w-100">View All</a>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card h-100 shadow-sm border-dark service-card">
          <div class="card-body text-center">
            <i class="bi bi-file-earmark-text-fill" style="font-size:3.5rem;color:#000;"></i>
            <h5 class="mt-3">Request Documents</h5>
            <p class="text-muted">Request barangay certificates</p>
            <a href="/request-documents.php" class="btn btn-dark w-100">Make Request</a>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card h-100 shadow-sm border-dark service-card">
          <div class="card-body text-center">
            <i class="bi bi-telephone-fill" style="font-size:3.5rem;color:#000;"></i>
            <h5 class="mt-3">Emergency Hotlines</h5>
            <p class="text-muted">Quick access to emergency contacts</p>
            <a href="#hotlines" class="btn btn-dark w-100">View Hotlines</a>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card h-100 shadow-sm border-dark service-card">
          <div class="card-body text-center">
            <i class="bi bi-chat-dots-fill" style="font-size:3.5rem;color:#000;"></i>
            <h5 class="mt-3">Messages</h5>
            <p class="text-muted">Contact barangay officials</p>
            <a href="/messages.php" class="btn btn-dark w-100">View Messages</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Announcements Section -->
    <h3 class="mb-4" id="announcements"><i class="bi bi-megaphone-fill me-2"></i>Latest Announcements</h3>
    <div class="row g-4 mb-5">
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
                <p class="card-text"><?= nl2br(htmlspecialchars($ann['content'])) ?></p>
                <p class="text-muted small mb-0">
                  <i class="bi bi-calendar-event me-1"></i>
                  <?= date('F d, Y', strtotime($ann['createdAt'])) ?>
                </p>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-info text-center">
            <i class="bi bi-info-circle me-2"></i>No announcements available at this time.
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Emergency Hotlines Section -->
    <h3 class="mb-4" id="hotlines"><i class="bi bi-telephone-fill me-2"></i>Emergency Hotlines</h3>
    <div class="row g-4">
      <?php if ($hotlines && $hotlines->num_rows > 0): ?>
        <?php while ($hot = $hotlines->fetch_assoc()): ?>
          <div class="col-md-4">
            <div class="card shadow-sm h-100">
              <div class="card-body text-center">
                <i class="bi bi-telephone-forward-fill text-danger" style="font-size:2.5rem;"></i>
                <h6 class="mt-3 fw-bold"><?= htmlspecialchars($hot['name']) ?></h6>
                <?php if (!empty($hot['description'])): ?>
                  <p class="text-muted small mb-2"><?= htmlspecialchars($hot['description']) ?></p>
                <?php endif; ?>
                <a href="tel:<?= htmlspecialchars($hot['number']) ?>" class="btn btn-dark btn-sm">
                  <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($hot['number']) ?>
                </a>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-warning text-center">
            <i class="bi bi-exclamation-triangle me-2"></i>No emergency hotlines configured yet.
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Document Requests Status Section -->
    <h3 class="mb-4 mt-5" id="requests"><i class="bi bi-file-earmark-text-fill me-2"></i>My Document Requests</h3>
    <div class="card shadow-sm">
      <div class="card-body">
        <?php if ($doc_requests && $doc_requests->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover table-sm">
              <thead>
                <tr>
                  <th>Request ID</th>
                  <th>Document Type</th>
                  <th>Name</th>
                  <th>Status</th>
                  <th>Requested</th>
                  <th>Last Updated</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($doc = $doc_requests->fetch_assoc()): ?>
                  <tr>
                    <td><strong>#<?= str_pad($doc['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                    <td><?= htmlspecialchars($doc['document_type']) ?></td>
                    <td><?= htmlspecialchars($doc['full_name']) ?></td>
                    <td>
                      <?php
                        $status = $doc['status'];
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
                      <span class="badge bg-<?= $badge_class ?>">
                        <i class="bi <?= $icon ?> me-1"></i><?= strtoupper($status) ?>
                      </span>
                    </td>
                    <td><?= date('M d, Y', strtotime($doc['requested_at'])) ?></td>
                    <td><?= date('M d, Y', strtotime($doc['updated_at'])) ?></td>
                    <td>
                      <?php if ($doc['status'] === 'ready' && $doc['uploaded_pdf_path']): ?>
                        <a href="/<?= htmlspecialchars($doc['uploaded_pdf_path']) ?>" class="btn btn-sm btn-success" target="_blank">
                          <i class="bi bi-download me-1"></i>Download
                        </a>
                      <?php elseif ($doc['status'] === 'ready'): ?>
                        <a href="/download-certificate.php?id=<?= $doc['id'] ?>" class="btn btn-sm btn-info" target="_blank">
                          <i class="bi bi-file-earmark-pdf me-1"></i>Generate
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
          <div class="text-center mt-3">
            <a href="/request-documents.php" class="btn btn-dark">
              <i class="bi bi-plus-circle me-2"></i>Submit New Request
            </a>
            <a href="/request-documents.php#my-requests" class="btn btn-outline-dark">
              <i class="bi bi-list-ul me-2"></i>View All Requests
            </a>
          </div>
        <?php else: ?>
          <div class="text-center py-4">
            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3 mb-3">You haven't submitted any document requests yet.</p>
            <a href="/request-documents.php" class="btn btn-dark">
              <i class="bi bi-plus-circle me-2"></i>Request a Document
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="text-white text-center py-4 bg-dark fixed-bottom-footer">
    <p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>