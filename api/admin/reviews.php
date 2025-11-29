<?php
// filepath: c:\xampp\htdocs\Oxygym\api\admin\reviews.php

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
        SELECT 
            r.Review_ID,
            m.First_Name,
            m.Last_Name,
            r.Rating,
            r.Title,
            r.Created_At
        FROM reviews r
        JOIN members m ON r.Member_ID = m.Member_ID
        ORDER BY r.Created_At DESC
    ");

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    echo json_encode(['reviews' => $reviews]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>