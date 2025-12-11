<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../includes/db_connect.php');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Email and password are required'
        ]);
        exit();
    }

    error_log("=== LOGIN ATTEMPT ===");
    error_log("Username/Email: $username");

    // Query users table
    $userQuery = $conn->prepare("
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

    if (!$userQuery) {
        error_log("Query prepare error: " . $conn->error);
        throw new Exception('Database error: ' . $conn->error);
    }

    $userQuery->bind_param("s", $username);
    if (!$userQuery->execute()) {
        error_log("Query execute error: " . $userQuery->error);
        throw new Exception('Query error: ' . $userQuery->error);
    }

    $result = $userQuery->get_result();

    if ($result->num_rows === 0) {
        error_log("User not found: $username");
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid email or password'
        ]);
        $userQuery->close();
        $conn->close();
        exit();
    }

    $user = $result->fetch_assoc();
    $userQuery->close();

    error_log("User found: " . $user['Username'] . ", Role: " . $user['Role']);

    // Verify password
    if (!password_verify($password, $user['Password_Hash'])) {
        error_log("Password verification FAILED for: $username");
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid email or password'
        ]);
        $conn->close();
        exit();
    }

    error_log("Password verification PASSED");

    // Get member details
    $memberInfo = [
        'First_Name' => '',
        'Last_Name' => '',
        'Email' => $username
    ];

    if ($user['Member_ID']) {
        $memberStmt = $conn->prepare("
            SELECT First_Name, Last_Name, Email
            FROM members 
            WHERE Member_ID = ?
        ");
        $memberStmt->bind_param("i", $user['Member_ID']);
        $memberStmt->execute();
        $memberResult = $memberStmt->get_result();
        if ($memberResult->num_rows > 0) {
            $memberInfo = $memberResult->fetch_assoc();
            error_log("Member info: " . $memberInfo['First_Name'] . " " . $memberInfo['Last_Name']);
        }
        $memberStmt->close();
    }

    // Set session variables
    $_SESSION['user_id'] = $user['User_ID'];
    $_SESSION['username'] = $user['Username'];
    $_SESSION['role'] = $user['Role'];
    $_SESSION['member_id'] = $user['Member_ID'];
    $_SESSION['first_name'] = $memberInfo['First_Name'] ?? '';
    $_SESSION['last_name'] = $memberInfo['Last_Name'] ?? '';
    $_SESSION['email'] = $memberInfo['Email'] ?? $username;

    error_log("Session variables set");
    error_log("Role: " . $_SESSION['role']);
    error_log("Member ID: " . $_SESSION['member_id']);

    // Determine redirect
    $redirect = '/Oxygym/index.html';

    if ($user['Role'] === 'Admin') {
        $redirect = '/Oxygym/adminDashboard.html';
        error_log("Admin detected - redirecting to: $redirect");
    } elseif ($user['Role'] === 'Staff') {
        $redirect = '/Oxygym/api/staff/dashboard.php';
        error_log("Staff detected - redirecting to: $redirect");
    } elseif ($user['Role'] === 'Member') {
        // Always redirect members to the homepage (index.html) after successful login
        $redirect = '/Oxygym/index.html';
        error_log("Member detected - redirecting to: $redirect");
    }
    
    error_log("Final redirect URL: $redirect");
    error_log("=== LOGIN SUCCESS ===");

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => $redirect,
        'user' => [
            'username' => $user['Username'],
            'role' => $user['Role'],
            'name' => trim(($memberInfo['First_Name'] ?? '') . ' ' . ($memberInfo['Last_Name'] ?? ''))
        ]
    ]);

} catch (Exception $e) {
    error_log("=== LOGIN ERROR ===");
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>