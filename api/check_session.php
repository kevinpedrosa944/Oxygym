<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'loggedIn' => isset($_SESSION['username']),
    'authenticated' => isset($_SESSION['username']),
    'username' => $_SESSION['username'] ?? null,
    'role' => $_SESSION['role'] ?? null
]);
