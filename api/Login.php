<?php
session_start();
include('../includes/headers.php');
include('../includes/db_connect.php');
include('../includes/validate.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed. Use POST.']);
    exit;
}

$raw = file_get_contents('php://input');
if (empty($raw)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Empty request body.']);
    exit;
}

$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Username and password are required.']);
    exit;
}

// Query database for user
$stmt = $conn->prepare("SELECT User_ID, Username, Password_Hash, Members.Email, Members.First_Name 
                        FROM Users 
                        LEFT JOIN Members ON Users.Member_ID = Members.Member_ID 
                        WHERE Username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
    exit;
}

$user = $result->fetch_assoc();

if (password_verify($password, $user['Password_Hash'])) {
    $_SESSION['username'] = $user['Username'];
    $_SESSION['email'] = $user['Email'] ?? $username . '@oxygym.com';
    $_SESSION['membership'] = 'Standard';
    $_SESSION['status'] = 'Active';
    $_SESSION['days_active'] = 30;
    
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Login successful!']);
} else {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
}

$stmt->close();
$conn->close();
?>