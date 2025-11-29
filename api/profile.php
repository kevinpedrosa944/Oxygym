<?php
// filepath: c:\xampp\htdocs\Oxygym\api\profile.php

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../includes/db_connect.php');
include('../includes/auth.php');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $memberId = $_SESSION['member_id'] ?? null;

    if (!$memberId) {
        http_response_code(400);
        echo json_encode(['error' => 'Member ID not found in session']);
        exit();
    }

    // Get member details - use lowercase table names
    $memberQuery = $conn->prepare("
        SELECT 
            Member_ID,
            First_Name,
            Last_Name,
            Email,
            Phone,
            Join_Date
        FROM members
        WHERE Member_ID = ?
        LIMIT 1
    ");

    if (!$memberQuery) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $memberQuery->bind_param("i", $memberId);
    $memberQuery->execute();
    $result = $memberQuery->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Member not found']);
        $memberQuery->close();
        $conn->close();
        exit();
    }

    $member = $result->fetch_assoc();
    $memberQuery->close();

    // Get subscription details - use lowercase table names
    $subQuery = $conn->prepare("
        SELECT 
            sh.Subscription_ID,
            mt.Name as Plan_Name,
            mt.Price,
            sh.Start_Date,
            sh.End_Date,
            sh.Status
        FROM subscription_history sh
        JOIN membership_types mt ON sh.Membership_ID = mt.Membership_ID
        WHERE sh.Member_ID = ? AND sh.Status = 'Active'
        ORDER BY sh.Start_Date DESC
        LIMIT 1
    ");

    if (!$subQuery) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $subQuery->bind_param("i", $memberId);
    $subQuery->execute();
    $subResult = $subQuery->get_result();
    $subscription = $subResult->fetch_assoc();
    $subQuery->close();

    // Calculate dates
    $today = new DateTime();
    $daysRemaining = 0;
    $daysActive = 0;
    $endDate = 'Not set';
    $startDate = 'Not set';

    if ($subscription && $subscription['End_Date']) {
        $endDateTime = new DateTime($subscription['End_Date']);
        $startDateTime = new DateTime($subscription['Start_Date']);
        $daysRemaining = max(0, $today->diff($endDateTime)->days);
        $startDate = $startDateTime->format('M d, Y');
        $endDate = $endDateTime->format('M d, Y');
    }

    if ($member['Join_Date']) {
        $joinDateTime = new DateTime($member['Join_Date']);
        $daysActive = $today->diff($joinDateTime)->days;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'profile' => [
            'member_id' => (int)$member['Member_ID'],
            'first_name' => $member['First_Name'] ?? 'N/A',
            'last_name' => $member['Last_Name'] ?? 'N/A',
            'email' => $member['Email'] ?? 'N/A',
            'phone' => $member['Phone'] ?? 'Not provided',
            'join_date' => $member['Join_Date'] ?? 'N/A',
            'days_active' => $daysActive
        ],
        'subscription' => [
            'plan_name' => $subscription['Plan_Name'] ?? 'No Active Plan',
            'price' => (float)($subscription['Price'] ?? 0),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_remaining' => $daysRemaining,
            'status' => $subscription['Status'] ?? 'Inactive'
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Profile error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
