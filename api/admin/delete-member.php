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
$memberId = isset($data['member_id']) ? (int)$data['member_id'] : 0;

if ($memberId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid member ID']);
    exit();
}

try {
    $stmt = $conn->prepare("DELETE FROM members WHERE Member_ID = ?");
    $stmt->bind_param("i", $memberId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete member']);
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
