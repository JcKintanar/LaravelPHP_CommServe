<?php
// Require login for any dashboard page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /pages/loginPage.php');
    exit;
}