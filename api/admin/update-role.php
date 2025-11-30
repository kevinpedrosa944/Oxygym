<?php
// filepath: c:\xampp\htdocs\Oxygym\api\admin\update-role.php

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
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['user_id']) || !isset($data['role'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }

    $userId = (int)$data['user_id'];
    $role = trim($data['role']);

    // Validate role
    $validRoles = ['Member', 'Staff', 'Admin'];
    if (!in_array($role, $validRoles)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid role']);
        exit();
    }

    // Update role
    $stmt = $conn->prepare("UPDATE users SET Role = ? WHERE User_ID = ?");
    $stmt->bind_param("si", $role, $userId);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Role updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>