<?php
session_start();
include('../includes/headers.php');
include('../includes/db_connect.php');
include('../includes/validate.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
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
    echo json_encode(['status' => 'error', 'message' => 'All fields required.']);
    $conn->close();
    exit;
}

if (!validateEmail($email)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email.']);
    $conn->close();
    exit;
}

if (!validatePasswordStrength($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password too weak.']);
    $conn->close();
    exit;
}

// Check if email exists
$checkStmt = $conn->prepare("SELECT Email FROM Members WHERE Email = ?");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();

if ($checkStmt->get_result()->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email already registered.']);
    $checkStmt->close();
    $conn->close();
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
    echo json_encode(['status' => 'error', 'message' => 'Registration failed.']);
    $memberStmt->close();
    $conn->close();
    exit;
}

$memberId = $conn->insert_id;
$memberStmt->close();

// Create user account
$username = explode('@', $email)[0];
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$userStmt = $conn->prepare("INSERT INTO Users (Member_ID, Username, Password_Hash, Role) 
                            VALUES (?, ?, ?, 'Member')");
$userStmt->bind_param("iss", $memberId, $username, $passwordHash);

if (!$userStmt->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Account creation failed.']);
    $userStmt->close();
    $conn->close();
    exit;
}

$userStmt->close();
$conn->close();

http_response_code(201);
echo json_encode([
    'status' => 'success',
    'message' => 'Account created!',
    'user' => ['username' => $username, 'email' => $email]
]);
?>