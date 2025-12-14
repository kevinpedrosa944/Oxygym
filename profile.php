<?php
session_start();
include('includes/db_connect.php');
include('includes/auth.php');

// If login handler redirected here with ?from=login, forward user to homepage
if (!empty($_GET['from']) && $_GET['from'] === 'login') {
    $conn->close();
    header("Location: /Oxygym/index.html");
    exit();
}

// Get member details (without inline subscription join)
$memberQuery = $conn->prepare("\n    SELECT \n        m.Member_ID,\n        m.First_Name,\n        m.Last_Name,\n        m.Email,\n        m.Phone,\n        m.Address,\n        m.Gender,\n        m.Birthdate,\n        m.Join_Date\n    FROM Users u\n    LEFT JOIN Members m ON u.Member_ID = m.Member_ID\n    WHERE u.Username = ?\n    LIMIT 1\n");

if (!$memberQuery) {
    die('Database error: ' . $conn->error);
}

$memberQuery->bind_param("s", $_SESSION['username']);
$memberQuery->execute();
$result = $memberQuery->get_result();

if ($result->num_rows === 0) {
    $memberQuery->close();
    $conn->close();
    header("Location: /Oxygym/Login.html");
    exit();
}

$member = $result->fetch_assoc();
$memberQuery->close();

// Fetch latest active subscription for this member (most recent Start_Date)
$subStmt = $conn->prepare("\n    SELECT sh.Start_Date, sh.End_Date, sh.Status as Subscription_Status,\n           mt.Name as Membership_Name, mt.Price, mt.Duration_Days\n    FROM Subscription_History sh\n    LEFT JOIN Membership_Types mt ON sh.Membership_ID = mt.Membership_ID\n    WHERE sh.Member_ID = ? AND sh.Status = 'Active'\n    ORDER BY sh.Start_Date DESC\n    LIMIT 1\n");
if ($subStmt) {
    $subStmt->bind_param("i", $member['Member_ID']);
    $subStmt->execute();
    $subRes = $subStmt->get_result();
    if ($subRes && $subRes->num_rows > 0) {
        $sub = $subRes->fetch_assoc();
        // merge subscription fields into $member for existing profile usage
        $member['Start_Date'] = $sub['Start_Date'];
        $member['End_Date'] = $sub['End_Date'];
        $member['Subscription_Status'] = $sub['Subscription_Status'];
        $member['Membership_Name'] = $sub['Membership_Name'];
        $member['Price'] = $sub['Price'];
        $member['Duration_Days'] = $sub['Duration_Days'];
    } else {
        // no active subscription found
        $subStmt->close();
        $conn->close();
        header("Location: /Oxygym/pages/subs.php");
        exit();
    }
    $subStmt->close();
} else {
    die('Database error: ' . $conn->error);
}

// Calculate days remaining - with null check
$today = new DateTime();
$endDate = new DateTime($member['End_Date']);
$interval = $today->diff($endDate);
if ($interval->invert) {
    $daysRemaining = 0;
} else {
    // The format('%a') gives the total number of days, which is more reliable.
    $daysRemaining = (int)$interval->format('%a');
}

// Calculate days since join - with null check
$joinDate = new DateTime($member['Join_Date'] ?? date('Y-m-d'));
$daysActive = (int)$today->diff($joinDate)->format('%a');

// Format dates - with null checks
$joinDateFormatted = $member['Join_Date'] ? date('M d, Y', strtotime($member['Join_Date'])) : 'N/A';
$subscriptionStart = $member['Start_Date'] ? date('M d, Y', strtotime($member['Start_Date'])) : 'N/A';
$subscriptionEnd = $member['End_Date'] ? date('M d, Y', strtotime($member['End_Date'])) : 'N/A';
$birthdate = $member['Birthdate'] ? date('M d, Y', strtotime($member['Birthdate'])) : 'Not provided';

// Calculate age - with null check
$age = 0;
if ($member['Birthdate']) {
    $birthdateObj = new DateTime($member['Birthdate']);
    $age = $today->diff($birthdateObj)->y;
}

// Create user-friendly duration labels
$durationInDays = $member['Duration_Days'] ?? 30;
$priceLabel = 'per ' . $durationInDays . ' days';
$durationValue = $durationInDays . ' days';

if ($durationInDays >= 360) {
    $priceLabel = 'per year';
    $durationValue = '1 Year';
} elseif ($durationInDays >= 85 && $durationInDays <= 95) {
    $priceLabel = 'per quarter';
    $durationValue = '1 Quarter';
} elseif ($durationInDays >= 28 && $durationInDays <= 31) {
    $priceLabel = 'per month';
    $durationValue = '1 Month';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <link rel="stylesheet" href="assets/css/styles.css">
    <title>My Profile - OxyGym</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/profile.css">
</head>
<body>
    <header>
        <div class="container nav-container">
            <h1 class="logo">LOGO OF OXYGYM</h1>
            <nav>
                <ul>
                    <li><a href="/Oxygym/index.html#about-section">About</a></li>
                    <li><a href="/Oxygym/index.html#plan-section">Plan</a></li>
                    <li><a href="faq.php">FAQ</a></li>
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
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-info">
                <h1><?= htmlspecialchars($member['First_Name'] . ' ' . $member['Last_Name']) ?></h1>
                <p><?= htmlspecialchars($member['Email']) ?></p>
            </div>
        </section>

        <!-- Subscription Status Warning -->
        <?php if ($daysRemaining <= 7 && $daysRemaining > 0): ?>
            <div class="warning-banner">
                <i class="fas fa-exclamation-triangle"></i>
                Your subscription expires in <?= $daysRemaining ?> days. <a href="/Oxygym/pages/subs.php">Renew now</a>
            </div>
        <?php endif; ?>

        <!-- Personal Information -->
        <section class="info-section">
            <h2><i class="fas fa-user-circle"></i> Personal Information</h2>
            <div class="details-grid">
                <div class="detail-item">
                    <span class="detail-label">First Name</span>
                    <span class="detail-value"><?= htmlspecialchars($member['First_Name'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Last Name</span>
                    <span class="detail-value"><?= htmlspecialchars($member['Last_Name'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email</span>
                    <span class="detail-value"><?= htmlspecialchars($member['Email'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Phone</span>
                    <span class="detail-value"><?= htmlspecialchars($member['Phone'] ?? 'Not provided') ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Address</span>
                    <span class="detail-value"><?= htmlspecialchars($member['Address'] ?? 'Not provided') ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Gender</span>
                    <span class="detail-value"><?= htmlspecialchars($member['Gender'] ?? 'Not specified') ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Date of Birth</span>
                    <span class="detail-value"><?= $age > 0 ? $birthdate . ' (' . $age . ' years old)' : 'Not provided' ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Member Since</span>
                    <span class="detail-value"><?= $joinDateFormatted ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Days Active</span>
                    <span class="detail-value"><?= $daysActive ?> days</span>
                </div>
            </div>
        </section>

        <!-- Subscription Information -->
        <section class="info-section">
            <h2><i class="fas fa-receipt"></i> Subscription Information</h2>
            
            <div class="subscription-section">
                <h3>Current Plan: <?= htmlspecialchars($member['Membership_Name'] ?? 'No Active Plan') ?></h3>
                <div class="subscription-grid">
                    <div class="subscription-stat">
                        <div class="stat-number"><?= $daysRemaining ?></div>
                        <div class="stat-label">Days Remaining</div>
                    </div>
                    <div class="subscription-stat">
                        <div class="stat-number">₱<?= number_format($member['Price'] ?? 0, 2) ?></div>
                        <div class="stat-label">Price <?= $priceLabel ?></div>
                    </div>
                    <div class="subscription-stat">
                        <div class="stat-number"><span class="status-badge <?= strtolower(htmlspecialchars($member['Subscription_Status'])) ?>"><?= htmlspecialchars($member['Subscription_Status']) ?></span></div>
                        <div class="stat-label">Status</div>
                    </div>
                </div>
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <span class="detail-label">Plan Name</span>
                    <span class="detail-value"><?= htmlspecialchars($member['Membership_Name'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Price</span>
                    <span class="detail-value"><span class="price-tag">₱<?= number_format($member['Price'] ?? 0, 2) ?></span></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Duration</span>
                    <span class="detail-value"><?= $durationValue ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Start Date</span>
                    <span class="detail-value"><?= $subscriptionStart ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Expiry Date</span>
                    <span class="detail-value"><?= $subscriptionEnd ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status</span>
                    <span class="detail-value"><span class="status-badge <?= strtolower(htmlspecialchars($member['Subscription_Status'])) ?>"><?= htmlspecialchars($member['Subscription_Status']) ?></span></span>
                </div>
            </div>
        </section>


        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="/Oxygym/pages/subs.php" class="btn btn-primary">
                <i class="fas fa-sync-alt"></i> Change Plan
            </a>
            <a href="/Oxygym/pages/subs.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Renew Subscription
            </a>
            <a href="#" class="btn btn-danger" onclick="handleLogout(event)">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <div class="back-link">
            <a href="/Oxygym/index.html">← Back to Home</a>
        </div>

    </main>
    <script src="/Oxygym/assets/js/app.js"></script>
</body>
</html>