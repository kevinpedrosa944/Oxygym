<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../includes/db_connect.php');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit();
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

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

    // Query user
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
        throw new Exception($conn->error);
    }

    $stmt->bind_param("s", $username);
    
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password']);
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Verify password
    if (!password_verify($password, $user['Password_Hash'])) {
        $conn->close();
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password']);
        exit();
    }

    // Set session variables
    $_SESSION['user_id'] = (int)$user['User_ID'];
    $_SESSION['username'] = $user['Username'];
    $_SESSION['role'] = $user['Role'];
    $_SESSION['member_id'] = $user['Member_ID'] ? (int)$user['Member_ID'] : null;

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
            }
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

    // Write session before closing connection
    session_write_close();
    $conn->close();

    // Return JSON response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => $redirect,
        'role' => $user['Role'],
        'username' => $user['Username']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>