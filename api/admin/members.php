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
    // Corrected query to fetch the actual status from the Members table
    $stmt = $conn->prepare("\n        SELECT \n            u.User_ID,\n            u.Username,\n            u.Role,\n            u.Member_ID,\n            m.First_Name,\n            m.Last_Name,\n            COALESCE(m.Email, u.Username) AS Email,\n            m.Phone,\n            m.Join_Date,\n            m.STATUS\n        FROM Users u\n        LEFT JOIN Members m ON u.Member_ID = m.Member_ID\n        WHERE u.Role = 'Member'\n        ORDER BY u.User_ID DESC\n    ");
    $stmt->execute();
    $res = $stmt->get_result();
    $members = [];
    while ($row = $res->fetch_assoc()) {
        $members[] = [\n            'User_ID'   => (int)$row['User_ID'],\n            'Member_ID' => $row['Member_ID'] !== null ? (int)$row['Member_ID'] : null,\n            'Username'  => $row['Username'],\n            'First_Name'=> $row['First_Name'] ?? '',\n            'Last_Name' => $row['Last_Name'] ?? '',\n            'Email'     => $row['Email'] ?? '',\n            'Phone'     => $row['Phone'] ?? null,\n            'Join_Date' => $row['Join_Date'] ?? null,\n            // Use the actual status from the database, defaulting to 'Inactive'\n            'Status'    => $row['STATUS'] ?? 'Inactive' \n        ];
    }
    $stmt->close();

    echo json_encode(['members' => $members]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>