<?php
// filepath: c:\xampp\htdocs\Oxygym\logout.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = [];

// Destroy session
session_destroy();

// Clear cookies
foreach ($_COOKIE as $name => $value) {
    setcookie($name, '', time() - 3600, '/');
}

// Redirect to login
header('Location: /Oxygym/Login.html');
exit();
?>
