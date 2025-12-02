<?php
// Simple logout endpoint for the non-Laravel pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session data
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}

session_unset();
session_destroy();

// Redirect back to landing page
header('Location: /pages/landingPage.php');
exit;
