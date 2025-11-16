<?php
session_start();
include('includes/auth.php');

// Check if user has active subscription
$checkSub = $conn->prepare("SELECT Subscription_History.Subscription_ID 
                            FROM Users 
                            LEFT JOIN Members ON Users.Member_ID = Members.Member_ID 
                            LEFT JOIN Subscription_History ON Members.Member_ID = Subscription_History.Member_ID 
                            WHERE Users.Username = ? AND Subscription_History.Status = 'Active'");
$checkSub->bind_param("s", $_SESSION['username']);
$checkSub->execute();
$subResult = $checkSub->get_result();

// If no active subscription, redirect to subscription page
if ($subResult->num_rows === 0) {
    header("Location: /Oxygym/pages/subs.php");
    exit();
}
$checkSub->close();

$username = $_SESSION['username'] ?? 'Guest';
$email = $_SESSION['email'] ?? 'user@example.com';
$phone = $_SESSION['phone'] ?? 'N/A';
$membershipType = $_SESSION['membership'] ?? 'Standard';
$membershipStatus = $_SESSION['status'] ?? 'Active';
$daysActive = $_SESSION['days_active'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - OxyGym</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .profile-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 1rem;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #ddd;
            object-fit: cover;
            border: 4px solid white;
        }

        .profile-header h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        .profile-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }

        .user-details {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .user-details h2 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .details-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .detail-item {
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-item dt {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .detail-item dd {
            color: #6b7280;
            margin: 0;
        }

        .stats-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stats-card .clock {
            width: 60px;
            height: 60px;
            color: #667eea;
        }

        .review-body {
            display: flex;
            gap: 1rem;
        }

        .review-body .div-wrapper:first-child p {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .review-body .div-wrapper:last-child p {
            color: #6b7280;
            margin: 0;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-logout {
            background-color: #ef4444;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .btn-logout:hover {
            background-color: #dc2626;
        }

        .btn-subscribe {
            background-color: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .btn-subscribe:hover {
            background-color: #5568d3;
        }

        .back-link {
            text-align: center;
            margin-top: 2rem;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
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

    <main class="profile-container">
        
        <!-- Profile Header -->
        <section class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user" style="font-size: 80px; line-height: 120px; text-align: center; width: 100%; color: #667eea;"></i>
            </div>
            <div class="text-content-flow">
                <h1><?= htmlspecialchars(strtoupper($username)) ?></h1>
                <p><?= htmlspecialchars($email) ?></p>
            </div>
        </section>

        <!-- User Details -->
        <section class="user-details">
            <h2>Account Information</h2>
            <dl class="details-list">
                <div class="detail-item">
                    <dt>📞 Phone Number:</dt>
                    <dd><?= htmlspecialchars($phone) ?></dd>
                </div>
                <div class="detail-item">
                    <dt>🏋️ Membership Type:</dt>
                    <dd><?= htmlspecialchars($membershipType) ?></dd>
                </div>
                <div class="detail-item">
                    <dt>✅ Status:</dt>
                    <dd><?= htmlspecialchars($membershipStatus) ?></dd>
                </div>
            </dl>
        </section>

        <!-- Days Active Card -->
        <aside class="stats-card">
            <i class="fas fa-clock" style="font-size: 40px; color: #667eea;"></i>
            <div class="review-body">
                <div class="div-wrapper">
                    <p class="text-heading"><?= $daysActive ?></p>
                </div>
                <div class="div-wrapper">
                    <p class="text">Days Active</p>
                </div>
            </div>
        </aside>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="/Oxygym/pages/subs.php" class="btn-subscribe">Change Plan</a>
            <a href="#" class="btn-logout" onclick="handleLogout(event)">Logout</a>
        </div>

        <div class="back-link">
            <a href="/Oxygym/index.html">← Back to Home</a>
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
