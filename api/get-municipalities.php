<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../userAccounts/config.php';

// If province_id is provided, filter by province
$province_id = isset($_GET['province_id']) ? (int)$_GET['province_id'] : 0;

if ($province_id > 0) {
  $stmt = $conn->prepare("SELECT id, name, province FROM municipalities WHERE province_id = ? ORDER BY name ASC");
  $stmt->bind_param('i', $province_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $municipalities = [];
  while ($row = $result->fetch_assoc()) {
    $municipalities[] = $row;
  }
  $stmt->close();
} else {
  // Fetch all municipalities
  $stmt = $conn->query("SELECT id, name, province FROM municipalities ORDER BY name ASC");
  $municipalities = [];
  while ($row = $stmt->fetch_assoc()) {
    $municipalities[] = $row;
  }
}

echo json_encode(['success' => true, 'municipalities' => $municipalities]);
