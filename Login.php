<?php
header('Content-Type: application/json');


$users = [
    "admin" => "12345",
    "user" => "password123"
];

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode([
        "status" => "error",
        "message" => "Username and password are required."
    ]);
    exit;
}


if (array_key_exists($username, $users) && $users[$username] === $password) {
    echo json_encode([
        "status" => "success",
        "message" => "Login successful!"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid username or password."
    ]);
}
?>
