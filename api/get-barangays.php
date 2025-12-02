<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../userAccounts/config.php';

// Validate municipality_id parameter
$municipality_id = isset($_GET['municipality_id']) ? (int)$_GET['municipality_id'] : 0;

if ($municipality_id <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid municipality ID']);
  exit;
}

// Fetch barangays for the selected municipality
$stmt = $conn->prepare("SELECT id, name FROM barangays WHERE municipality_id = ? ORDER BY name ASC");
$stmt->bind_param('i', $municipality_id);
$stmt->execute();
$result = $stmt->get_result();

$barangays = [];
while ($row = $result->fetch_assoc()) {
  $barangays[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'barangays' => $barangays]);
