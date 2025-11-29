<?php
// filepath: c:\xampp\htdocs\Oxygym\api\Login.php

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../includes/db_connect.php');
include('../includes/auth.php');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Log the attempt
    error_log("Login attempt for: " . ($data['username'] ?? 'unknown'));

    if (!$data || !isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        exit();
    }

    $username = trim($data['username']);
    $password = trim($data['password']);

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password cannot be empty']);
        exit();
    }

    error_log("Querying user: $username");

    // Query with lowercase table name
    $stmt = $conn->prepare("
        SELECT 
            User_ID,
            Username,
            Password_Hash,
            Role,
            Member_ID
        FROM users
        WHERE Username = ?
        LIMIT 1
    ");

    if (!$stmt) {
        http_response_code(500);
        error_log("Prepare error: " . $conn->error);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("s", $username);
    
    if (!$stmt->execute()) {
        http_response_code(500);
        error_log("Execute error: " . $stmt->error);
        echo json_encode(['error' => 'Database error: ' . $stmt->error]);
        $stmt->close();
        exit();
    }

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        error_log("User not found: $username");
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password']);
        $stmt->close();
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    error_log("User found: " . $user['Username'] . " with role: " . $user['Role']);

    // Verify password
    if (!password_verify($password, $user['Password_Hash'])) {
        error_log("Password verification failed for: $username");
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password']);
        exit();
    }

    error_log("Password verified for: $username");

    // Set session variables
    $_SESSION['user_id'] = (int)$user['User_ID'];
    $_SESSION['username'] = $user['Username'];
    $_SESSION['role'] = $user['Role'];
    $_SESSION['member_id'] = $user['Member_ID'] ? (int)$user['Member_ID'] : null;

    error_log("Session set - User: {$_SESSION['username']}, Role: {$_SESSION['role']}");

    // Get member details if available
    if ($user['Member_ID']) {
        $memberStmt = $conn->prepare("
            SELECT First_Name, Last_Name, Email, Phone
            FROM members
            WHERE Member_ID = ?
            LIMIT 1
        ");

        if ($memberStmt) {
            $memberStmt->bind_param("i", $user['Member_ID']);
            $memberStmt->execute();
            $memberResult = $memberStmt->get_result();

            if ($memberResult->num_rows > 0) {
                $member = $memberResult->fetch_assoc();
                $_SESSION['first_name'] = $member['First_Name'];
                $_SESSION['last_name'] = $member['Last_Name'];
                $_SESSION['email'] = $member['Email'];
                $_SESSION['phone'] = $member['Phone'];
                error_log("Member details loaded: {$member['First_Name']} {$member['Last_Name']}");
            }
            $memberResult->free();
            $memberStmt->close();
        }
    }

    // Determine redirect based on role
    $redirect = '/Oxygym/profile.html';
    if ($user['Role'] === 'Admin') {
        $redirect = '/Oxygym/adminDashboard.html';
    } elseif ($user['Role'] === 'Staff') {
        $redirect = '/Oxygym/staffDashboard.html';
    }

    error_log("Redirect URL: $redirect");

    session_write_close();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => $redirect,
        'role' => $user['Role'],
        'username' => $user['Username']
    ]);

    error_log("Login successful for: {$user['Username']}");

} catch (Exception $e) {
    http_response_code(500);
    error_log("Login exception: " . $e->getMessage());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>