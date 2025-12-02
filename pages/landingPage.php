<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome | CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Lato', sans-serif;
      background: linear-gradient(135deg, #f7f7f7 60%, #e3e6f3 100%);
      min-height: 100vh;
    }
    .navbar .nav-link,
    .navbar .navbar-brand {
      color: white !important;
    }
    .welcome-section {
      min-height: 80vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: none;
    }
    .welcome-card {
      background: #fff;
      border-radius: 2rem;
      box-shadow: 0 8px 32px rgba(44, 122, 123, 0.08);
      padding: 3rem 2.5rem;
      max-width: 430px;
      margin: auto;
    }
    .welcome-title {
      font-family: 'Montserrat', sans-serif;
      font-size: 2.5rem;
      font-weight: 700;
      letter-spacing: 1px;
    }
    .welcome-slogan {
      font-size: 1.15rem;
      color: #555;
      margin-bottom: 2rem;
    }
    .btn-custom {
      font-size: 1.1rem;
      border-radius: 2rem;
      padding: 0.75rem 2.5rem;
      margin: 0 0.5rem;
      box-shadow: 0 2px 8px rgba(44, 122, 123, 0.08);
      transition: transform 0.1s;
    }
    .btn-custom:hover {
      transform: translateY(-2px) scale(1.03);
    }
  </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="landingPage.php">
      <i class="bi bi-people-fill me-2"></i>CommServe
    </a>
  </div>
</nav>

<!-- Welcome Section -->
<section class="welcome-section">
  <div class="welcome-card text-center">
    <i class="bi bi-people-fill text-primary mb-3" style="font-size: 2.5rem;"></i>
    <h1 class="welcome-title mb-2">Welcome To CommServe</h1>
    <div class="welcome-slogan mb-4">
      CommServe brings barangay services, announcements, and assistance right to your fingertips.
    </div>
    <div class="d-flex justify-content-center mb-2">
      <a href="loginPage.php" class="btn btn-dark btn-custom me-2">
        <i class="bi bi-box-arrow-in-right me-1"></i> Login
      </a>
      <a href="Signup.php" class="btn btn-outline-dark btn-custom">
        <i class="bi bi-person-plus me-1"></i> Sign Up
      </a>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="text-white text-center py-4 bg-dark">
  <p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>