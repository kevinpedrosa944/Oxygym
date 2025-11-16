<?php
session_start();
session_unset();
session_destroy();

// Clear all cookies
foreach ($_COOKIE as $key => $value) {
    setcookie($key, '', time() - 3600, '/');
}

// Redirect to home
header("Location: /Oxygym/index.html");
exit();
?>
