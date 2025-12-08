<?php
// filepath: c:\xampp\htdocs\Oxygym\api\admin\subscriptions.php

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
            sh.Subscription_ID,
            CONCAT(m.First_Name, ' ', m.Last_Name) as member_name,
            mt.Name as Plan_Name,
            mt.Price,
            sh.Start_Date,
            sh.End_Date,
            sh.Status
        FROM subscription_history sh
        JOIN members m ON sh.Member_ID = m.Member_ID
        JOIN membership_types mt ON sh.Membership_ID = mt.Membership_ID
        ORDER BY sh.Start_Date DESC
    ");

    $subscriptions = [];
    while ($row = $result->fetch_assoc()) {
        $subscriptions[] = $row;
    }

    echo json_encode(['subscriptions' => $subscriptions]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>