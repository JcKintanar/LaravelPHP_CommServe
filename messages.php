<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';

// Guard: All logged-in users can access
if (!isset($_SESSION['user_id'])) {
  header('Location: /pages/loginPage.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'resident';

// Get user info
$stmt_user = $conn->prepare("SELECT id, firstName, lastName, middleName, barangay, role FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$barangay = htmlspecialchars($user_data['barangay'] ?? 'Barangay', ENT_QUOTES, 'UTF-8');
$fullName = htmlspecialchars($user_data['firstName'] . ' ' . $user_data['lastName'], ENT_QUOTES, 'UTF-8');

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
    header('Location: /messages.php');
    exit;
  }

  $action = $_POST['action'] ?? '';
  
  if ($action === 'send_message') {
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($receiver_id <= 0 || $message === '') {
      flash('danger', 'Please select a recipient and enter a message.');
      header('Location: /messages.php');
      exit;
    }

    // Verify receiver exists
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt_check->bind_param("i", $receiver_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();
    $stmt_check->close();

    if ($check_result->num_rows === 0) {
      flash('danger', 'Recipient not found.');
      header('Location: /messages.php');
      exit;
    }

    $stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, message) VALUES (?,?,?)');
    $stmt->bind_param('iis', $user_id, $receiver_id, $message);
    if ($stmt->execute()) {
      flash('success', 'Message sent successfully.');
    } else {
      flash('danger', 'Failed to send message: ' . $conn->error);
    }
    $stmt->close();
    header('Location: /messages.php?conversation=' . $receiver_id);
    exit;
  }

  if ($action === 'mark_read') {
    $msg_id = (int)($_POST['message_id'] ?? 0);
    if ($msg_id > 0) {
      $stmt = $conn->prepare('UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?');
      $stmt->bind_param('ii', $msg_id, $user_id);
      $stmt->execute();
      $stmt->close();
    }
    exit;
  }
}

// Get conversation partner if specified
$conversation_user_id = (int)($_GET['conversation'] ?? 0);
$conversation_user = null;
if ($conversation_user_id > 0) {
  $stmt_conv = $conn->prepare("SELECT id, firstName, lastName, middleName, role FROM users WHERE id = ?");
  $stmt_conv->bind_param("i", $conversation_user_id);
  $stmt_conv->execute();
  $conversation_user = $stmt_conv->get_result()->fetch_assoc();
  $stmt_conv->close();

  // Mark messages as read
  $stmt_mark = $conn->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?');
  $stmt_mark->bind_param('ii', $conversation_user_id, $user_id);
  $stmt_mark->execute();
  $stmt_mark->close();
}

// Admin barangay filter
$filter_barangay = $_GET['filter_barangay'] ?? '';
$all_barangays = [];
if ($user_role === 'admin') {
  $stmt_brgy = $conn->query("SELECT DISTINCT barangay FROM users WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay");
  while ($brgy = $stmt_brgy->fetch_assoc()) {
    $all_barangays[] = $brgy['barangay'];
  }
}

// Get list of people user has messaged with (conversations)
$conversations = [];
if ($user_role === 'admin') {
  // Admin can see all conversations, optionally filtered by barangay
  if ($filter_barangay) {
    $stmt_convs = $conn->prepare("
      SELECT DISTINCT 
        CASE 
          WHEN m.sender_id IN (SELECT id FROM users WHERE barangay = ?) THEN m.sender_id
          ELSE m.receiver_id 
        END as contact_id
      FROM messages m
      JOIN users u1 ON m.sender_id = u1.id
      JOIN users u2 ON m.receiver_id = u2.id
      WHERE u1.barangay = ? OR u2.barangay = ?
    ");
    $stmt_convs->bind_param('sss', $filter_barangay, $filter_barangay, $filter_barangay);
  } else {
    $stmt_convs = $conn->prepare("
      SELECT DISTINCT 
        CASE 
          WHEN sender_id = ? THEN receiver_id 
          WHEN receiver_id = ? THEN sender_id
          ELSE sender_id
        END as contact_id
      FROM messages 
      WHERE sender_id = ? OR receiver_id = ?
      UNION
      SELECT DISTINCT sender_id as contact_id FROM messages WHERE sender_id != ?
      UNION
      SELECT DISTINCT receiver_id as contact_id FROM messages WHERE receiver_id != ?
    ");
    $stmt_convs->bind_param('iiiiii', $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
  }
} else {
  $stmt_convs = $conn->prepare("
    SELECT DISTINCT 
      CASE 
        WHEN sender_id = ? THEN receiver_id 
        ELSE sender_id 
      END as contact_id
    FROM messages 
    WHERE sender_id = ? OR receiver_id = ?
  ");
  $stmt_convs->bind_param('iii', $user_id, $user_id, $user_id);
}

$stmt_convs->execute();
$convs_result = $stmt_convs->get_result();
while ($conv = $convs_result->fetch_assoc()) {
  $contact_id = $conv['contact_id'];
  
  // Get contact info and unread count
  if ($user_role === 'admin') {
    $stmt_contact = $conn->prepare("
      SELECT u.id, u.firstName, u.lastName, u.middleName, u.role, u.barangay,
             (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND is_read = 0) as unread_count,
             (SELECT message FROM messages WHERE (sender_id = u.id) OR (receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message,
             (SELECT created_at FROM messages WHERE (sender_id = u.id) OR (receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message_time
      FROM users u 
      WHERE u.id = ?
    ");
    $stmt_contact->bind_param('i', $contact_id);
  } else {
    $stmt_contact = $conn->prepare("
      SELECT u.id, u.firstName, u.lastName, u.middleName, u.role, u.barangay,
             (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count,
             (SELECT message FROM messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message,
             (SELECT created_at FROM messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message_time
      FROM users u 
      WHERE u.id = ?
    ");
    $stmt_contact->bind_param('iiiiii', $user_id, $user_id, $user_id, $user_id, $user_id, $contact_id);
  }
  
  $stmt_contact->execute();
  $contact_data = $stmt_contact->get_result()->fetch_assoc();
  $stmt_contact->close();
  
  if ($contact_data && $contact_data['id'] != $user_id) {
    $conversations[] = $contact_data;
  }
}
$stmt_convs->close();

// Sort conversations by last message time
usort($conversations, function($a, $b) {
  return strtotime($b['last_message_time'] ?? '0') - strtotime($a['last_message_time'] ?? '0');
});

// Get available users to message
$available_users = [];
if ($user_role === 'resident') {
  // Residents can message officials from their barangay
  $stmt_avail = $conn->prepare("SELECT id, firstName, lastName, middleName, role, barangay FROM users WHERE (role = 'official' OR role = 'admin') AND barangay = ? AND id != ? ORDER BY lastName, firstName");
  $stmt_avail->bind_param('si', $user_data['barangay'], $user_id);
} elseif ($user_role === 'admin') {
  // Admins can message anyone
  $stmt_avail = $conn->prepare("SELECT id, firstName, lastName, middleName, role, barangay FROM users WHERE id != ? ORDER BY barangay, role, lastName, firstName");
  $stmt_avail->bind_param('i', $user_id);
} else {
  // Officials can message anyone from their barangay
  $stmt_avail = $conn->prepare("SELECT id, firstName, lastName, middleName, role, barangay FROM users WHERE barangay = ? AND id != ? ORDER BY role, lastName, firstName");
  $stmt_avail->bind_param('si', $user_data['barangay'], $user_id);
}
$stmt_avail->execute();
$avail_result = $stmt_avail->get_result();
while ($user = $avail_result->fetch_assoc()) {
  $available_users[] = $user;
}
$stmt_avail->close();

// Get messages for current conversation
$messages = [];
if ($conversation_user) {
  if ($user_role === 'admin') {
    // Admin can see all messages involving this user
    $stmt_msgs = $conn->prepare("
      SELECT m.*, 
             s.firstName as sender_firstName, s.lastName as sender_lastName, s.role as sender_role,
             r.firstName as receiver_firstName, r.lastName as receiver_lastName, r.role as receiver_role
      FROM messages m
      JOIN users s ON m.sender_id = s.id
      JOIN users r ON m.receiver_id = r.id
      WHERE m.sender_id = ? OR m.receiver_id = ?
      ORDER BY m.created_at ASC
    ");
    $stmt_msgs->bind_param('ii', $conversation_user_id, $conversation_user_id);
  } else {
    $stmt_msgs = $conn->prepare("
      SELECT m.*, 
             s.firstName as sender_firstName, s.lastName as sender_lastName, s.role as sender_role,
             r.firstName as receiver_firstName, r.lastName as receiver_lastName, r.role as receiver_role
      FROM messages m
      JOIN users s ON m.sender_id = s.id
      JOIN users r ON m.receiver_id = r.id
      WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
      ORDER BY m.created_at ASC
    ");
    $stmt_msgs->bind_param('iiii', $user_id, $conversation_user_id, $conversation_user_id, $user_id);
  }
  $stmt_msgs->execute();
  $messages = $stmt_msgs->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt_msgs->close();
}

// Count total unread messages
$stmt_unread = $conn->prepare("SELECT COUNT(*) as unread FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt_unread->bind_param('i', $user_id);
$stmt_unread->execute();
$unread_total = $stmt_unread->get_result()->fetch_assoc()['unread'];
$stmt_unread->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    body { background-color: #f8f9fa; }
    .conversation-list { height: calc(100vh - 250px); overflow-y: auto; }
    .message-container { height: calc(100vh - 350px); overflow-y: auto; }
    .conversation-item { cursor: pointer; transition: background-color 0.2s; }
    .conversation-item:hover { background-color: #f8f9fa; }
    .conversation-item.active { background-color: #e9ecef; border-left: 3px solid #000; }
    .message-bubble { max-width: 70%; word-wrap: break-word; }
    .message-sent { background-color: #000; color: #fff; }
    .message-received { background-color: #e9ecef; color: #000; }
    .unread-badge { background-color: #dc3545; }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="<?= $user_role === 'admin' ? '/dashboards/adminDashboard.php' : ($user_role === 'official' ? '/dashboards/officialDashboard.php' : '/dashboards/userDashboard.php') ?>">
        <i class="bi bi-chat-dots-fill me-2"></i>Barangay <?= $barangay ?>
      </a>
      <div class="d-flex gap-3">
        <span class="text-white">
          <i class="bi bi-envelope-fill me-1"></i>
          <?php if ($unread_total > 0): ?>
            <span class="badge bg-danger"><?= $unread_total ?></span>
          <?php endif; ?>
        </span>
        <a class="nav-link text-white" href="<?= $user_role === 'admin' ? '/dashboards/adminDashboard.php' : ($user_role === 'official' ? '/dashboards/officialDashboard.php' : '/dashboards/userDashboard.php') ?>">
          <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
      </div>
    </div>
  </nav>

  <div class="container-fluid py-4">
    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['type']) ?> alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="row">
      <!-- Conversations List -->
      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-chat-dots-fill me-2"></i>Messages</h5>
          </div>
          <?php if ($user_role === 'admin' && count($all_barangays) > 0): ?>
            <div class="card-body border-bottom">
              <form method="get" action="/messages.php">
                <label class="form-label small mb-1">Filter by Barangay:</label>
                <select name="filter_barangay" class="form-select form-select-sm" onchange="this.form.submit()">
                  <option value="">All Barangays</option>
                  <?php foreach ($all_barangays as $brgy): ?>
                    <option value="<?= htmlspecialchars($brgy) ?>" <?= $filter_barangay === $brgy ? 'selected' : '' ?>>
                      <?= htmlspecialchars($brgy) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </form>
            </div>
          <?php endif; ?>
          <div class="card-body p-0">
            <div class="conversation-list">
              <?php if (count($conversations) > 0): ?>
                <?php foreach ($conversations as $conv): ?>
                  <a href="/messages.php?conversation=<?= $conv['id'] ?>" class="text-decoration-none text-dark">
                    <div class="conversation-item p-3 border-bottom <?= $conversation_user_id === $conv['id'] ? 'active' : '' ?>">
                      <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                          <h6 class="mb-1">
                            <?= htmlspecialchars($conv['firstName'] . ' ' . $conv['lastName']) ?>
                            <span class="badge bg-secondary ms-2"><?= ucfirst($conv['role']) ?></span>
                          </h6>
                          <?php if ($user_role === 'admin' && !empty($conv['barangay'])): ?>
                            <p class="mb-1 text-muted small">
                              <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($conv['barangay']) ?>
                            </p>
                          <?php endif; ?>
                          <p class="mb-0 text-muted small text-truncate" style="max-width: 200px;">
                            <?= htmlspecialchars(substr($conv['last_message'] ?? '', 0, 50)) ?><?= strlen($conv['last_message'] ?? '') > 50 ? '...' : '' ?>
                          </p>
                          <small class="text-muted">
                            <?php 
                              $time_diff = time() - strtotime($conv['last_message_time'] ?? '0');
                              if ($time_diff < 60) echo 'Just now';
                              elseif ($time_diff < 3600) echo floor($time_diff / 60) . ' min ago';
                              elseif ($time_diff < 86400) echo floor($time_diff / 3600) . ' hr ago';
                              else echo date('M d', strtotime($conv['last_message_time']));
                            ?>
                          </small>
                        </div>
                        <?php if ($conv['unread_count'] > 0): ?>
                          <span class="badge rounded-pill unread-badge"><?= $conv['unread_count'] ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="p-4 text-center text-muted">
                  <i class="bi bi-inbox display-4 d-block mb-3"></i>
                  <p>No conversations yet. Start a new message!</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="card-footer">
            <button class="btn btn-dark w-100" data-bs-toggle="modal" data-bs-target="#newMessageModal">
              <i class="bi bi-plus-circle me-2"></i>New Message
            </button>
          </div>
        </div>
      </div>

      <!-- Message Thread -->
      <div class="col-md-8">
        <div class="card shadow-sm">
          <?php if ($conversation_user): ?>
            <div class="card-header bg-dark text-white">
              <h5 class="mb-0">
                <i class="bi bi-person-circle me-2"></i>
                <?php if ($user_role === 'admin'): ?>
                  Messages involving: <?= htmlspecialchars($conversation_user['firstName'] . ' ' . $conversation_user['lastName']) ?>
                  <span class="badge bg-light text-dark ms-2"><?= ucfirst($conversation_user['role']) ?></span>
                  <?php if (!empty($conversation_user['barangay'])): ?>
                    <span class="badge bg-info ms-1"><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($conversation_user['barangay']) ?></span>
                  <?php endif; ?>
                <?php else: ?>
                  <?= htmlspecialchars($conversation_user['firstName'] . ' ' . $conversation_user['lastName']) ?>
                  <span class="badge bg-light text-dark ms-2"><?= ucfirst($conversation_user['role']) ?></span>
                <?php endif; ?>
              </h5>
            </div>
            <div class="card-body message-container" id="messageContainer">
              <?php if (count($messages) > 0): ?>
                <?php foreach ($messages as $msg): ?>
                  <?php 
                    $is_sent_by_me = ($msg['sender_id'] == $user_id);
                    $is_admin_view = ($user_role === 'admin');
                  ?>
                  <div class="mb-3 <?= $is_sent_by_me ? 'text-end' : '' ?>">
                    <?php if ($is_admin_view): ?>
                      <small class="text-muted d-block mb-1">
                        <strong><?= htmlspecialchars($msg['sender_firstName'] . ' ' . $msg['sender_lastName']) ?></strong>
                        <i class="bi bi-arrow-right mx-1"></i>
                        <strong><?= htmlspecialchars($msg['receiver_firstName'] . ' ' . $msg['receiver_lastName']) ?></strong>
                      </small>
                    <?php endif; ?>
                    <div class="d-inline-block message-bubble p-3 rounded <?= $is_sent_by_me ? 'message-sent' : 'message-received' ?>">
                      <p class="mb-1"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                      <small class="<?= $is_sent_by_me ? 'text-white-50' : 'text-muted' ?>">
                        <?= date('M d, Y g:i A', strtotime($msg['created_at'])) ?>
                      </small>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="text-center text-muted py-5">
                  <i class="bi bi-chat-left-text display-4 d-block mb-3"></i>
                  <p>No messages yet. Start the conversation!</p>
                </div>
              <?php endif; ?>
            </div>
            <div class="card-footer">
              <form method="post" action="/messages.php" class="d-flex gap-2">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="send_message">
                <input type="hidden" name="receiver_id" value="<?= $conversation_user_id ?>">
                <textarea name="message" class="form-control" rows="2" placeholder="Type your message..." required></textarea>
                <button type="submit" class="btn btn-dark">
                  <i class="bi bi-send-fill"></i> Send
                </button>
              </form>
            </div>
          <?php else: ?>
            <div class="card-body text-center py-5">
              <i class="bi bi-chat-square-dots display-1 text-muted mb-4"></i>
              <h5 class="text-muted">Select a conversation or start a new message</h5>
              <button class="btn btn-dark mt-3" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                <i class="bi bi-plus-circle me-2"></i>Start New Message
              </button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- New Message Modal -->
  <div class="modal fade" id="newMessageModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-envelope me-2"></i>New Message</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post" action="/messages.php">
          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="send_message">
            <div class="mb-3">
              <label class="form-label">To:</label>
              <select name="receiver_id" class="form-select" required>
                <option value="">Select recipient...</option>
                <?php foreach ($available_users as $avail_user): ?>
                  <option value="<?= $avail_user['id'] ?>">
                    <?= htmlspecialchars($avail_user['firstName'] . ' ' . $avail_user['lastName']) ?> 
                    (<?= ucfirst($avail_user['role']) ?>)
                    <?php if ($user_role === 'admin' && !empty($avail_user['barangay'])): ?>
                      - <?= htmlspecialchars($avail_user['barangay']) ?>
                    <?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Message:</label>
              <textarea name="message" class="form-control" rows="4" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark"><i class="bi bi-send-fill me-2"></i>Send Message</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Auto-scroll to bottom of messages
    const messageContainer = document.getElementById('messageContainer');
    if (messageContainer) {
      messageContainer.scrollTop = messageContainer.scrollHeight;
    }

    // Auto-refresh every 30 seconds if in a conversation
    <?php if ($conversation_user): ?>
    setInterval(() => {
      location.reload();
    }, 30000);
    <?php endif; ?>
  </script>
</body>
</html>
