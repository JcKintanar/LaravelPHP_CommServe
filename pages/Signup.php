<?php
session_start();
// Get flash messages from session
$success_msg = $_SESSION['signup_success'] ?? '';
$error_msg = $_SESSION['signup_error'] ?? '';
// Get stored form data
$form_data = $_SESSION['signup_data'] ?? [];
// Clear messages after displaying
unset($_SESSION['signup_success'], $_SESSION['signup_error']);
// Don't clear form data yet - only clear on success
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
<style>
  body {
    font-family: 'Lato', sans-serif;
    background-color: #f7f7f7;
    min-height: 100vh;
    padding-bottom: 80px;
  }
  .signup-container {
    padding: 2rem 0;
  }
  .signup-card {
    max-width: 900px;
    margin: 0 auto;
    border-radius: 20px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
  }
  .form-label {
    font-weight: 500;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
  }
  .form-control, .form-select {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
  }
  small.text-muted {
    font-size: 0.75rem;
  }
  @media (max-width: 768px) {
    .signup-card {
      margin: 0 1rem;
    }
    .card p-4 {
      padding: 1.5rem !important;
    }
  }
  footer {
    position: fixed;
    bottom: 0;
    width: 100%;
    z-index: 999;
  }
</style>
</head>
<body>

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
<div class="signup-container">
  <div class="card signup-card p-4">
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
    
    <form method="POST" action="/process-signup.php" id="signupForm" onsubmit="return validateLocationFields()">
      <div class="row g-3">
        <div class="col-md-4">
          <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="lastName" name="lastName" value="<?= htmlspecialchars($form_data['lastName'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="firstName" name="firstName" value="<?= htmlspecialchars($form_data['firstName'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label for="middleName" class="form-label">Middle Name</label>
          <input type="text" class="form-control" id="middleName" name="middleName" value="<?= htmlspecialchars($form_data['middleName'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($form_data['username'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
          <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label for="phoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="phoneNumber" name="phoneNumber" value="<?= htmlspecialchars($form_data['phoneNumber'] ?? '') ?>" placeholder="09123456789" required>
        </div>
        <div class="col-md-6">
          <label for="dateOfBirth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
          <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth" value="<?= htmlspecialchars($form_data['dateOfBirth'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label for="civilStatus" class="form-label">Civil Status <span class="text-danger">*</span></label>
          <select class="form-select" id="civilStatus" name="civilStatus" required>
            <option value="Single" <?= ($form_data['civilStatus'] ?? 'Single') === 'Single' ? 'selected' : '' ?>>Single</option>
            <option value="Married" <?= ($form_data['civilStatus'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
            <option value="Widowed" <?= ($form_data['civilStatus'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
            <option value="Divorced" <?= ($form_data['civilStatus'] ?? '') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
            <option value="Separated" <?= ($form_data['civilStatus'] ?? '') === 'Separated' ? 'selected' : '' ?>>Separated</option>
          </select>
        </div>
        <div class="col-md-6">
          <label for="yearResidency" class="form-label">Year Started Residing <span class="text-danger">*</span></label>
          <input type="number" class="form-control" id="yearResidency" name="yearResidency" 
                 min="1900" max="<?= date('Y') ?>" value="<?= htmlspecialchars($form_data['yearResidency'] ?? '') ?>" placeholder="e.g., 2015" required>
          <small class="text-muted d-block">Year you started living in your barangay</small>
        </div>
        <div class="col-md-6">
          <label for="region_id" class="form-label">Region <span class="text-danger">*</span></label>
          <select class="form-select" id="region_id" name="region_id" required>
            <option value="">Select Region</option>
          </select>
          <input type="hidden" id="region" name="region">
        </div>
        <div class="col-md-6">
          <label for="province_id" class="form-label">Province <span class="text-danger">*</span></label>
          <select class="form-select" id="province_id" name="province_id" required disabled>
            <option value="">Select Region first</option>
          </select>
          <input type="hidden" id="province" name="province">
        </div>
        <div class="col-md-6">
          <label for="municipality_id" class="form-label">City/Municipality <span class="text-danger">*</span></label>
          <select class="form-select" id="municipality_id" name="municipality_id" required disabled>
            <option value="">Select Province first</option>
          </select>
          <input type="hidden" id="cityMunicipality" name="cityMunicipality">
        </div>
        <div class="col-md-6">
          <label for="barangay_id" class="form-label">Barangay <span class="text-danger">*</span></label>
          <select class="form-select" id="barangay_id" name="barangay_id" required disabled>
            <option value="">Select Municipality first</option>
          </select>
          <input type="hidden" id="barangay" name="barangay">
        </div>
        <div class="col-md-12">
          <label for="sitio" class="form-label">Sitio/Purok</label>
          <input type="text" class="form-control" id="sitio" name="sitio" value="<?= htmlspecialchars($form_data['sitio'] ?? '') ?>" placeholder="Optional">
        </div>
        <div class="col-md-6">
          <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
          <input type="password" class="form-control" id="password" name="password" required>
          <small class="text-muted">Password is not saved on errors for security</small>
        </div>
        <div class="col-md-6">
          <label for="confirmPassword" class="form-label">Confirm Password <span class="text-danger">*</span></label>
          <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
        </div>
      </div>
      <div class="col-12 mt-3">
        <button type="submit" name="signup" class="btn btn-dark w-100 rounded-pill py-2">
          <i class="bi bi-person-plus me-1"></i> Create Account
        </button>
      </div>
      <div class="col-12 text-center mt-2">
        <small>Already have an account? <a href="/pages/loginPage.php" class="text-decoration-none fw-bold">Login here</a></small>
      </div>
    </form>
  </div>
</div>

<!-- Footer -->
<footer class="text-white text-center py-3 bg-dark">
  <p class="mb-0">&copy; 2025 CommServe. All rights reserved.</p>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Stored form data from PHP
const savedData = <?= json_encode($form_data) ?>;

// Validate that location hidden fields are properly filled before submission
function validateLocationFields() {
  const region = document.getElementById('region').value;
  const province = document.getElementById('province').value;
  const cityMunicipality = document.getElementById('cityMunicipality').value;
  const barangay = document.getElementById('barangay').value;
  
  console.log('Form submission - Hidden field values:');
  console.log('Region:', region);
  console.log('Province:', province);
  console.log('Municipality:', cityMunicipality);
  console.log('Barangay:', barangay);
  
  const missing = [];
  if (!region || region === '0') missing.push('Region');
  if (!province || province === '0') missing.push('Province');
  if (!cityMunicipality || cityMunicipality === '0') missing.push('Municipality/City');
  if (!barangay || barangay === '0') missing.push('Barangay');
  
  if (missing.length > 0) {
    alert('Please select the following location fields: ' + missing.join(', '));
    console.log('Validation failed - missing:', missing);
    return false;
  }
  
  console.log('Validation passed!');
  return true;
}

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
        
        // Restore saved region selection
        if (savedData && savedData.region_id) {
          select.value = savedData.region_id;
          // Update hidden field from selected option's dataset
          const selectedOption = select.options[select.selectedIndex];
          document.getElementById('region').value = selectedOption.dataset.name || selectedOption.textContent;
          onRegionChange({target: select});
        }
      }
    })
    .catch(error => console.error('Error loading regions:', error));
}

// Handle region selection
function onRegionChange(e) {
  const regionId = e.target.value;
  const selectedOption = e.target.options[e.target.selectedIndex];
  const regionName = selectedOption.dataset.name || selectedOption.textContent;
  
  // Update hidden field only if we have a valid region ID
  if (regionId && regionId !== '' && regionId !== '0') {
    document.getElementById('region').value = regionName;
    console.log('Region set to:', regionName);
  } else {
    document.getElementById('region').value = '';
    console.log('Region cleared');
  }
  
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
        
        // Restore saved province selection
        if (savedData && savedData.province_id) {
          provinceSelect.value = savedData.province_id;
          // Update hidden field from selected option's dataset
          const selectedOption = provinceSelect.options[provinceSelect.selectedIndex];
          document.getElementById('province').value = selectedOption.dataset.name || selectedOption.textContent;
          onProvinceChange({target: provinceSelect});
        }
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
  
  // Update hidden field only if we have a valid province ID
  if (provinceId && provinceId !== '' && provinceId !== '0') {
    document.getElementById('province').value = provinceName;
  } else {
    document.getElementById('province').value = '';
  }
  
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
        
        // Restore saved municipality selection
        if (savedData && savedData.municipality_id) {
          municipalitySelect.value = savedData.municipality_id;
          // Update hidden field from selected option's dataset
          const selectedOption = municipalitySelect.options[municipalitySelect.selectedIndex];
          document.getElementById('cityMunicipality').value = selectedOption.dataset.name || selectedOption.textContent;
          onMunicipalityChange({target: municipalitySelect});
        }
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
  
  // Update hidden field only if we have a valid municipality ID
  if (municipalityId && municipalityId !== '' && municipalityId !== '0') {
    document.getElementById('cityMunicipality').value = municipalityName;
  } else {
    document.getElementById('cityMunicipality').value = '';
  }
  
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
        
        // Restore saved barangay selection
        if (savedData && savedData.barangay_id) {
          barangaySelect.value = savedData.barangay_id;
          // Update hidden field from selected option's dataset
          const selectedOption = barangaySelect.options[barangaySelect.selectedIndex];
          document.getElementById('barangay').value = selectedOption.dataset.name || selectedOption.textContent;
        }
        
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
  const barangayId = e.target.value;
  const selectedOption = e.target.options[e.target.selectedIndex];
  const barangayName = selectedOption.dataset.name || selectedOption.textContent;
  
  // Update hidden field only if we have a valid barangay ID
  if (barangayId && barangayId !== '' && barangayId !== '0') {
    document.getElementById('barangay').value = barangayName;
  } else {
    document.getElementById('barangay').value = '';
  }
}
</script>
</body>
</html>
