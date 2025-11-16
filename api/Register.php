<?php
include('../includes/headers.php');
include('../includes/validate.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
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
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON.']);
    exit;
}

$firstName = trim($data['firstName'] ?? '');
$lastName = trim($data['lastName'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$firstName || !$lastName || !$email || !$password) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

if (!validateEmail($email)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}

if (!validatePasswordStrength($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters long.']);
    exit;
}

http_response_code(201);
echo json_encode([
    'status' => 'success',
    'message' => 'Account created successfully! You can now login.',
    'user' => [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email
    ]
]);
?>