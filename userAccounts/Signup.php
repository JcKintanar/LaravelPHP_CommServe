<?php
session_start();
include 'Config.php';

// CREATE
$msg = '';
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
  $password = $_POST['password'];
  $confirmPassword = $_POST['confirmPassword'];

    if ($password !== $confirmPassword) {
        $_SESSION['signup_error'] = "Passwords do not match!";
        header('Location: /pages/Signup.php');
        exit;
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        // Check for existing username, email, or phone number
        $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email' OR phoneNumber='$phoneNumber'");
        if ($check->num_rows > 0) {
            $_SESSION['signup_error'] = "Username, email, or phone number already registered!";
            header('Location: /pages/Signup.php');
            exit;
        } else {
            $stmt = $conn->prepare("INSERT INTO users (lastName, firstName, middleName, username, email, phoneNumber, cityMunicipality, barangay, province, region, sitio, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssss", $lastName, $firstName, $middleName, $username, $email, $phoneNumber, $cityMunicipality, $barangay, $province, $region, $sitio, $hashedPassword);
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
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up - CommServe</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato&display=swap" rel="stylesheet">

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="font-family: 'Lato', sans-serif; background-color: #f7f7f7;">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/landingpage.php">
      <i class="bi bi-people-fill me-2"></i>CommServe
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
  </div>
</nav>

<!-- Sign Up Section -->
<section class="d-flex align-items-center justify-content-center vh-100">
  <div class="card p-4 shadow-sm rounded-4" style="max-width: 650px; width: 100%;">
    <h3 class="text-center fw-bold mb-3" style="font-family: 'Montserrat', sans-serif;">
      <i class="bi bi-person-plus-fill me-2"></i>Sign Up
    </h3>
    <?php if (!empty($msg)) echo "<div class='alert alert-info'>$msg</div>"; ?>
    <form method="POST" action="">
      <div class="row g-2">
        <div class="col-md-6">
          <div class="mb-3">
            <label for="lastName" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="lastName" name="lastName" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="firstName" class="form-label">First Name</label>
            <input type="text" class="form-control" id="firstName" name="firstName" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="middleName" class="form-label">Middle Name</label>
            <input type="text" class="form-control" id="middleName" name="middleName">
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="phoneNumber" class="form-label">Phone Number</label>
            <input type="text" class="form-control" id="phoneNumber" name="phoneNumber" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="cityMunicipality" class="form-label">City / Municipality</label>
            <input type="text" class="form-control" id="cityMunicipality" name="cityMunicipality" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="barangay" class="form-label">Barangay</label>
            <input type="text" class="form-control" id="barangay" name="barangay" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="province" class="form-label">Province</label>
            <input type="text" class="form-control" id="province" name="province" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="region" class="form-label">Region</label>
            <select class="form-select" id="region" name="region" required>
              <option value="">Select Region</option>
              <option>I</option>
              <option>II</option>
              <option>III</option>
              <option>IV-A</option>
              <option>V</option>
              <option>VI</option>
              <option>VII</option>
              <option>VIII</option>
              <option>IX</option>
              <option>X</option>
              <option>XI</option>
              <option>XII</option>
              <option>XIII</option>
              <option>MIMAROPA</option>
              <option>NCR</option>
              <option>CAR</option>
              <option>BARMM</option>
              <option>NIR</option>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="sitio" class="form-label">Sitio <span class="text-muted">(optional)</span></label>
            <input type="text" class="form-control" id="sitio" name="sitio">
          </div>
        </div>
        <div class="col-md-6"></div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="confirmPassword" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
          </div>
        </div>
      </div>
      <button type="submit" name="signup" class="btn btn-dark w-100 rounded-pill mb-2">
        <i class="bi bi-person-plus me-1"></i> Create Account
      </button>
      <div class="text-center">
        <a>Already have an account?</a> | 
        <a href="/Loginpage.php">Login</a>
      </div>
    </form>
  </div>
</section>

<!-- Footer -->
<footer class="text-white text-center py-4 bg-dark">
  <p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>