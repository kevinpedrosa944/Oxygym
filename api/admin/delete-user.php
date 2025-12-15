<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../../includes/db_connect.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // First, get the Member_ID associated with this user
    $stmt = $conn->prepare("SELECT Member_ID FROM users WHERE User_ID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $memberId = $row['Member_ID'] ?? null;
    $stmt->close();

    // Delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE User_ID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    // If the user had a Member_ID, delete the member as well
    if ($memberId) {
        $stmt = $conn->prepare("DELETE FROM members WHERE Member_ID = ?");
        $stmt->bind_param("i", $memberId);
        $stmt->execute();
        $stmt->close();
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
