<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/loginPage.php');
    exit;
}

$role = $_SESSION['role'] ?? '';
if ($role !== 'official' && $role !== 'admin') {
    header('Location: /dashboards/userDashboard.php');
    exit;
}