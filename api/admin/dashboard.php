<?php
// filepath: c:\xampp\htdocs\Oxygym\api\admin\dashboard.php

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../../includes/db_connect.php');
include('../../includes/auth.php');

checkAuth();

// Only admins can access
if ($_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

try {
    // Total users
    $usersResult = $conn->query("SELECT COUNT(*) as count FROM users");
    $totalUsers = $usersResult->fetch_assoc()['count'];

    // Total members
    $membersResult = $conn->query("SELECT COUNT(*) as count FROM members");
    $totalMembers = $membersResult->fetch_assoc()['count'];

    // Active subscriptions
    $subsResult = $conn->query("SELECT COUNT(*) as count FROM subscription_history WHERE Status = 'Active'");
    $activeSubscriptions = $subsResult->fetch_assoc()['count'];

    // Total reviews
    $reviewsResult = $conn->query("SELECT COUNT(*) as count FROM reviews");
    $totalReviews = $reviewsResult->fetch_assoc()['count'];

    echo json_encode([
        'totalUsers' => $totalUsers,
        'totalMembers' => $totalMembers,
        'activeSubscriptions' => $activeSubscriptions,
        'totalReviews' => $totalReviews
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>