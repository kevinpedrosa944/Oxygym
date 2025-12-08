<?php
// filepath: c:\xampp\htdocs\Oxygym\api\admin\users.php

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../../includes/db_connect.php');
include('../../includes/auth.php');

checkAuth();

if ($_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

try {
    $result = $conn->query("
        SELECT User_ID, Username, Password_Hash, Role, Member_ID
        FROM users
        ORDER BY User_ID DESC
    ");

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode(['users' => $users]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>