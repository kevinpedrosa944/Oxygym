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

    // Validate input
    if (!isset($data['firstName']) || !isset($data['lastName']) || 
        !isset($data['email']) || !isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required']);
        exit();
    }

    $firstName = trim($data['firstName']);
    $lastName = trim($data['lastName']);
    $email = trim($data['email']);
    $username = trim($data['username']);
    $password = trim($data['password']);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit();
    }

    // Validate password
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 6 characters']);
        exit();
    }

    // Check if email exists
    $emailCheck = $conn->prepare("SELECT Member_ID FROM members WHERE Email = ?");
    if (!$emailCheck) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $emailCheck->bind_param("s", $email);
    $emailCheck->execute();
    
    if ($emailCheck->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Email already registered']);
        $emailCheck->close();
        exit();
    }
    $emailCheck->close();

    // Check if username exists
    $usernameCheck = $conn->prepare("SELECT User_ID FROM users WHERE Username = ?");
    if (!$usernameCheck) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $usernameCheck->bind_param("s", $username);
    $usernameCheck->execute();
    
    if ($usernameCheck->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Username already taken']);
        $usernameCheck->close();
        exit();
    }
    $usernameCheck->close();

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert member
        $memberStmt = $conn->prepare("
            INSERT INTO members (First_Name, Last_Name, Email, Join_Date, Status)
            VALUES (?, ?, ?, CURDATE(), 'Active')
        ");
        
        if (!$memberStmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $memberStmt->bind_param("sss", $firstName, $lastName, $email);
        if (!$memberStmt->execute()) {
            throw new Exception('Failed to create member: ' . $memberStmt->error);
        }
        $memberId = $memberStmt->insert_id;
        $memberStmt->close();

        // Insert user
        $userStmt = $conn->prepare("
            INSERT INTO users (Member_ID, Username, Password_Hash, Role)
            VALUES (?, ?, ?, 'Member')
        ");
        
        if (!$userStmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $userStmt->bind_param("iss", $memberId, $username, $passwordHash);
        if (!$userStmt->execute()) {
            throw new Exception('Failed to create user: ' . $userStmt->error);
        }
        $userId = $userStmt->insert_id;
        $userStmt->close();

        // Commit transaction
        $conn->commit();

        // Set session
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'Member';
        $_SESSION['member_id'] = $memberId;
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        $_SESSION['email'] = $email;

        session_write_close();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'redirect' => '/Oxygym/pages/subs.php'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>