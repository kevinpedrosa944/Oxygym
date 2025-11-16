<?php
session_start();
include('../includes/db_connect.php');
include('../includes/validate.php');
include('../includes/auth.php');

// Handle subscription form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan'])) {
    $plan = sanitizeInput($_POST['plan']);
    
    // Map plan name to Membership_ID
    $planMap = ['Standard' => 1, 'Prime' => 2, 'Premium' => 3];
    $planId = $planMap[$plan] ?? 1;
    
    // Get member ID from username
    $stmt = $conn->prepare("SELECT Member_ID FROM Users WHERE Username = ?");
    if (!$stmt) {
        die('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $memberId = $row['Member_ID'];
        
        // Insert subscription record
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+30 days'));
        
        $subStmt = $conn->prepare("INSERT INTO Subscription_History (Member_ID, Membership_ID, Start_Date, End_Date, Status) 
                                   VALUES (?, ?, ?, ?, 'Active')");
        
        if ($subStmt) {
            $subStmt->bind_param("iiss", $memberId, $planId, $startDate, $endDate);
            $subStmt->execute();
            $subStmt->close();
            
            // Redirect to profile
            $stmt->close();
            $conn->close();
            header("Location: /Oxygym/profile.php");
            exit();
        }
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <title>Subscribe - OxyGym</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .subs-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 2rem;
            text-align: center;
        }
        .plan-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        .plan-option {
            border: 2px solid #e5e7eb;
            border-radius: 1rem;
            padding: 2rem;
            transition: all 0.3s;
            cursor: pointer;
        }
        .plan-option:hover {
            border-color: #3b82f6;
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.2);
        }
        .plan-option input[type="radio"] {
            margin-right: 0.5rem;
        }
        .btn-subscribe {
            background-color: #3b82f6;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 2rem;
        }
        .btn-subscribe:hover {
            background-color: #2563eb;
        }
        .back-link {
            margin-top: 2rem;
        }
        .back-link a {
            color: #3b82f6;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <header>
        <div class="container nav-container">
            <h1 class="logo">LOGO OF OXYGYM</h1>
            <nav>
                <ul>
                    <li><a href="../index.html#about-section">About</a></li>
                    <li><a href="../index.html#plan-section">Plan</a></li>
                </ul>
            </nav>
            <div id="authButtons">
                <a href="#" class="register-btn" onclick="handleLogout(event)">Logout</a>
            </div>
        </div>
    </header>

    <div class="subs-container">
        <h2>Choose Your Membership Plan</h2>
        <p>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>!</p>

        <form method="POST">
            <div class="plan-options">
                <label class="plan-option">
                    <input type="radio" name="plan" value="Standard" required>
                    <h3>STANDARD</h3>
                    <p>₱999/month</p>
                    <small>Basic access to gym</small>
                </label>

                <label class="plan-option">
                    <input type="radio" name="plan" value="Prime">
                    <h3>PRIME</h3>
                    <p>₱1,499/month</p>
                    <small>+ 1 on 1 coaching</small>
                </label>

                <label class="plan-option">
                    <input type="radio" name="plan" value="Premium">
                    <h3>PREMIUM</h3>
                    <p>₱14,999/year</p>
                    <small>+ Nutrition plan</small>
                </label>
            </div>

            <button type="submit" class="btn-subscribe">Subscribe Now</button>
        </form>

        <div class="back-link">
            <a href="../index.html">← Back to Home</a>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        function handleLogout(event) {
            event.preventDefault();
            window.location.href = '/Oxygym/logout.php';
        }
    </script>
</body>
</html>