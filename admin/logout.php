<?php
session_name('SCOOPS_ADMIN_SESSION');
session_start();

// Clear and destroy admin-only session
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}
session_destroy();

// Redirect to admin login
header('Location: login.php');
exit;
?>
