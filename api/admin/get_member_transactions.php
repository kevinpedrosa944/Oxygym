<?php
header('Content-Type: application/json');
// Use absolute path for includes
include_once __DIR__ . '/../../includes/db_connect.php';

$memberId = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;

if ($memberId <= 0) {
    http_response_code(400);
    echo json_encode([]); // Return empty array for invalid ID
    exit();
}

try {
    $stmt = $conn->prepare("\n        SELECT \n            th.Transaction_Date, \n            th.Amount, \n            mt.Name AS Membership_Name, \n            th.Status\n        FROM Transaction_History th\n        JOIN Membership_Types mt ON th.Membership_ID = mt.Membership_ID\n        WHERE th.Member_ID = ?\n        ORDER BY th.Transaction_Date DESC\n    ");
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode($transactions);

} catch (Exception $e) {
    http_response_code(500);
    // In case of an error, return a JSON object with an error message
    // This helps the frontend to understand what went wrong.
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}

$conn->close();
?>