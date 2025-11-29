<?php
// filepath: c:\xampp\htdocs\Oxygym\api\check_session.php

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['username'])) {
    http_response_code(200);
    echo json_encode([
        'authenticated' => true,
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'] ?? 'Member',
        'member_id' => $_SESSION['member_id'] ?? null
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'authenticated' => false
    ]);
}
?>