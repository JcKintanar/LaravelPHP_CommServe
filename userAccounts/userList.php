<?php
include 'Config.php';

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: UserList.php");
    exit();
}

// Handle update action
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $lastName = $conn->real_escape_string($_POST['lastName']);
    $firstName = $conn->real_escape_string($_POST['firstName']);
    $middleName = $conn->real_escape_string($_POST['middleName']);
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $phoneNumber = $conn->real_escape_string($_POST['phoneNumber']);
    $cityMunicipality = $conn->real_escape_string($_POST['cityMunicipality']);
    $barangay = $conn->real_escape_string($_POST['barangay']);
    $sitio = $conn->real_escape_string($_POST['sitio']);
    $conn->query("UPDATE users SET lastName='$lastName', firstName='$firstName', middleName='$middleName', username='$username', email='$email', phoneNumber='$phoneNumber', cityMunicipality='$cityMunicipality', barangay='$barangay', sitio='$sitio' WHERE id=$id");
    $msg = "User updated!";
}

// Fetch all users
$users = $conn->query("SELECT * FROM users");

// Fetch user for editing if edit is set
$editUser = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM users WHERE id=$editId");
    if ($result && $result->num_rows > 0) {
        $editUser = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registered Users - CommServe</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
  }
  .table thead th {
    background-color: #212529;
    color: #fff;
    font-family: 'Montserrat', sans-serif;
    font-weight: 700;
    text-align: center;
    vertical-align: middle;
  }
  .navbar-brand {
    font-family: 'Montserrat', sans-serif;
    font-weight: 700;
  }
  .table td, .table th {
    vertical-align: middle !important;
    text-align: center;
  }
  .table {
    width: 100%;
    table-layout: fixed;
  }
  .table th, .table td {
    word-break: break-word;
  }
  .action-btn {
    width: 90px;
    margin-bottom: 4px;
  }
  .btn-edit {
    background-color: #ffc107;
    color: #212529;
    border: none;
  }
  .btn-edit:hover {
    background-color: #e0a800;
    color: #fff;
  }
  .btn-delete {
    background-color: #dc3545;
    color: #fff;
    border: none;
  }
  .btn-delete:hover {
    background-color: #b52a37;
    color: #fff;
  }
  .card {
    max-width: 100%;
  }
</style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/index.php">
      <i class="bi bi-people-fill me-2"></i>CommServe
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
  </div>
</nav>

<section class="container my-5">
  <div class="card shadow-sm rounded-4">
    <div class="card-header bg-dark text-white">
      <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>Registered Users</h5>
    </div>
    <div class="card-body">
      <?php if (!empty($msg)) echo "<div class='alert alert-info'>$msg</div>"; ?>

      <?php if ($editUser): ?>
      <!-- Edit User Form -->
      <form method="POST" class="mb-4">
        <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
        <div class="row g-2 mb-2">
          <div class="col-md-2">
            <input type="text" class="form-control" name="lastName" value="<?= htmlspecialchars($editUser['lastName']) ?>" required placeholder="Last Name">
          </div>
          <div class="col-md-2">
            <input type="text" class="form-control" name="firstName" value="<?= htmlspecialchars($editUser['firstName']) ?>" required placeholder="First Name">
          </div>
          <div class="col-md-2">
            <input type="text" class="form-control" name="middleName" value="<?= htmlspecialchars($editUser['middleName']) ?>" placeholder="Middle Name">
          </div>
          <div class="col-md-2">
            <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($editUser['username']) ?>" required placeholder="Username">
          </div>
          <div class="col-md-2">
            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($editUser['email']) ?>" required placeholder="Email">
          </div>
          <div class="col-md-2">
            <input type="text" class="form-control" name="phoneNumber" value="<?= htmlspecialchars($editUser['phoneNumber']) ?>" required placeholder="Phone">
          </div>
        </div>
        <div class="row g-2 mb-2">
          <div class="col-md-4">
            <input type="text" class="form-control" name="cityMunicipality" value="<?= htmlspecialchars($editUser['cityMunicipality']) ?>" required placeholder="City / Municipality">
          </div>
          <div class="col-md-4">
            <input type="text" class="form-control" name="barangay" value="<?= htmlspecialchars($editUser['barangay']) ?>" required placeholder="Barangay">
          </div>
          <div class="col-md-4">
            <input type="text" class="form-control" name="sitio" value="<?= htmlspecialchars($editUser['sitio']) ?>" placeholder="Sitio (optional)">
          </div>
        </div>
        <div class="mt-3">
          <button type="submit" name="update" class="btn btn-edit action-btn">
            <i class="bi bi-save"></i> Save
          </button>
          <a href="UserList.php" class="btn btn-secondary action-btn">Cancel</a>
        </div>
      </form>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Last Name</th>
              <th>First Name</th>
              <th>Middle Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Phone</th>
              <th>City / Municipality</th>
              <th>Barangay</th>
              <th>Sitio</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = $users->fetch_assoc()): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['lastName']) ?></td>
              <td><?= htmlspecialchars($row['firstName']) ?></td>
              <td><?= htmlspecialchars($row['middleName']) ?></td>
              <td><?= htmlspecialchars($row['username']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['phoneNumber']) ?></td>
              <td><?= htmlspecialchars($row['cityMunicipality']) ?></td>
              <td><?= htmlspecialchars($row['barangay']) ?></td>
              <td><?= htmlspecialchars($row['sitio']) ?></td>
              <td>
                <a href="?edit=<?= $row['id'] ?>" class="btn btn-edit action-btn mb-1">
                  <i class="bi bi-pencil-square"></i> Edit
                </a>
                <a href="?delete=<?= $row['id'] ?>" class="btn btn-delete action-btn" onclick="return confirm('Delete user?')">
                  <i class="bi bi-trash"></i> Delete
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
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