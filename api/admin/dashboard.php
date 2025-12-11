<?php
session_start();
header('Content-Type: application/json');
include('../../includes/db_connect.php');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $totals = [
        'totalUsers' => 0,
        'totalMembers' => 0,
        'activeSubscriptions' => 0,
        'totalReviews' => 0
    ];

    $q = $conn->prepare("SELECT COUNT(*) AS c FROM users");
    $q->execute(); $r = $q->get_result(); $row = $r->fetch_assoc(); $totals['totalUsers'] = (int)$row['c']; $q->close();

    $q = $conn->prepare("SELECT COUNT(*) AS c FROM members");
    $q->execute(); $r = $q->get_result(); $row = $r->fetch_assoc(); $totals['totalMembers'] = (int)$row['c']; $q->close();

    $q = $conn->prepare("SELECT COUNT(*) AS c FROM Subscription_History WHERE Status = 'Active'");
    $q->execute(); $r = $q->get_result(); $row = $r->fetch_assoc(); $totals['activeSubscriptions'] = (int)$row['c']; $q->close();

    $q = $conn->prepare("SELECT COUNT(*) AS c FROM reviews");
    $q->execute(); $r = $q->get_result(); $row = $r->fetch_assoc(); $totals['totalReviews'] = (int)$row['c']; $q->close();

    echo json_encode($totals);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
$conn->close();
?>