<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use absolute path for includes
include_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON or missing credentials']);
    exit();
}

$username = $data['username'];
$password = $data['password'];

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username and password are required']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT User_ID, Username, Password_Hash, Role, Member_ID FROM Users WHERE Username = ?");
    if ($stmt === false) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        error_log("Authentication failed for user: $username. User not found.");
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
        $stmt->close();
        $conn->close();
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();

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
        $memberStmt = $conn->prepare("SELECT First_Name, Last_Name, Email FROM Members WHERE Member_ID = ?");
        if($memberStmt) {
            $memberStmt->bind_param("i", $user['Member_ID']);
            $memberStmt->execute();
            $memberResult = $memberStmt->get_result();
            if($memberRow = $memberResult->fetch_assoc()) {
                $memberInfo['First_Name'] = $memberRow['First_Name'];
                $memberInfo['Last_Name'] = $memberRow['Last_Name'];
                $memberInfo['Email'] = $memberRow['Email'] ?: $username;
            }
            $memberStmt->close();
        }
    }

    $_SESSION['user_id'] = $user['User_ID'];
    $_SESSION['username'] = $user['Username'];
    $_SESSION['role'] = $user['Role'];
    $_SESSION['member_id'] = $user['Member_ID'];
    $_SESSION['first_name'] = $memberInfo['First_Name'];
    $_SESSION['last_name'] = $memberInfo['Last_Name'];
    $_SESSION['email'] = $memberInfo['Email'];
    $_SESSION['loggedin'] = true;

    error_log("Session created successfully for user: $username");
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful!',
        'role' => $user['Role']
    ]);

} catch (Exception $e) {
    error_log("Login process error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An internal server error occurred.']);

} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>