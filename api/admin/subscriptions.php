<?php
// filepath: c:\xampp\htdocs\Oxygym\api\admin\subscriptions.php

session_start();
header('Content-Type: application/json');
include('../../includes/db_connect.php');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT 
            sh.Subscription_ID,
            sh.Member_ID,
            mt.Name AS Plan_Name,
            mt.Price,
            sh.Start_Date,
            sh.End_Date,
            sh.Status,
            COALESCE(CONCAT(m.First_Name, ' ', m.Last_Name), u.Username) AS member_name
        FROM Subscription_History sh
        LEFT JOIN Membership_Types mt ON sh.Membership_ID = mt.Membership_ID
        LEFT JOIN Members m ON sh.Member_ID = m.Member_ID
        LEFT JOIN Users u ON u.Member_ID = m.Member_ID
        ORDER BY sh.Start_Date DESC
    ");
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