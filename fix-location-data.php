<?php
/**
 * Fix Location Data Script
 * This script updates users with "0" or empty location names by fetching
 * the proper names from the philippine_regions, provinces, municipalities, and barangays tables
 */

require_once __DIR__ . '/userAccounts/config.php';

// Enable this to run the script (set to false after running)
$CONFIRM_FIX = true;

if (!$CONFIRM_FIX) {
  die("Script is disabled. Set \$CONFIRM_FIX = true to run this script.\n");
}

echo "<h2>Fixing Location Data for Users</h2>";
echo "<p>Starting fix process...</p>";

// Get all users with location IDs
$query = "SELECT id, region_id, province_id, municipality_id, barangay_id, region, province, cityMunicipality, barangay 
          FROM users 
          WHERE region_id IS NOT NULL OR province_id IS NOT NULL OR municipality_id IS NOT NULL OR barangay_id IS NOT NULL";

$result = $conn->query($query);

if (!$result) {
  die("Error fetching users: " . $conn->error);
}

$updated_count = 0;
$error_count = 0;

echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; margin-top: 20px;'>";
echo "<tr style='background-color: #333; color: white;'>
        <th>User ID</th>
        <th>Field</th>
        <th>Old Value</th>
        <th>New Value</th>
        <th>Status</th>
      </tr>";

while ($user = $result->fetch_assoc()) {
  $user_id = $user['id'];
  $updates = [];
  $update_fields = [];
  
  // Fix Region
  if ($user['region_id'] && (empty($user['region']) || $user['region'] === '0' || is_numeric($user['region']))) {
    $region_query = $conn->query("SELECT name FROM regions WHERE id = " . (int)$user['region_id']);
    if ($region_query && $region_row = $region_query->fetch_assoc()) {
      $old_region = $user['region'];
      $new_region = $region_row['name'];
      $updates[] = "region = '" . $conn->real_escape_string($new_region) . "'";
      $update_fields[] = ['field' => 'Region', 'old' => $old_region, 'new' => $new_region];
      
      echo "<tr>
              <td>{$user_id}</td>
              <td>Region</td>
              <td>" . htmlspecialchars($old_region) . "</td>
              <td>" . htmlspecialchars($new_region) . "</td>
              <td style='color: green;'>✓</td>
            </tr>";
    }
  }
  
  // Fix Province
  if ($user['province_id'] && (empty($user['province']) || $user['province'] === '0' || is_numeric($user['province']))) {
    $province_query = $conn->query("SELECT name FROM provinces WHERE id = " . (int)$user['province_id']);
    if ($province_query && $province_row = $province_query->fetch_assoc()) {
      $old_province = $user['province'];
      $new_province = $province_row['name'];
      $updates[] = "province = '" . $conn->real_escape_string($new_province) . "'";
      $update_fields[] = ['field' => 'Province', 'old' => $old_province, 'new' => $new_province];
      
      echo "<tr>
              <td>{$user_id}</td>
              <td>Province</td>
              <td>" . htmlspecialchars($old_province) . "</td>
              <td>" . htmlspecialchars($new_province) . "</td>
              <td style='color: green;'>✓</td>
            </tr>";
    }
  }
  
  // Fix Municipality/City
  if ($user['municipality_id'] && (empty($user['cityMunicipality']) || $user['cityMunicipality'] === '0' || is_numeric($user['cityMunicipality']))) {
    $municipality_query = $conn->query("SELECT name FROM municipalities WHERE id = " . (int)$user['municipality_id']);
    if ($municipality_query && $municipality_row = $municipality_query->fetch_assoc()) {
      $old_city = $user['cityMunicipality'];
      $new_city = $municipality_row['name'];
      $updates[] = "cityMunicipality = '" . $conn->real_escape_string($new_city) . "'";
      $update_fields[] = ['field' => 'Municipality/City', 'old' => $old_city, 'new' => $new_city];
      
      echo "<tr>
              <td>{$user_id}</td>
              <td>Municipality/City</td>
              <td>" . htmlspecialchars($old_city) . "</td>
              <td>" . htmlspecialchars($new_city) . "</td>
              <td style='color: green;'>✓</td>
            </tr>";
    }
  }
  
  // Fix Barangay
  if ($user['barangay_id'] && (empty($user['barangay']) || $user['barangay'] === '0' || is_numeric($user['barangay']))) {
    $barangay_query = $conn->query("SELECT name FROM barangays WHERE id = " . (int)$user['barangay_id']);
    if ($barangay_query && $barangay_row = $barangay_query->fetch_assoc()) {
      $old_barangay = $user['barangay'];
      $new_barangay = $barangay_row['name'];
      $updates[] = "barangay = '" . $conn->real_escape_string($new_barangay) . "'";
      $update_fields[] = ['field' => 'Barangay', 'old' => $old_barangay, 'new' => $new_barangay];
      
      echo "<tr>
              <td>{$user_id}</td>
              <td>Barangay</td>
              <td>" . htmlspecialchars($old_barangay) . "</td>
              <td>" . htmlspecialchars($new_barangay) . "</td>
              <td style='color: green;'>✓</td>
            </tr>";
    }
  }
  
  // Execute update if there are changes
  if (!empty($updates)) {
    $update_sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = $user_id";
    
    if ($conn->query($update_sql)) {
      $updated_count++;
    } else {
      $error_count++;
      echo "<tr>
              <td>{$user_id}</td>
              <td colspan='3'>ERROR</td>
              <td style='color: red;'>✗ " . htmlspecialchars($conn->error) . "</td>
            </tr>";
    }
  }
}

echo "</table>";

echo "<div style='margin-top: 20px; padding: 15px; background-color: #f0f0f0; border-radius: 5px;'>";
echo "<h3>Summary</h3>";
echo "<p><strong>Total users updated:</strong> $updated_count</p>";
echo "<p><strong>Errors encountered:</strong> $error_count</p>";
echo "<p style='color: green;'><strong>✓ Fix completed successfully!</strong></p>";
echo "</div>";

echo "<p style='margin-top: 20px;'><a href='/userManagement.php' style='padding: 10px 20px; background-color: #333; color: white; text-decoration: none; border-radius: 5px;'>Go to User Management</a></p>";

// Disable the script after running
echo "<p style='color: red; margin-top: 20px;'><strong>Important:</strong> Set \$CONFIRM_FIX = false in this script to prevent accidental re-runs.</p>";

$conn->close();
?>
