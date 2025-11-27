<?php
session_start();
include('includes/db_connect.php');
include('includes/validate.php');
include('includes/auth.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $plan = sanitizeInput($_POST['plan'] ?? '');
    $name = sanitizeInput($_POST['name'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $birthday = sanitizeInput($_POST['birthday'] ?? '');
    $age = sanitizeInput($_POST['age'] ?? '');
    $gender = sanitizeInput($_POST['gender'] ?? '');

    // Validate all fields
    if (empty($plan) || empty($name) || empty($address) || empty($birthday) || empty($age) || empty($gender)) {
        echo "<h2>❌ Registration Failed</h2>";
        echo "<p>Please fill all fields.</p>";
        echo '<a href="/Oxygym/pages/subs.php">← Go Back</a>';
        exit;
    }

    // Validate age
    if (!is_numeric($age) || $age < 18) {
        echo "<h2>❌ Registration Failed</h2>";
        echo "<p>You must be at least 18 years old.</p>";
        echo '<a href="/Oxygym/pages/subs.php">← Go Back</a>';
        exit;
    }

    // Validate birthday format
    if (!strtotime($birthday)) {
        echo "<h2>❌ Registration Failed</h2>";
        echo "<p>Invalid birthday format.</p>";
        echo '<a href="/Oxygym/pages/subs.php">← Go Back</a>';
        exit;
    }

    // Map plan name to Membership_ID
    $planMap = ['Standard' => 1, 'Prime' => 2, 'Premium' => 3];
    $planId = $planMap[$plan] ?? null;

    if (!$planId) {
        echo "<h2>❌ Registration Failed</h2>";
        echo "<p>Invalid plan selected.</p>";
        echo '<a href="/Oxygym/pages/subs.php">← Go Back</a>';
        exit;
    }

    // Get member ID from username
    $stmt = $conn->prepare("SELECT Member_ID FROM Users WHERE Username = ?");
    if (!$stmt) {
        echo "<h2>❌ Database Error</h2>";
        echo "<p>" . $conn->error . "</p>";
        exit;
    }

    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<h2>❌ Registration Failed</h2>";
        echo "<p>User not found.</p>";
        $stmt->close();
        $conn->close();
        exit;
    }

    $row = $result->fetch_assoc();
    $memberId = $row['Member_ID'];
    $stmt->close();

    // Update member details in database
    $updateStmt = $conn->prepare("
        UPDATE Members 
        SET 
            Birthdate = ?,
            Gender = ?,
            Phone = ?
        WHERE Member_ID = ?
    ");

    if (!$updateStmt) {
        echo "<h2>❌ Database Error</h2>";
        echo "<p>" . $conn->error . "</p>";
        exit;
    }

    $updateStmt->bind_param("sssi", $birthday, $gender, $address, $memberId);
    
    if (!$updateStmt->execute()) {
        echo "<h2>❌ Update Failed</h2>";
        echo "<p>" . $updateStmt->error . "</p>";
        $updateStmt->close();
        $conn->close();
        exit;
    }
    $updateStmt->close();

    // Check if already has active subscription
    $checkSub = $conn->prepare("
        SELECT Subscription_ID 
        FROM Subscription_History 
        WHERE Member_ID = ? AND Status = 'Active'
    ");
    $checkSub->bind_param("i", $memberId);
    $checkSub->execute();
    $subResult = $checkSub->get_result();
    $checkSub->close();

    // Insert new subscription
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+30 days'));

    $subStmt = $conn->prepare("
        INSERT INTO Subscription_History 
        (Member_ID, Membership_ID, Start_Date, End_Date, Status) 
        VALUES (?, ?, ?, ?, 'Active')
    ");

    if (!$subStmt) {
        echo "<h2>❌ Database Error</h2>";
        echo "<p>" . $conn->error . "</p>";
        exit;
    }

    $subStmt->bind_param("iiss", $memberId, $planId, $startDate, $endDate);

    if (!$subStmt->execute()) {
        echo "<h2>❌ Subscription Failed</h2>";
        echo "<p>" . $subStmt->error . "</p>";
        $subStmt->close();
        $conn->close();
        exit;
    }

    $subStmt->close();

    // Get plan details for display
    $planStmt = $conn->prepare("SELECT Name, Price FROM Membership_Types WHERE Membership_ID = ?");
    $planStmt->bind_param("i", $planId);
    $planStmt->execute();
    $planResult = $planStmt->get_result();
    $planData = $planResult->fetch_assoc();
    $planStmt->close();

    $conn->close();

    // Display success message
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registration Successful - OxyGym</title>
        <link rel="stylesheet" href="assets/css/styles.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            .success-container {
                max-width: 600px;
                margin: 8rem auto 3rem;
                padding: 3rem 2rem;
                background: white;
                border-radius: 1rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                text-align: center;
            }

            .success-icon {
                font-size: 4rem;
                color: #10b981;
                margin-bottom: 1rem;
            }

            .success-title {
                color: #10b981;
                font-size: 2rem;
                margin-bottom: 1rem;
            }

            .success-message {
                color: #666;
                font-size: 1rem;
                margin-bottom: 2rem;
            }

            .details-box {
                background: #f9fafb;
                padding: 2rem;
                border-radius: 0.5rem;
                margin-bottom: 2rem;
                text-align: left;
            }

            .detail-row {
                display: flex;
                justify-content: space-between;
                padding: 0.75rem 0;
                border-bottom: 1px solid #e5e7eb;
            }

            .detail-row:last-child {
                border-bottom: none;
            }

            .detail-label {
                font-weight: 600;
                color: #333;
            }

            .detail-value {
                color: #667eea;
                font-weight: 500;
            }

            .action-buttons {
                display: flex;
                gap: 1rem;
                justify-content: center;
                flex-wrap: wrap;
            }

            .btn {
                padding: 0.75rem 1.5rem;
                border: none;
                border-radius: 0.5rem;
                cursor: pointer;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }

            .btn-primary {
                background-color: #667eea;
                color: white;
            }

            .btn-primary:hover {
                background-color: #5568d3;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            }

            .btn-secondary {
                background-color: #6b7280;
                color: white;
            }

            .btn-secondary:hover {
                background-color: #4b5563;
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <header>
            <div class="container nav-container">
                <h1 class="logo">LOGO OF OXYGYM</h1>
                <nav>
                    <ul>
                        <li><a href="/Oxygym/index.html#about-section">About</a></li>
                        <li><a href="/Oxygym/index.html#plan-section">Plan</a></li>
                    </ul>
                </nav>
                <div id="authButtons">
                    <a href="#" class="register-btn" onclick="handleLogout(event)">Logout</a>
                </div>
            </div>
        </header>

        <main class="success-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>

            <h2 class="success-title">✅ Registration Successful!</h2>
            <p class="success-message">Your subscription has been activated. Welcome to OxyGym!</p>

            <div class="details-box">
                <div class="detail-row">
                    <span class="detail-label">Plan:</span>
                    <span class="detail-value"><?= htmlspecialchars($planData['Name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Price:</span>
                    <span class="detail-value">₱<?= number_format($planData['Price'], 2) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?= htmlspecialchars($name) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value"><?= htmlspecialchars($address) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Birthday:</span>
                    <span class="detail-value"><?= date('M d, Y', strtotime($birthday)) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Age:</span>
                    <span class="detail-value"><?= htmlspecialchars($age) ?> years old</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Gender:</span>
                    <span class="detail-value"><?= htmlspecialchars($gender) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Start Date:</span>
                    <span class="detail-value"><?= date('M d, Y', strtotime($startDate)) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Expiry Date:</span>
                    <span class="detail-value"><?= date('M d, Y', strtotime($endDate)) ?></span>
                </div>
            </div>

            <div class="action-buttons">
                <a href="/Oxygym/profile.php" class="btn btn-primary">
                    <i class="fas fa-user"></i> View Profile
                </a>
                <a href="/Oxygym/index.html" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </main>

        <script src="assets/js/app.js"></script>
        <script>
            function handleLogout(event) {
                event.preventDefault();
                window.location.href = '/Oxygym/logout.php';
            }
        </script>
    </body>
    </html>
    <?php

} else {
    echo "<h2>❌ Invalid Access</h2>";
    echo '<a href="/Oxygym/pages/subs.php">← Go Back</a>';
}
?>