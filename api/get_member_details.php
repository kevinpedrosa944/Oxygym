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
        m.Member_ID, 
        m.First_Name, 
        m.Last_Name, 
        m.Email, 
        m.Phone,
        m.Address,
        m.Join_Date
    FROM Members m
    WHERE m.Member_ID = ?;
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $memberId);
$stmt->execute();
$result = $stmt->get_result();

$memberDetails = null;
if ($result && $result->num_rows > 0) {
    $memberDetails = $result->fetch_assoc();
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Member not found.']);
    exit();
}

$stmt->close();
$conn->close();

echo json_encode($memberDetails);
?>
