<?php
require_once __DIR__ . '/userAccounts/config.php';

echo "<h2>Checking Civil Status Field</h2>";

$result = $conn->query("SELECT id, firstName, lastName, civilStatus FROM users ORDER BY id DESC LIMIT 10");

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Civil Status</th><th>Status</th></tr>";

while ($user = $result->fetch_assoc()) {
    $status = $user['civilStatus'];
    $display = htmlspecialchars($status ?? 'NULL');
    
    $color = 'black';
    if (empty($status)) {
        $color = 'red';
        $display = 'EMPTY';
    } else if ($status === '0' || $status === 'Not Set') {
        $color = 'orange';
    } else {
        $color = 'green';
    }
    
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['firstName']} {$user['lastName']}</td>";
    echo "<td style='color: $color; font-weight: bold;'>{$display}</td>";
    echo "<td>" . (empty($status) ? '❌ Empty' : '✓ Has Value') . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Fixing Empty Civil Status Values</h3>";
echo "<p>Would you like to set all empty civil status values to 'Single' as default?</p>";
echo "<a href='fix-civil-status.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Fix Empty Civil Status Values</a>";
?>
