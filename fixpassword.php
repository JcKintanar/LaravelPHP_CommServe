<?php
include 'useraccounts/Config.php';

// Hash the password "123"
$password = "123";
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Update the database
$username = "Jckintanar";
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
$stmt->bind_param("ss", $hashed, $username);

if ($stmt->execute()) {
    echo "✅ Password updated successfully!<br>";
    echo "You can now login with:<br>";
    echo "Username: <strong>Jckintanar</strong><br>";
    echo "Password: <strong>123</strong><br><br>";
    echo "<a href='Loginpage.php'>Go to Login</a>";
} else {
    echo "❌ Error updating password!";
}

$stmt->close();
?>