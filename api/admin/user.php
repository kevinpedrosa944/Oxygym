<?php
// filepath: c:\xampp\htdocs\Oxygym\api\admin\user.php

session_start();
header('Content-Type: application/json');
include('../../includes/db_connect.php');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'user_id required']);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT u.User_ID, u.Username, u.Role, u.Member_ID,
               m.First_Name, m.Last_Name, m.Email, m.Phone
        FROM users u
        LEFT JOIN members m ON u.Member_ID = m.Member_ID
        WHERE u.User_ID = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    } else {
        echo json_encode(['user' => $user]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
$conn->close();
?>