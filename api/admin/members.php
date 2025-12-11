<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../../includes/db_connect.php');

// simple session/role check (avoid includes/auth.php redirection/output)
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

try {
    // fetch users with Role = 'Member' and join member profile when available
    $stmt = $conn->prepare("
        SELECT 
            u.User_ID,
            u.Username,
            u.Role,
            u.Member_ID,
            m.First_Name,
            m.Last_Name,
            COALESCE(m.Email, u.Username) AS Email,
            m.Phone,
            m.Join_Date
        FROM Users u
        LEFT JOIN Members m ON u.Member_ID = m.Member_ID
        WHERE u.Role = 'Member'
        ORDER BY u.User_ID DESC
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    $members = [];
    while ($row = $res->fetch_assoc()) {
        $members[] = [
            'User_ID'   => (int)$row['User_ID'],
            'Member_ID' => $row['Member_ID'] !== null ? (int)$row['Member_ID'] : null,
            'Username'  => $row['Username'],
            'First_Name'=> $row['First_Name'] ?? '',
            'Last_Name' => $row['Last_Name'] ?? '',
            'Email'     => $row['Email'] ?? '',
            'Phone'     => $row['Phone'] ?? null,
            'Join_Date' => $row['Join_Date'] ?? null,
            'Status'    => 'Active'
        ];
    }
    $stmt->close();

    echo json_encode(['members' => $members]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>