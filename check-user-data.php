<?php
require_once __DIR__ . '/userAccounts/config.php';

// Get specific user data
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

$query = "SELECT id, firstName, lastName, region, region_id, province, province_id, 
                 cityMunicipality, municipality_id, barangay, barangay_id 
          FROM users WHERE id = $user_id";

$result = $conn->query($query);
$user = $result->fetch_assoc();

echo "<h2>User Data for ID: $user_id</h2>";
echo "<h3>{$user['firstName']} {$user['lastName']}</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Name Value</th><th>ID Value</th></tr>";
echo "<tr><td>Region</td><td>" . htmlspecialchars($user['region'] ?? 'NULL') . "</td><td>" . ($user['region_id'] ?? 'NULL') . "</td></tr>";
echo "<tr><td>Province</td><td>" . htmlspecialchars($user['province'] ?? 'NULL') . "</td><td>" . ($user['province_id'] ?? 'NULL') . "</td></tr>";
echo "<tr><td>Municipality</td><td>" . htmlspecialchars($user['cityMunicipality'] ?? 'NULL') . "</td><td>" . ($user['municipality_id'] ?? 'NULL') . "</td></tr>";
echo "<tr><td>Barangay</td><td>" . htmlspecialchars($user['barangay'] ?? 'NULL') . "</td><td>" . ($user['barangay_id'] ?? 'NULL') . "</td></tr>";
echo "</table>";

// Now check what's in the location tables
if ($user['region_id']) {
  echo "<h3>Looking up Region ID: {$user['region_id']}</h3>";
  $region = $conn->query("SELECT * FROM regions WHERE id = " . (int)$user['region_id']);
  if ($region && $region->num_rows > 0) {
    $r = $region->fetch_assoc();
    echo "Found: " . htmlspecialchars($r['name']) . "<br>";
  } else {
    echo "Not found in regions table<br>";
  }
}

if ($user['province_id']) {
  echo "<h3>Looking up Province ID: {$user['province_id']}</h3>";
  $province = $conn->query("SELECT * FROM provinces WHERE id = " . (int)$user['province_id']);
  if ($province && $province->num_rows > 0) {
    $p = $province->fetch_assoc();
    echo "Found: " . htmlspecialchars($p['name']) . "<br>";
  } else {
    echo "Not found in provinces table<br>";
  }
}

if ($user['municipality_id']) {
  echo "<h3>Looking up Municipality ID: {$user['municipality_id']}</h3>";
  $municipality = $conn->query("SELECT * FROM municipalities WHERE id = " . (int)$user['municipality_id']);
  if ($municipality && $municipality->num_rows > 0) {
    $m = $municipality->fetch_assoc();
    echo "Found: " . htmlspecialchars($m['name']) . "<br>";
  } else {
    echo "Not found in municipalities table<br>";
  }
}

if ($user['barangay_id']) {
  echo "<h3>Looking up Barangay ID: {$user['barangay_id']}</h3>";
  $barangay = $conn->query("SELECT * FROM barangays WHERE id = " . (int)$user['barangay_id']);
  if ($barangay && $barangay->num_rows > 0) {
    $b = $barangay->fetch_assoc();
    echo "Found: " . htmlspecialchars($b['name']) . "<br>";
  } else {
    echo "Not found in barangays table<br>";
  }
}

echo "<br><br><a href='check-user-data.php?id=" . ($user_id + 1) . "'>Next User</a>";
?>
