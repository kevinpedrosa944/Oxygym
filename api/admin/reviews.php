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
    $stmt = $conn->prepare("
        SELECT 
            r.Review_ID,
            r.Member_ID,
            r.Rating,
            r.Title,
            r.Body,
            r.Created_At,
            m.First_Name,
            m.Last_Name,
            u.Username,
            COALESCE(NULLIF(CONCAT_WS(' ', m.First_Name, m.Last_Name), ''), u.Username, 'Unknown') AS member_name
        FROM reviews r
        LEFT JOIN Members m ON r.Member_ID = m.Member_ID
        LEFT JOIN Users u ON r.Member_ID = u.Member_ID
        ORDER BY r.Created_At DESC
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    $reviews = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['reviews' => $reviews]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>