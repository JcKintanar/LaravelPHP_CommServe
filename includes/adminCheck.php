<?php
// filepath: [adminCheck.php](http://_vscodecontentref_/0)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /pages/loginPage.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /dashboards/userDashboard.php');
    exit;
}