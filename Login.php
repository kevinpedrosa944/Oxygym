<?php
header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

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

/*
  demo, change to database pa
*/
$users = [
    // password: 12345
    'admin' => password_hash('12345', PASSWORD_DEFAULT),
    // password: password123
    'user'  => password_hash('password123', PASSWORD_DEFAULT)
];

if (!array_key_exists($username, $users)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
    exit;
}

$storedHash = $users[$username];
if (password_verify($password, $storedHash)) {
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Login successful!']);
    exit;
}

http_response_code(401);
echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
?>
