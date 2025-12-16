<?php
require_once __DIR__ . '/userAccounts/config.php';

// Show the last inserted user
$result = $conn->query("SELECT * FROM users ORDER BY id DESC LIMIT 1");
$user = $result->fetch_assoc();

echo "<h2>Last Created User Data</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
foreach ($user as $key => $value) {
    if (strpos($key, 'password') === false) {
        echo "<tr><td>$key</td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
    }
}
echo "</table>";

// Test: What happens when we insert with the current signup logic
echo "<h2>Testing Location Name Fetch</h2>";

// Simulate what process-signup.php does
$test_region_id = 7;
$test_province_id = 72;
$test_municipality_id = 1398;
$test_barangay_id = 22861;

echo "<h3>Fetching names for IDs:</h3>";
echo "Region ID: $test_region_id<br>";
echo "Province ID: $test_province_id<br>";
echo "Municipality ID: $test_municipality_id<br>";
echo "Barangay ID: $test_barangay_id<br>";

$region = '';
$province = '';
$cityMunicipality = '';
$barangay = '';

$region_result = $conn->query("SELECT name FROM regions WHERE id = $test_region_id");
if ($region_result && $row = $region_result->fetch_assoc()) {
  $region = $row['name'];
  echo "<p style='color: green;'>Region: $region</p>";
} else {
  echo "<p style='color: red;'>Region NOT FOUND</p>";
}

$province_result = $conn->query("SELECT name FROM provinces WHERE id = $test_province_id");
if ($province_result && $row = $province_result->fetch_assoc()) {
  $province = $row['name'];
  echo "<p style='color: green;'>Province: $province</p>";
} else {
  echo "<p style='color: red;'>Province NOT FOUND</p>";
}

$municipality_result = $conn->query("SELECT name FROM municipalities WHERE id = $test_municipality_id");
if ($municipality_result && $row = $municipality_result->fetch_assoc()) {
  $cityMunicipality = $row['name'];
  echo "<p style='color: green;'>Municipality: $cityMunicipality</p>";
} else {
  echo "<p style='color: red;'>Municipality NOT FOUND</p>";
}

$barangay_result = $conn->query("SELECT name FROM barangays WHERE id = $test_barangay_id");
if ($barangay_result && $row = $barangay_result->fetch_assoc()) {
  $barangay = $row['name'];
  echo "<p style='color: green;'>Barangay: $barangay</p>";
} else {
  echo "<p style='color: red;'>Barangay NOT FOUND</p>";
}

echo "<h3>Final values that would be saved:</h3>";
echo "Region: '" . htmlspecialchars($region) . "'<br>";
echo "Province: '" . htmlspecialchars($province) . "'<br>";
echo "City: '" . htmlspecialchars($cityMunicipality) . "'<br>";
echo "Barangay: '" . htmlspecialchars($barangay) . "'<br>";

if (empty($region) || empty($province) || empty($cityMunicipality) || empty($barangay)) {
  echo "<p style='color: red; font-weight: bold;'>VALIDATION WOULD FAIL - Some fields are empty</p>";
} else {
  echo "<p style='color: green; font-weight: bold;'>VALIDATION WOULD PASS - All fields have values</p>";
}
?>
