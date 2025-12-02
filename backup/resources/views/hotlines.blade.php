<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Emergency Hotlines - CommServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; min-height: 100vh; display: flex; flex-direction: column; }
    .hotline-card { min-height: 180px; }
    .hotline-icon { font-size: 2.2rem; }
    .hotline-title { font-weight: bold; font-size: 1.2rem; }
    .hotline-date { font-size: 0.95rem; color: #888; }
    .hotline-number { font-size: 1.1rem; font-weight: 500; }
    .hotline-desc { color: #444; }
    .hotline-container { max-width: 1200px; margin: 0 auto; }
    .section-title { font-family: 'Montserrat', sans-serif; font-weight: bold; font-size: 2.2rem; }
    .hotline-icon-bg { width: 2.8rem; height: 2.8rem; display:flex; align-items:center; justify-content:center; border-radius:50%; margin-right:0.7rem; }
    .hotline-icon-bg.bg-primary { background: #0d6efd; color: #fff; }
    .hotline-icon-bg.bg-success { background: #198754; color: #fff; }
    .hotline-icon-bg.bg-danger { background: #dc3545; color: #fff; }
    .hotline-icon-bg.bg-warning { background: #ffc107; color: #212529; }
    .hotline-icon-bg.bg-info { background: #0dcaf0; color: #212529; }
    .footer-bottom { margin-top: auto; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">
      <i class="bi bi-people-fill me-2" style="color:#fff;"></i>
      Barangay {{ e($barangay) }} Resident
    </a>
  </div>
</nav>

<div class="container hotline-container my-5">
  <div class="d-flex align-items-center mb-4">
    <i class="bi bi-telephone-fill me-2" style="font-size:2.2rem;color:#000;"></i>
    <span class="section-title">Emergency Hotlines</span>
  </div>

  <div class="row g-4">
    @forelse($hotlines as $h)
      <div class="col-md-6">
        <div class="card hotline-card shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="hotline-icon-bg bg-light"><i class="bi bi-telephone-fill hotline-icon" style="color:#000;"></i></div>
            <div>
              <div class="hotline-title mb-1">{{ $h->name }}</div>
              <div class="hotline-number mb-1"><i class="bi bi-hash me-1" style="color:#000;"></i>{{ $h->number }}</div>
              @if(!empty($h->description))
                <div class="hotline-desc mb-1"><i class="bi bi-info-circle me-1" style="color:#000;"></i>{{ $h->description }}</div>
              @endif
              <div class="hotline-date"><i class="bi bi-calendar me-1" style="color:#000;"></i>Posted: {{ \Carbon\Carbon::parse($h->createdAt)->format('M d, Y') }}</div>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12"><p class="text-muted">No hotlines available.</p></div>
    @endforelse
  </div>
</div>

<footer class="text-white text-center py-4 bg-dark mt-auto">
  <p class="mb-1">&copy; 2025 CommServe. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>