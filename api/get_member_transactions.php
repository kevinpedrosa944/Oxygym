<?php
header('Content-Type: application/json');
// Corrected include path
include(__DIR__ . '/../includes/auth_admin.php');
include(__DIR__ . '/../includes/db_connect.php');

if (!isset($_GET['member_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Member ID is required.']);
    exit();
}

$memberId = (int)$_GET['member_id'];

$query = "
    SELECT 
        t.Transaction_ID, 
        t.Amount, 
        t.Transaction_Date, 
        t.Status, 
        mt.Name as Membership_Name
    FROM Transactions t
    LEFT JOIN Subscription_History sh ON t.Subscription_ID = sh.Subscription_ID
    LEFT JOIN Membership_Types mt ON sh.Membership_ID = mt.Membership_ID
    WHERE t.Member_ID = ?
    ORDER BY t.Transaction_Date DESC;
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $memberId);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}

$stmt->close();
$conn->close();

echo json_encode($transactions);
?>