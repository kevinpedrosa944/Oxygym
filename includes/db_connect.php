<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'oxygym';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

$conn->set_charset("utf8mb4");
?>