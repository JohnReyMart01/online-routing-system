<?php
define('SECURE_ACCESS', true);
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page using relative path
header('Location: index.php?show_login=1');
exit();
?>