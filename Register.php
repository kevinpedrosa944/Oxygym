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

$firstName = trim($data['firstName'] ?? '');
$lastName = trim($data['lastName'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}

// Validate password strength (at least 6 characters)
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters long.']);
    exit;
}

/*
  Demo: Check if user already exists (replace with database query later)
  For now, we'll just accept all registrations
*/

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

/*
  TODO: Insert user into database
  Example:
  INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)
*/

// For now, return success (replace with actual DB insert)
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