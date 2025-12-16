<?php
require_once __DIR__ . '/userAccounts/config.php';

// Test with sample IDs from Cebu
$test_region_id = 7; // Region VII
$test_province_id = 1226; // Cebu
$test_municipality_id = 1398; // Lapu-Lapu City
$test_barangay_id = 12345; // Example

echo "<h2>Testing Location Lookup</h2>";

echo "<h3>Region ID: $test_region_id</h3>";
$result = $conn->query("SELECT * FROM regions WHERE id = $test_region_id");
if ($result && $row = $result->fetch_assoc()) {
    echo "Found: " . htmlspecialchars($row['name']) . "<br>";
    print_r($row);
} else {
    echo "Not found<br>";
}

echo "<h3>Province ID: $test_province_id</h3>";
$result = $conn->query("SELECT * FROM provinces WHERE id = $test_province_id");
if ($result && $row = $result->fetch_assoc()) {
    echo "Found: " . htmlspecialchars($row['name']) . "<br>";
    print_r($row);
} else {
    echo "Not found<br>";
}

echo "<h3>Municipality ID: $test_municipality_id</h3>";
$result = $conn->query("SELECT * FROM municipalities WHERE id = $test_municipality_id");
if ($result && $row = $result->fetch_assoc()) {
    echo "Found: " . htmlspecialchars($row['name']) . "<br>";
    print_r($row);
} else {
    echo "Not found<br>";
}

echo "<h3>List all regions:</h3>";
$result = $conn->query("SELECT id, name FROM regions LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']} - {$row['name']}<br>";
}
?>
