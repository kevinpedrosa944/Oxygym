<?php
// filepath: c:\xampp\htdocs\Oxygym\includes\auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAuth() {
    if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        die(json_encode(['error' => 'Unauthorized. Please login first.']));
    }
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentMemberId() {
    return $_SESSION['member_id'] ?? null;
}

function logout() {
    session_destroy();
    foreach ($_COOKIE as $name => $value) {
        setcookie($name, '', time() - 3600, '/');
    }
    header('Location: /Oxygym/Login.html');
    exit();
}

function isAdmin() {
    return ($_SESSION['role'] ?? null) === 'Admin';
}

function isStaff() {
    return ($_SESSION['role'] ?? null) === 'Staff';
}

function isMember() {
    return ($_SESSION['role'] ?? null) === 'Member';
}

function getRoleRedirect() {
    $role = $_SESSION['role'] ?? 'Member';
    
    if ($role === 'Admin') {
        return '/Oxygym/adminDashboard.html';
    } elseif ($role === 'Staff') {
        return '/Oxygym/staffDashboard.html';
    }
    
    return '/Oxygym/profile.html';
}

if (!isset($_SESSION['username'])) {
    header("Location: /Oxygym/Login.html");
    exit();
}
?>
