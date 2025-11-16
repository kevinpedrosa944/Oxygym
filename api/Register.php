<?php
include('../includes/headers.php');
include('../includes/db_connect.php');
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

$firstName = sanitizeInput($data['firstName'] ?? '');
$lastName = sanitizeInput($data['lastName'] ?? '');
$email = sanitizeInput($data['email'] ?? '');
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
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters.']);
    exit;
}

// Check if email already exists
$checkStmt = $conn->prepare("SELECT Email FROM Members WHERE Email = ?");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email already registered.']);
    exit;
}
$checkStmt->close();

// Insert member
$joinDate = date('Y-m-d');
$memberStmt = $conn->prepare("INSERT INTO Members (First_Name, Last_Name, Email, Join_Date, Status) 
                              VALUES (?, ?, ?, ?, 'Active')");
$memberStmt->bind_param("ssss", $firstName, $lastName, $email, $joinDate);

if (!$memberStmt->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $memberStmt->error]);
    exit;
}

$memberId = $conn->insert_id;
$memberStmt->close();

// Create username from email
$username = explode('@', $email)[0];
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$userStmt = $conn->prepare("INSERT INTO Users (Member_ID, Username, Password_Hash, Role) 
                            VALUES (?, ?, ?, 'Member')");
$userStmt->bind_param("iss", $memberId, $username, $passwordHash);

if (!$userStmt->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $userStmt->error]);
    exit;
}

$userStmt->close();
$conn->close();

http_response_code(201);
echo json_encode([
    'status' => 'success',
    'message' => 'Account created successfully! You can now login.',
    'user' => [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'username' => $username
    ]
]);
?>