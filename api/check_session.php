<?php
session_start();
header('Content-Type: application/json');

$loggedIn = isset($_SESSION['username']);

echo json_encode([
    'loggedIn' => $loggedIn,
    'username' => $loggedIn ? $_SESSION['username'] : null
]);
?>