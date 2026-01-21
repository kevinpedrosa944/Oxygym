<?php
// filepath: c:\xampp\htdocs\Oxygym\api\admin\subscriptions.php

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
    $stmt = $conn->prepare("\n        SELECT \n            sh.Subscription_ID,\n            sh.Member_ID,\n            mt.Name AS Plan_Name,\n            mt.Price,\n            sh.Start_Date,\n            sh.End_Date,\n            sh.Status,\n            COALESCE(CONCAT(m.First_Name, ' ', m.Last_Name), u.Username) AS member_name\n        FROM Subscription_History sh\n        LEFT JOIN Membership_Types mt ON sh.Membership_ID = mt.Membership_ID\n        LEFT JOIN Members m ON sh.Member_ID = m.Member_ID\n        LEFT JOIN Users u ON u.Member_ID = m.Member_ID\n        ORDER BY sh.Start_Date DESC\n    ");
    $stmt->execute();
    $res = $stmt->get_result();
    $subs = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['subscriptions' => $subs]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
$conn->close();
?>