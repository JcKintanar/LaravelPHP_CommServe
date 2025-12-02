<!DOCTYPE html>
<html lang="en">
<head>
  <!-- ...existing head... -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato&display=swap" rel="stylesheet">
  <style>
    html,body{height:100%} body{display:flex;flex-direction:column} .content{flex:1}
  </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}"><i class="bi bi-people-fill me-2"></i>Barangay {{ strtoupper(e($barangay)) }} Resident</a>
    <div class="ms-auto text-white">
      <i class="bi bi-person-circle me-1"></i>{{ strtoupper(optional($user)->lastName ?? '') }} {{ $user->firstName ?? '' }}
      <a href="{{ route('logout') }}" class="btn btn-outline-light btn-sm ms-3">Logout</a>
    </div>
  </div>
</nav>

<section class="container my-5">
  <div class="card shadow-sm bg-dark text-white" style="border-radius:2rem;">
    <div class="card-body text-center py-5">
      <h1 class="fw-bold mb-3" style="font-family:'Montserrat',sans-serif;font-size:2.5rem;">Welcome, {{ e($username) }}</h1>
      <p class="mb-0" style="font-size:1.15rem;color:#ccc;">CommServe brings barangay services, announcements, and assistance right to your fingertips.</p>
    </div>
  </div>

  <div class="card shadow-sm bg-dark text-white mt-4" style="border-radius:2rem;">
    <div class="card-body text-center py-1">
      <p class="mb-0" style="color:#fff;">Barangay: <strong>{{ strtoupper(e($barangay)) }}</strong> | Municipality/City: <strong>{{ strtoupper(e($cityMunicipality)) }}</strong></p>
    </div>
  </div>

  <div class="row g-4 mt-4">
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-dark">
        <div class="card-body text-center">
          <i class="bi bi-megaphone-fill" style="font-size:3rem;color:#000;"></i>
          <h5 class="mt-3">Announcements</h5>
          <p class="text-muted">Latest barangay news</p>
          <a href="{{ url('/barangayAnnouncement') }}" class="btn btn-dark w-100">View Announcements</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-dark">
        <div class="card-body text-center">
          <i class="bi bi-telephone-fill" style="font-size:3rem;color:#000;"></i>
          <h5 class="mt-3">Emergency Hotlines</h5>
          <p class="text-muted">Quick access to contacts</p>
          <a href="{{ route('hotlines.index') }}" class="btn btn-dark w-100">View Hotlines</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-dark">
        <div class="card-body text-center">
          <i class="bi bi-file-earmark-text-fill" style="font-size:3rem;color:#000;"></i>
          <h5 class="mt-3">Document Requests</h5>
          <p class="text-muted">Request certificates</p>
          <a href="{{ url('/request-document') }}" class="btn btn-dark w-100">Request Now</a>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="text-white text-center py-4 bg-dark mt-auto">
  <p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
