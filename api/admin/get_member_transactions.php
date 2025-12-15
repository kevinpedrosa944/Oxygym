<?php
header('Content-Type: application/json');
// Use absolute path for includes
include_once __DIR__ . '/../../includes/db_connect.php';

$memberId = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;

if ($memberId <= 0) {
    http_response_code(400);
    echo json_encode(['transactions' => [], 'debug' => 'Invalid member ID']);
    exit();
}

try {
    // First check if member exists
    $memberCheck = $conn->prepare("SELECT Member_ID FROM members WHERE Member_ID = ?");
    $memberCheck->bind_param("i", $memberId);
    $memberCheck->execute();
    $memberExists = $memberCheck->get_result()->num_rows > 0;
    $memberCheck->close();

    if (!$memberExists) {
        http_response_code(404);
        echo json_encode(['transactions' => [], 'debug' => 'Member not found in database']);
        exit();
    }

    // Get transaction count
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE Member_ID = ?");
    $countStmt->bind_param("i", $memberId);
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    $transactionCount = $countResult['count'];
    $countStmt->close();

    // Get actual transactions
    $stmt = $conn->prepare("
        SELECT 
            t.Transaction_ID, 
            t.DATE AS Transaction_Date, 
            t.Amount, 
            t.Payment_Method,
            t.STATUS AS Status
        FROM transactions t
        WHERE t.Member_ID = ?
        ORDER BY t.DATE DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $memberId);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'transactions' => $transactions,
        'count' => $transactionCount,
        'memberId' => $memberId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database query failed: ' . $e->getMessage(),
        'transactions' => [],
        'memberId' => $memberId
    ]);
}

$conn->close();
?>