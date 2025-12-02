<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../userAccounts/config.php';

// Fetch all regions
$stmt = $conn->query("SELECT id, name, code FROM regions ORDER BY name ASC");
$regions = [];
while ($row = $stmt->fetch_assoc()) {
  $regions[] = $row;
}

echo json_encode(['success' => true, 'regions' => $regions]);
?>
