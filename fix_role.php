<?php
// Temporary script to update user role to 'official'
require_once 'userAccounts/config.php';

// Get the current logged-in user or specify username
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// You can either use the session username or specify it directly
$username = $_SESSION['username'] ?? 'Jckintanar'; // Change this to your username if different

// Update the user role to 'official'
$stmt = $conn->prepare("UPDATE users SET role = 'official' WHERE username = ?");
$stmt->bind_param("s", $username);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "✅ Successfully updated user '$username' role to 'official'!<br>";
        echo "You can now access the official dashboard.<br><br>";
        
        // Update session if this is the current user
        if (isset($_SESSION['username']) && $_SESSION['username'] === $username) {
            $_SESSION['role'] = 'official';
            echo "✅ Session role updated too!<br>";
        }
        
        echo "<a href='/dashboards/officialDashboard.php' class='btn btn-success'>Go to Official Dashboard</a><br><br>";
        echo "<a href='/dashboards/userDashboard.php' class='btn btn-primary'>Go to User Dashboard</a>";
    } else {
        echo "❌ No user found with username '$username'";
    }
} else {
    echo "❌ Error updating role: " . $conn->error;
}

$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix User Role</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <div class="alert alert-info">
        <h4>Role Update Complete</h4>
        <p>You can delete this file (fix_role.php) after use.</p>
    </div>
</body>
</html>