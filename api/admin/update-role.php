<?php
// filepath: c:\xampp\htdocs\Oxygym\api\admin\update-role.php

session_start();
header('Content-Type: application/json');
// Use absolute path for includes
include_once __DIR__ . '/../../includes/db_connect.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$role = isset($data['role']) ? trim($data['role']) : '';

$allowed = ['Member', 'Staff', 'Admin'];
if ($user_id <= 0 || !in_array($role, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

try {
    $stmt = $conn->prepare("UPDATE users SET Role = ? WHERE User_ID = ?");
    $stmt->bind_param("si", $role, $user_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    echo json_encode(['success' => true, 'affected' => $affected]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
$conn->close();
?>