<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';

// CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
  $lastName = $conn->real_escape_string($_POST['lastName']);
  $firstName = $conn->real_escape_string($_POST['firstName']);
  $middleName = $conn->real_escape_string($_POST['middleName']);
  $username = $conn->real_escape_string($_POST['username']);
  $email = $conn->real_escape_string($_POST['email']);
  $phoneNumber = $conn->real_escape_string($_POST['phoneNumber']);
  $cityMunicipality = $conn->real_escape_string($_POST['cityMunicipality']);
  $barangay = $conn->real_escape_string($_POST['barangay']);
  $province = $conn->real_escape_string($_POST['province']);
  $region = $conn->real_escape_string($_POST['region']);
  $sitio = isset($_POST['sitio']) ? $conn->real_escape_string($_POST['sitio']) : '';
  $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null;
  $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : null;
  $municipality_id = isset($_POST['municipality_id']) ? (int)$_POST['municipality_id'] : null;
  $barangay_id = isset($_POST['barangay_id']) ? (int)$_POST['barangay_id'] : null;
  $password = $_POST['password'];
  $confirmPassword = $_POST['confirmPassword'];

  if ($password !== $confirmPassword) {
    $_SESSION['signup_error'] = "Passwords do not match!";
    header('Location: /pages/Signup.php');
    exit;
  }
  
  $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
  
  // Check for existing username, email, or phone number
  $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email' OR phoneNumber='$phoneNumber'");
  if ($check->num_rows > 0) {
    $_SESSION['signup_error'] = "Username, email, or phone number already registered!";
    header('Location: /pages/Signup.php');
    exit;
  }
  
  $stmt = $conn->prepare("INSERT INTO users (lastName, firstName, middleName, username, email, phoneNumber, cityMunicipality, municipality_id, barangay, barangay_id, province, province_id, region, region_id, sitio, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("sssssssisisissss", $lastName, $firstName, $middleName, $username, $email, $phoneNumber, $cityMunicipality, $municipality_id, $barangay, $barangay_id, $province, $province_id, $region, $region_id, $sitio, $hashedPassword);
  
  if ($stmt->execute()) {
    $_SESSION['signup_success'] = "Account created successfully! You can now log in.";
    header('Location: /pages/Signup.php');
    exit;
  } else {
    $_SESSION['signup_error'] = "Error creating account. Please try again.";
    header('Location: /pages/Signup.php');
    exit;
  }
  $stmt->close();
} else {
  header('Location: /pages/Signup.php');
  exit;
}
?>
