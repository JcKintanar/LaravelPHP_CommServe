<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/loginPage.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'official') {
    header('Location: /dashboards/userDashboard.php');
    exit;
}