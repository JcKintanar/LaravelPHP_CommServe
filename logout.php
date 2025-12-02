<?php
// Start session
session_start();

// Clear all session data
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Redirect to landing page
header('Location: http://localhost/pages/landingPage.php');
exit();
?>
