<?php
session_start();
include('../includes/headers.php');
include('../includes/users.php');

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

if (!array_key_exists($username, $DEMO_USERS)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
    exit;
}

$storedHash = $DEMO_USERS[$username];
if (password_verify($password, $storedHash)) {
    $_SESSION['username'] = $username;
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Login successful!']);
    exit;
}

http_response_code(401);
echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
?>