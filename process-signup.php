<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';

// CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
  // Store all form data in session for repopulation
  $_SESSION['signup_data'] = $_POST;
  
  $lastName = $conn->real_escape_string($_POST['lastName']);
  $firstName = $conn->real_escape_string($_POST['firstName']);
  $middleName = $conn->real_escape_string($_POST['middleName']);
  $username = $conn->real_escape_string($_POST['username']);
  $email = $conn->real_escape_string($_POST['email']);
  $phoneNumber = $conn->real_escape_string($_POST['phoneNumber']);
  $dateOfBirth = $conn->real_escape_string($_POST['dateOfBirth']);
  $civilStatus = $conn->real_escape_string($_POST['civilStatus']);
  $yearResidency = (int)$_POST['yearResidency'];
  $sitio = isset($_POST['sitio']) ? $conn->real_escape_string($_POST['sitio']) : '';
  $region_id = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null;
  $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : null;
  $municipality_id = isset($_POST['municipality_id']) ? (int)$_POST['municipality_id'] : null;
  $barangay_id = isset($_POST['barangay_id']) ? (int)$_POST['barangay_id'] : null;
  $password = $_POST['password'];
  $confirmPassword = $_POST['confirmPassword'];

  // Validate location IDs are provided
  if (!$region_id || !$province_id || !$municipality_id || !$barangay_id) {
    $_SESSION['signup_error'] = "Please select all location fields (Region, Province, Municipality/City, and Barangay).";
    header('Location: /pages/Signup.php');
    exit;
  }

  // Fetch location names from database using IDs
  $region = '';
  $province = '';
  $cityMunicipality = '';
  $barangay = '';
  
  $region_result = $conn->query("SELECT name FROM regions WHERE id = $region_id");
  if ($region_result && $row = $region_result->fetch_assoc()) {
    $region = $conn->real_escape_string($row['name']);
  }
  
  $province_result = $conn->query("SELECT name FROM provinces WHERE id = $province_id");
  if ($province_result && $row = $province_result->fetch_assoc()) {
    $province = $conn->real_escape_string($row['name']);
  }
  
  $municipality_result = $conn->query("SELECT name FROM municipalities WHERE id = $municipality_id");
  if ($municipality_result && $row = $municipality_result->fetch_assoc()) {
    $cityMunicipality = $conn->real_escape_string($row['name']);
  }
  
  $barangay_result = $conn->query("SELECT name FROM barangays WHERE id = $barangay_id");
  if ($barangay_result && $row = $barangay_result->fetch_assoc()) {
    $barangay = $conn->real_escape_string($row['name']);
  }

  // Verify we got all location names
  if (empty($region) || empty($province) || empty($cityMunicipality) || empty($barangay)) {
    $_SESSION['signup_error'] = "Invalid location selection. Please select all location fields again.";
    header('Location: /pages/Signup.php');
    exit;
  }

  // Validate phone number (must be exactly 11 digits)
  if (!preg_match('/^\d{11}$/', $phoneNumber)) {
    $_SESSION['signup_error'] = "Mobile number must be exactly 11 digits. You entered " . strlen($phoneNumber) . " characters.";
    header('Location: /pages/Signup.php');
    exit;
  }

  if ($password !== $confirmPassword) {
    $_SESSION['signup_error'] = "Passwords do not match! Please check both password fields.";
    header('Location: /pages/Signup.php');
    exit;
  }
  
  $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
  
  // Check for existing username, email, or phone number
  $check = $conn->query("SELECT username, email, phoneNumber FROM users WHERE username='$username' OR email='$email' OR phoneNumber='$phoneNumber'");
  if ($check->num_rows > 0) {
    $existing = $check->fetch_assoc();
    if ($existing['username'] === $username) {
      $_SESSION['signup_error'] = "Username '$username' is already taken. Please choose a different username.";
    } elseif ($existing['email'] === $email) {
      $_SESSION['signup_error'] = "Email '$email' is already registered. Please use a different email or login to your existing account.";
    } elseif ($existing['phoneNumber'] === $phoneNumber) {
      $_SESSION['signup_error'] = "Phone number '$phoneNumber' is already registered. Please use a different phone number.";
    }
    header('Location: /pages/Signup.php');
    exit;
  }
  
  $stmt = $conn->prepare("INSERT INTO users (lastName, firstName, middleName, username, email, phoneNumber, dateOfBirth, civilStatus, yearResidency, cityMunicipality, municipality_id, barangay, barangay_id, province, province_id, region, region_id, sitio, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssssssissisisisss", $lastName, $firstName, $middleName, $username, $email, $phoneNumber, $dateOfBirth, $civilStatus, $yearResidency, $cityMunicipality, $municipality_id, $barangay, $barangay_id, $province, $province_id, $region, $region_id, $sitio, $hashedPassword);
  
  if ($stmt->execute()) {
    // Clear stored form data on success
    unset($_SESSION['signup_data']);
    $_SESSION['signup_success'] = "Account created successfully! You can now log in.";
    header('Location: /pages/Signup.php');
    exit;
  } else {
    $_SESSION['signup_error'] = "Error creating account: " . $stmt->error . ". Please check all fields and try again.";
    header('Location: /pages/Signup.php');
    exit;
  }
  $stmt->close();
} else {
  header('Location: /pages/Signup.php');
  exit;
}
?>
