<?php
// filepath: c:\xampp\htdocs\Oxygym\api\admin\reviews.php

session_start();
header('Content-Type: application/json');
// Use absolute path for includes
include_once __DIR__ . '/../../includes/db_connect.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $conn->prepare("\n        SELECT \n            r.Review_ID,\n            r.Member_ID,\n            r.Rating,\n            r.Title,\n            r.Body,\n            r.Created_At,\n            m.First_Name,\n            m.Last_Name,\n            u.Username,\n            COALESCE(NULLIF(CONCAT_WS(' ', m.First_Name, m.Last_Name), ''), u.Username, 'Unknown') AS member_name\n        FROM reviews r\n        LEFT JOIN Members m ON r.Member_ID = m.Member_ID\n        LEFT JOIN Users u ON r.Member_ID = u.Member_ID\n        ORDER BY r.Created_At DESC\n    ");
    $stmt->execute();
    $res = $stmt->get_result();
    $reviews = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['reviews' => $reviews]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>