<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../userAccounts/config.php';

// Validate region_id parameter
$region_id = isset($_GET['region_id']) ? (int)$_GET['region_id'] : 0;

if ($region_id <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid region ID']);
  exit;
}

// Fetch provinces for the selected region
$stmt = $conn->prepare("SELECT id, name FROM provinces WHERE region_id = ? ORDER BY name ASC");
$stmt->bind_param('i', $region_id);
$stmt->execute();
$result = $stmt->get_result();

$provinces = [];
while ($row = $result->fetch_assoc()) {
  $provinces[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'provinces' => $provinces]);
?>
