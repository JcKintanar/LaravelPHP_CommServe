<?php
require_once __DIR__ . '/userAccounts/config.php';

echo "<h2>Checking Location Tables</h2>";

echo "<h3>Regions (first 10):</h3>";
$result = $conn->query("SELECT id, name, code FROM regions ORDER BY id LIMIT 10");
echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>Code</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['code']}</td></tr>";
}
echo "</table>";

echo "<h3>Provinces for Region 1 (first 10):</h3>";
$result = $conn->query("SELECT id, name, region_id FROM provinces WHERE region_id = 1 ORDER BY id LIMIT 10");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>Region ID</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['region_id']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No provinces found for region_id = 1</p>";
}

echo "<h3>Check Province ID 1:</h3>";
$result = $conn->query("SELECT * FROM provinces WHERE id = 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>Province ID 1 not found</p>";
}

echo "<h3>Municipalities for Province 1 (first 10):</h3>";
$result = $conn->query("SELECT id, name, province_id FROM municipalities WHERE province_id = 1 ORDER BY id LIMIT 10");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>Province ID</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['province_id']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No municipalities found for province_id = 1</p>";
}

echo "<h3>Check Municipality ID 3:</h3>";
$result = $conn->query("SELECT * FROM municipalities WHERE id = 3");
if ($result && $row = $result->fetch_assoc()) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>Municipality ID 3 not found</p>";
}

echo "<h3>Barangays for Municipality 3 (first 10):</h3>";
$result = $conn->query("SELECT id, name, municipality_id FROM barangays WHERE municipality_id = 3 ORDER BY id LIMIT 10");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>Municipality ID</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['municipality_id']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No barangays found for municipality_id = 3</p>";
}

echo "<h3>Check Barangay ID 4:</h3>";
$result = $conn->query("SELECT * FROM barangays WHERE id = 4");
if ($result && $row = $result->fetch_assoc()) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>Barangay ID 4 not found</p>";
}
?>
