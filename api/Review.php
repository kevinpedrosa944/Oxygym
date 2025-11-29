<?php
// filepath: c:\xampp\htdocs\Oxygym\api\review.php

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../includes/db_connect.php');

try {
    // Check authentication
    if (!isset($_SESSION['member_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    $memberId = $_SESSION['member_id'];
    $method = $_SERVER['REQUEST_METHOD'];

    // GET - Fetch reviews
    if ($method === 'GET') {
        $stmt = $conn->prepare("
            SELECT 
                Review_ID as review_id,
                Rating as rating,
                Title as title,
                Body as body,
                Created_At as created_at,
                Updated_At as updated_at
            FROM reviews
            WHERE Member_ID = ?
            ORDER BY Created_At DESC
        ");

        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("i", $memberId);
        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'reviews' => $reviews
        ]);
    }

    // POST - Create/Update review
    elseif ($method === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!isset($data['rating']) || !isset($data['title']) || !isset($data['body'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }

        $rating = intval($data['rating']);
        $title = trim($data['title']);
        $body = trim($data['body']);

        if ($rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Rating must be between 1 and 5']);
            exit();
        }

        if (empty($title) || empty($body)) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and body are required']);
            exit();
        }

        // Check if review exists
        $checkStmt = $conn->prepare("SELECT Review_ID FROM reviews WHERE Member_ID = ?");
        $checkStmt->bind_param("i", $memberId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $checkStmt->close();

        if ($checkResult->num_rows > 0) {
            // Update existing
            $updateStmt = $conn->prepare("
                UPDATE reviews 
                SET Rating = ?, Title = ?, Body = ?, Updated_At = CURRENT_TIMESTAMP
                WHERE Member_ID = ?
            ");

            if (!$updateStmt) {
                throw new Exception($conn->error);
            }

            $updateStmt->bind_param("issi", $rating, $title, $body, $memberId);
            $updateStmt->execute();
            $updateStmt->close();

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Review updated']);
        } else {
            // Insert new
            $insertStmt = $conn->prepare("
                INSERT INTO reviews (Member_ID, Rating, Title, Body, Created_At)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");

            if (!$insertStmt) {
                throw new Exception($conn->error);
            }

            $insertStmt->bind_param("iiss", $memberId, $rating, $title, $body);
            $insertStmt->execute();
            $insertStmt->close();

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Review created']);
        }
    }

    // DELETE - Delete review
    elseif ($method === 'DELETE') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!isset($data['review_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Review ID required']);
            exit();
        }

        $reviewId = intval($data['review_id']);

        $deleteStmt = $conn->prepare("DELETE FROM reviews WHERE Review_ID = ? AND Member_ID = ?");

        if (!$deleteStmt) {
            throw new Exception($conn->error);
        }

        $deleteStmt->bind_param("ii", $reviewId, $memberId);
        $deleteStmt->execute();
        $deleteStmt->close();

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Review deleted']);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("Review API error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>