<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../Login.html");
    exit();
}
// Database connection can be added later
// include('db_connect.php');
?>