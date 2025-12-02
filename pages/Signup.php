<?php
session_start();
// Get flash messages from session
$success_msg = $_SESSION['signup_success'] ?? '';
$error_msg = $_SESSION['signup_error'] ?? '';
// Clear messages after displaying
unset($_SESSION['signup_success'], $_SESSION['signup_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
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
  <a class="navbar-brand fw-bold" href="/pages/landingPage.php">
      <i class="bi bi-people-fill me-2"></i>CommServe
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
  </div>
</nav>

<!-- Sign Up Section -->
<section class="d-flex align-items-center justify-content-center vh-100">
  <div class="card p-4 shadow-sm rounded-4" style="max-width: 800px; width: 100%;">
    <h3 class="text-center fw-bold mb-3" style="font-family: 'Montserrat', sans-serif;">
      <i class="bi bi-person-plus-fill me-2"></i>Sign Up
    </h3>
    
    <?php if (!empty($success_msg)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success_msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($error_msg)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error_msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    
    <form method="POST" action="/process-signup.php">
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
            <label for="region_id" class="form-label">Region</label>
            <select class="form-select" id="region_id" name="region_id" required>
              <option value="">Select Region</option>
            </select>
            <input type="hidden" id="region" name="region">
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="province_id" class="form-label">Province</label>
            <select class="form-select" id="province_id" name="province_id" required disabled>
              <option value="">Select Region first</option>
            </select>
            <input type="hidden" id="province" name="province">
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="municipality_id" class="form-label">City / Municipality</label>
            <select class="form-select" id="municipality_id" name="municipality_id" required disabled>
              <option value="">Select Province first</option>
            </select>
            <input type="hidden" id="cityMunicipality" name="cityMunicipality">
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="barangay_id" class="form-label">Barangay</label>
            <select class="form-select" id="barangay_id" name="barangay_id" required disabled>
              <option value="">Select Municipality first</option>
            </select>
            <input type="hidden" id="barangay" name="barangay">
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
      <div class="text-center mt-2">
        Already have an account? <a href="/pages/loginPage.php">Login here</a>
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
<script>
// Load regions on page load
document.addEventListener('DOMContentLoaded', function() {
  loadRegions();
  
  // Set up event listeners
  document.getElementById('region_id').addEventListener('change', onRegionChange);
  document.getElementById('province_id').addEventListener('change', onProvinceChange);
  document.getElementById('municipality_id').addEventListener('change', onMunicipalityChange);
  document.getElementById('barangay_id').addEventListener('change', onBarangayChange);
});

// Load all regions
function loadRegions() {
  fetch('/api/get-regions.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const select = document.getElementById('region_id');
        select.innerHTML = '<option value="">Select Region</option>';
        
        data.regions.forEach(region => {
          const option = document.createElement('option');
          option.value = region.id;
          option.textContent = region.name;
          option.dataset.name = region.name;
          select.appendChild(option);
        });
      }
    })
    .catch(error => console.error('Error loading regions:', error));
}

// Handle region selection
function onRegionChange(e) {
  const regionId = e.target.value;
  const selectedOption = e.target.options[e.target.selectedIndex];
  const regionName = selectedOption.dataset.name || selectedOption.textContent;
  
  // Update hidden field
  document.getElementById('region').value = regionName;
  
  const provinceSelect = document.getElementById('province_id');
  const municipalitySelect = document.getElementById('municipality_id');
  const barangaySelect = document.getElementById('barangay_id');
  
  // Reset dependent dropdowns
  municipalitySelect.innerHTML = '<option value="">Select Province first</option>';
  municipalitySelect.disabled = true;
  barangaySelect.innerHTML = '<option value="">Select Municipality first</option>';
  barangaySelect.disabled = true;
  document.getElementById('province').value = '';
  document.getElementById('cityMunicipality').value = '';
  document.getElementById('barangay').value = '';
  
  if (!regionId) {
    provinceSelect.innerHTML = '<option value="">Select Region first</option>';
    provinceSelect.disabled = true;
    return;
  }
  
  // Load provinces for selected region
  provinceSelect.disabled = true;
  provinceSelect.innerHTML = '<option value="">Loading...</option>';
  
  fetch(`/api/get-provinces.php?region_id=${regionId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        provinceSelect.innerHTML = '<option value="">Select Province</option>';
        
        data.provinces.forEach(province => {
          const option = document.createElement('option');
          option.value = province.id;
          option.textContent = province.name;
          option.dataset.name = province.name;
          provinceSelect.appendChild(option);
        });
        
        provinceSelect.disabled = false;
      } else {
        provinceSelect.innerHTML = '<option value="">No provinces found</option>';
      }
    })
    .catch(error => {
      console.error('Error loading provinces:', error);
      provinceSelect.innerHTML = '<option value="">Error loading provinces</option>';
    });
}

// Handle province selection
function onProvinceChange(e) {
  const provinceId = e.target.value;
  const selectedOption = e.target.options[e.target.selectedIndex];
  const provinceName = selectedOption.dataset.name || selectedOption.textContent;
  
  // Update hidden field
  document.getElementById('province').value = provinceName;
  
  const municipalitySelect = document.getElementById('municipality_id');
  const barangaySelect = document.getElementById('barangay_id');
  
  // Reset dependent dropdowns
  barangaySelect.innerHTML = '<option value="">Select Municipality first</option>';
  barangaySelect.disabled = true;
  document.getElementById('cityMunicipality').value = '';
  document.getElementById('barangay').value = '';
  
  if (!provinceId) {
    municipalitySelect.innerHTML = '<option value="">Select Province first</option>';
    municipalitySelect.disabled = true;
    return;
  }
  
  // Load municipalities for selected province
  municipalitySelect.disabled = true;
  municipalitySelect.innerHTML = '<option value="">Loading...</option>';
  
  fetch(`/api/get-municipalities.php?province_id=${provinceId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        municipalitySelect.innerHTML = '<option value="">Select City/Municipality</option>';
        
        data.municipalities.forEach(municipality => {
          const option = document.createElement('option');
          option.value = municipality.id;
          option.textContent = municipality.name;
          option.dataset.name = municipality.name;
          municipalitySelect.appendChild(option);
        });
        
        municipalitySelect.disabled = false;
      } else {
        municipalitySelect.innerHTML = '<option value="">No municipalities found</option>';
      }
    })
    .catch(error => {
      console.error('Error loading municipalities:', error);
      municipalitySelect.innerHTML = '<option value="">Error loading municipalities</option>';
    });
}

// Handle municipality selection
function onMunicipalityChange(e) {
  const municipalityId = e.target.value;
  const selectedOption = e.target.options[e.target.selectedIndex];
  const municipalityName = selectedOption.dataset.name || selectedOption.textContent;
  
  // Update hidden field
  document.getElementById('cityMunicipality').value = municipalityName;
  
  const barangaySelect = document.getElementById('barangay_id');
  
  if (!municipalityId) {
    barangaySelect.innerHTML = '<option value="">Select Municipality first</option>';
    barangaySelect.disabled = true;
    document.getElementById('barangay').value = '';
    return;
  }
  
  // Load barangays for selected municipality
  barangaySelect.disabled = true;
  barangaySelect.innerHTML = '<option value="">Loading...</option>';
  
  fetch(`/api/get-barangays.php?municipality_id=${municipalityId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        
        data.barangays.forEach(barangay => {
          const option = document.createElement('option');
          option.value = barangay.id;
          option.textContent = barangay.name;
          option.dataset.name = barangay.name;
          barangaySelect.appendChild(option);
        });
        
        barangaySelect.disabled = false;
      } else {
        barangaySelect.innerHTML = '<option value="">No barangays found</option>';
      }
    })
    .catch(error => {
      console.error('Error loading barangays:', error);
      barangaySelect.innerHTML = '<option value="">Error loading barangays</option>';
    });
}

// Handle barangay selection
function onBarangayChange(e) {
  const selectedOption = e.target.options[e.target.selectedIndex];
  const barangayName = selectedOption.dataset.name || selectedOption.textContent;
  
  // Update hidden field
  document.getElementById('barangay').value = barangayName;
}
</script>
</body>
</html>
