<?php
// filepath: c:\xampp\htdocs\Oxygym\api\Register.php

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../includes/db_connect.php');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $firstName = $data['firstName'] ?? '';
    $lastName = $data['lastName'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    // Validate inputs
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'All fields are required'
        ]);
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid email format'
        ]);
        exit();
    }

    error_log("Registration attempt for: $email");

    // Check if email (username) already exists
    $checkStmt = $conn->prepare("SELECT User_ID FROM users WHERE Username = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Email already registered'
        ]);
        $checkStmt->close();
        $conn->close();
        exit();
    }
    $checkStmt->close();

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Start transaction
    $conn->begin_transaction();

    // Insert member first
    $memberStmt = $conn->prepare("
        INSERT INTO members (First_Name, Last_Name, Email, Join_Date, Status)
        VALUES (?, ?, ?, CURDATE(), 'Active')
    ");

    if (!$memberStmt) {
        throw new Exception('Member insert error: ' . $conn->error);
    }

    $memberStmt->bind_param("sss", $firstName, $lastName, $email);
    $memberStmt->execute();
    $memberId = $conn->insert_id;
    $memberStmt->close();

    error_log("Member created: ID $memberId");

    // Insert user - email is the username
    $userStmt = $conn->prepare("
        INSERT INTO users (Username, Password_Hash, Role, Member_ID, Created_At)
        VALUES (?, ?, 'Member', ?, NOW())
    ");

    if (!$userStmt) {
        throw new Exception('User insert error: ' . $conn->error);
    }

    $userStmt->bind_param("ssi", $email, $hashedPassword, $memberId);
    $userStmt->execute();
    $userId = $conn->insert_id;
    $userStmt->close();

    // Commit transaction
    $conn->commit();

    error_log("User created: ID $userId");

    // Set session
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $email;
    $_SESSION['role'] = 'Member';
    $_SESSION['member_id'] = $memberId;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name'] = $lastName;
    $_SESSION['email'] = $email;

    error_log("Registration successful for: $email");

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'redirect' => '/Oxygym/pages/subs.php',
        'user' => [
            'username' => $email,
            'role' => 'Member',
            'name' => "$firstName $lastName"
        ]
    ]);

} catch (Exception $e) {
    if ($conn) {
        $conn->rollback();
    }
    error_log("Registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Registration failed: ' . $e->getMessage()
    ]);
}

if ($conn) {
    $conn->close();
}
?>