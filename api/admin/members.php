<?php
// filepath: c:\xampp\htdocs\Oxygym\api\admin\members.php

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
        SELECT Member_ID, First_Name, Last_Name, Email, Phone, Join_Date, Status
        FROM members
        ORDER BY Join_Date DESC
    ");

    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }

    echo json_encode(['members' => $members]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>