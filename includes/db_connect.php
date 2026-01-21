<?php
// filepath: c:\xampp\htdocs\Oxygym\includes\db_connect.php

error_reporting(E_ALL);
ini_set('display_errors', 0);

// XAMPP Default Configuration
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "oxygym";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database Connection Error: " . $conn->connect_error);
    
    // For API calls, return JSON
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        die(json_encode(['error' => 'Database connection failed']));
    }
    
    // For regular pages
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

?>