<?php
require_once __DIR__ . '/userAccounts/config.php';

// Enable this to run the script
$CONFIRM_FIX = true;

if (!$CONFIRM_FIX) {
  die("Script is disabled. Set \$CONFIRM_FIX = true to run this script.");
}

echo "<h2>Fixing Empty Civil Status Values</h2>";

// Find all users with empty or NULL civil status
$query = "SELECT id, firstName, lastName, civilStatus FROM users 
          WHERE civilStatus IS NULL OR civilStatus = '' OR civilStatus = '0'";

$result = $conn->query($query);

if ($result->num_rows == 0) {
  echo "<p style='color: green; font-weight: bold;'>✓ No users with empty civil status found!</p>";
  echo "<p><a href='/userProfile.php'>Go to Profile</a> | <a href='/userManagement.php'>User Management</a></p>";
  exit;
}

echo "<p>Found <strong>{$result->num_rows}</strong> users with empty civil status.</p>";
echo "<p>Setting all to 'Single' as default...</p>";

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #333; color: white;'><th>ID</th><th>Name</th><th>Old Value</th><th>New Value</th><th>Status</th></tr>";

$updated = 0;
$failed = 0;

while ($user = $result->fetch_assoc()) {
  $user_id = $user['id'];
  $old_value = $user['civilStatus'] ?: '(empty)';
  $new_value = 'Single';
  
  $update = $conn->prepare("UPDATE users SET civilStatus = ? WHERE id = ?");
  $update->bind_param('si', $new_value, $user_id);
  
  if ($update->execute()) {
    echo "<tr>";
    echo "<td>{$user_id}</td>";
    echo "<td>{$user['firstName']} {$user['lastName']}</td>";
    echo "<td style='color: red;'>{$old_value}</td>";
    echo "<td style='color: green;'>{$new_value}</td>";
    echo "<td style='color: green;'>✓ Updated</td>";
    echo "</tr>";
    $updated++;
  } else {
    echo "<tr>";
    echo "<td>{$user_id}</td>";
    echo "<td>{$user['firstName']} {$user['lastName']}</td>";
    echo "<td>{$old_value}</td>";
    echo "<td>-</td>";
    echo "<td style='color: red;'>✗ Failed</td>";
    echo "</tr>";
    $failed++;
  }
  
  $update->close();
}

echo "</table>";

echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>Summary</h3>";
echo "<p><strong>Successfully updated:</strong> $updated users</p>";
echo "<p><strong>Failed:</strong> $failed users</p>";
echo "<p style='color: green; font-weight: bold;'>✓ All users now have civil status set!</p>";
echo "</div>";

echo "<p><a href='/userProfile.php' style='padding: 10px 20px; background: #333; color: white; text-decoration: none; border-radius: 5px;'>Go to Profile</a></p>";

echo "<p style='color: red; margin-top: 30px;'><strong>Remember:</strong> Set \$CONFIRM_FIX = false in this file to disable it.</p>";

$conn->close();
?>
