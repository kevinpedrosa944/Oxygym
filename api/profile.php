<?php
// filepath: c:\xampp\htdocs\Oxygym\api\profile.php

header('Content-Type: application/json');
session_start();

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

    // Get member details
    $memberQuery = $conn->prepare("
        SELECT 
            m.Member_ID,
            m.First_Name,
            m.Last_Name,
            m.Email,
            m.Phone,
            m.Gender,
            m.Birthdate,
            m.Join_Date
        FROM Members m
        WHERE m.Member_ID = ?
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
        exit();
    }

    $member = $result->fetch_assoc();
    $memberQuery->close();

    // Get subscription details
    $subQuery = $conn->prepare("
        SELECT 
            sh.Subscription_ID,
            mt.Name as Plan_Name,
            mt.Price,
            mt.Duration_Days,
            sh.Start_Date,
            sh.End_Date,
            sh.Status
        FROM Subscription_History sh
        JOIN Membership_Types mt ON sh.Membership_ID = mt.Membership_ID
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

    $age = 0;
    if ($member['Birthdate']) {
        $birthdateObj = new DateTime($member['Birthdate']);
        $age = $today->diff($birthdateObj)->y;
    }

    $response = [
        'member' => [
            'firstName' => $member['First_Name'] ?? 'N/A',
            'lastName' => $member['Last_Name'] ?? 'N/A',
            'email' => $member['Email'] ?? 'N/A',
            'phone' => $member['Phone'] ?? 'Not provided',
            'gender' => $member['Gender'] ?? 'Not specified',
            'birthdate' => $member['Birthdate'] ? (new DateTime($member['Birthdate']))->format('M d, Y') : 'Not provided',
            'age' => $age,
            'joinDate' => $member['Join_Date'] ? (new DateTime($member['Join_Date']))->format('M d, Y') : 'N/A',
            'daysActive' => $daysActive
        ],
        'subscription' => [
            'planName' => $subscription['Plan_Name'] ?? 'No Active Plan',
            'price' => $subscription['Price'] ?? 0,
            'duration' => $subscription['Duration_Days'] ?? 30,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'daysRemaining' => $daysRemaining,
            'status' => $subscription['Status'] ?? 'Inactive'
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Profile error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
