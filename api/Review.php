<?php
// filepath: c:\xampp\htdocs\Oxygym\api\Review.php

header('Content-Type: application/json');
session_start();

include('../includes/db_connect.php');
include('../includes/auth.php');

if (!isset($_SESSION['username']) || !isset($_SESSION['member_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$memberId = $_SESSION['member_id'];

try {
    if ($method === 'GET') {
        // Get all reviews for this member
        $query = $conn->prepare("
            SELECT 
                r.Review_ID,
                r.Member_ID,
                r.Rating,
                r.Title,
                r.Body,
                r.Created_At,
                m.First_Name,
                m.Last_Name
            FROM Reviews r
            JOIN Members m ON r.Member_ID = m.Member_ID
            WHERE r.Member_ID = ?
            ORDER BY r.Created_At DESC
        ");
        
        if (!$query) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $query->bind_param("i", $memberId);
        $query->execute();
        $result = $query->get_result();

        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $createdAt = new DateTime($row['Created_At']);
            $reviews[] = [
                'id' => (int)$row['Review_ID'],
                'rating' => (int)$row['Rating'],
                'title' => $row['Title'],
                'body' => $row['Body'],
                'createdAt' => $createdAt->format('M d, Y'),
                'reviewer' => $row['First_Name'] . ' ' . $row['Last_Name']
            ];
        }
        $query->close();

        echo json_encode(['reviews' => $reviews]);

    } elseif ($method === 'POST') {
        // Create new review
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['rating']) || !isset($data['title']) || !isset($data['body'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }

        $rating = (int)$data['rating'];
        $title = trim($data['title']);
        $body = trim($data['body']);

        // Validate
        if ($rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid rating']);
            exit();
        }

        if (strlen($title) < 3 || strlen($title) > 255) {
            http_response_code(400);
            echo json_encode(['error' => 'Title must be 3-255 characters']);
            exit();
        }

        if (strlen($body) < 10 || strlen($body) > 2000) {
            http_response_code(400);
            echo json_encode(['error' => 'Review must be 10-2000 characters']);
            exit();
        }

        // Insert review
        $insertQuery = $conn->prepare("
            INSERT INTO Reviews (Member_ID, Rating, Title, Body, Created_At)
            VALUES (?, ?, ?, ?, NOW())
        ");

        if (!$insertQuery) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $insertQuery->bind_param("iiss", $memberId, $rating, $title, $body);
        
        if (!$insertQuery->execute()) {
            throw new Exception('Failed to create review: ' . $insertQuery->error);
        }

        $reviewId = $insertQuery->insert_id;
        $insertQuery->close();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Review posted successfully',
            'id' => $reviewId
        ]);

    } elseif ($method === 'PUT') {
        // Update review
        $data = json_decode(file_get_contents('php://input'), true);
        $reviewId = $data['id'] ?? null;

        if (!$reviewId || !isset($data['rating']) || !isset($data['title']) || !isset($data['body'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }

        // Check if review belongs to current user
        $checkQuery = $conn->prepare("
            SELECT Review_ID FROM Reviews 
            WHERE Review_ID = ? AND Member_ID = ?
        ");
        $checkQuery->bind_param("ii", $reviewId, $memberId);
        $checkQuery->execute();
        
        if ($checkQuery->get_result()->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            $checkQuery->close();
            exit();
        }
        $checkQuery->close();

        $rating = (int)$data['rating'];
        $title = trim($data['title']);
        $body = trim($data['body']);

        $updateQuery = $conn->prepare("
            UPDATE Reviews 
            SET Rating = ?, Title = ?, Body = ?
            WHERE Review_ID = ? AND Member_ID = ?
        ");

        if (!$updateQuery) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $updateQuery->bind_param("issii", $rating, $title, $body, $reviewId, $memberId);
        
        if (!$updateQuery->execute()) {
            throw new Exception('Failed to update review: ' . $updateQuery->error);
        }

        $updateQuery->close();
        echo json_encode(['success' => true, 'message' => 'Review updated']);

    } elseif ($method === 'DELETE') {
        // Delete review
        $data = json_decode(file_get_contents('php://input'), true);
        $reviewId = $data['id'] ?? null;

        if (!$reviewId) {
            http_response_code(400);
            echo json_encode(['error' => 'Review ID required']);
            exit();
        }

        // Check if review belongs to current user
        $checkQuery = $conn->prepare("
            SELECT Review_ID FROM Reviews 
            WHERE Review_ID = ? AND Member_ID = ?
        ");
        $checkQuery->bind_param("ii", $reviewId, $memberId);
        $checkQuery->execute();
        
        if ($checkQuery->get_result()->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            $checkQuery->close();
            exit();
        }
        $checkQuery->close();

        $deleteQuery = $conn->prepare("
            DELETE FROM Reviews 
            WHERE Review_ID = ? AND Member_ID = ?
        ");

        if (!$deleteQuery) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $deleteQuery->bind_param("ii", $reviewId, $memberId);
        
        if (!$deleteQuery->execute()) {
            throw new Exception('Failed to delete review: ' . $deleteQuery->error);
        }

        $deleteQuery->close();
        echo json_encode(['success' => true, 'message' => 'Review deleted']);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("Review API error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>