<?php
session_start();
include('includes/db_connect.php');
include('includes/auth.php');

// Get full member details from database
$memberQuery = $conn->prepare("
    SELECT 
        m.Member_ID,
        m.First_Name,
        m.Last_Name,
        m.Email,
        m.Phone,
        m.Gender,
        m.Birthdate,
        m.Join_Date,
        mt.Name as Membership_Name,
        mt.Price,
        mt.Duration_Days,
        sh.Start_Date,
        sh.End_Date,
        sh.Status as Subscription_Status
    FROM Users u
    LEFT JOIN Members m ON u.Member_ID = m.Member_ID
    LEFT JOIN Subscription_History sh ON m.Member_ID = sh.Member_ID AND sh.Status = 'Active'AND sh.Start_Date <= CURDATE() AND sh.End_Date >= CURDATE()
    LEFT JOIN Membership_Types mt ON sh.Membership_ID = mt.Membership_ID
    WHERE u.Username = ?
    LIMIT 1
");

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

// Check if user has active subscription
if (!$member['Subscription_Status'] || $member['Subscription_Status'] !== 'Active') {
    $conn->close();
    header("Location: /Oxygym/pages/subs.php");
    exit();
}

// Calculate days remaining - with null check
$today = new DateTime();
$endDate = new DateTime($member['End_Date']);
$daysRemaining = max(0, $today->diff($endDate)->days);

// Calculate days since join - with null check
$joinDate = new DateTime($member['Join_Date'] ?? date('Y-m-d'));
$daysActive = max(0, $today->diff($joinDate)->days);

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

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <title>My Profile - OxyGym</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 8rem auto 3rem;
            padding: 0 2rem;
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
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid white;
            flex-shrink: 0;
        }

        .profile-avatar i {
            font-size: 60px;
            color: white;
        }

        .profile-info h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
        }

        .profile-info p {
            margin: 0;
            opacity: 0.95;
            font-size: 0.95rem;
        }

        .info-section {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .info-section h2 {
            font-size: 1.3rem;
            margin: 0 0 1.5rem 0;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .detail-label {
            font-weight: 600;
            color: #667eea;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            color: #333;
            font-size: 1rem;
        }

        .subscription-section {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border-left: 4px solid #667eea;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }

        .subscription-section h3 {
            color: #667eea;
            margin: 0 0 1rem 0;
        }

        .subscription-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .subscription-stat {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
        }

        .subscription-stat .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .subscription-stat .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .status-badge {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .price-tag {
            font-size: 1.3rem;
            color: #667eea;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 2rem;
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

        .btn-danger {
            background-color: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        .back-link {
            text-align: center;
            margin-top: 2rem;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .warning-banner {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            color: #92400e;
        }
        /* === Reviews Container === */
        .reviews-container {
            margin-top: 1.5rem;
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .review-card {
            background: linear-gradient(135deg, #3a3a40, #2c2c40);
            border-left: 4px solid #667eea;
            border-radius: 8px;
            padding: 1.5rem;
            color: #fff;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .reviewer-info {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .reviewer-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .reviewer-info h4 {
            margin: 0;
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
        }

        .reviewer-info small {
            display: block;
            color: #999;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        .review-rating {
            display: flex;
            gap: 5px;
            color: #ffc107;
        }

        .review-rating i {
            font-size: 1rem;
        }

        .review-title h3 {
            margin: 0 0 0.5rem 0;
            color: #fff;
            font-size: 1.1rem;
        }

        .review-body {
            color: #ccc;
            line-height: 1.6;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .review-body p {
            margin: 0;
        }

        .review-footer {
            color: #999;
            font-size: 0.85rem;
        }
        /* === Buttons === */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            font-family: inherit;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffcc00, #ffa500);
            color: #1a1a2e;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 204, 0, 0.3);
        }

        .btn-danger {
            background-color: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* === Action Buttons === */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }

        /* === Modal === */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.3s ease-in;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #2c2c40;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            margin: 0;
            color: #fff;
        }

        .close-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 2rem;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
        }

        .close-btn:hover {
            color: #ffcc00;
        }

        /* === Form === */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: #fff;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            background-color: #1a1a2e;
            border: 1px solid #444;
            border-radius: 6px;
            color: #fff;
            font-family: inherit;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ffcc00;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* === Rating Input === */
        .rating-input {
            display: flex;
            gap: 0.5rem;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .rating-input input[type="radio"] {
            display: none;
        }

        .rating-input label {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: 0.2s;
            margin: 0;
        }

        .rating-input input[type="radio"]:checked ~ label,
        .rating-input label:hover,
        .rating-input label:hover ~ label {
            color: #ffc107;
        }

        /* === Modal Actions === */
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .modal-actions .btn {
            margin: 0;
        }

        /* === Back Link === */
        .back-link {
            margin-top: 3rem;
            text-align: center;
        }

        .back-link a {
            color: #ffcc00;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .back-link a:hover {
            color: #ffa500;
        }

        /* === Responsive === */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-info h1 {
                font-size: 1.5rem;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .subscription-grid {
                grid-template-columns: 1fr;
            }

            header {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-container {
                flex-direction: column;
                width: 100%;
            }

            nav ul {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                width: 100%;
                justify-content: center;
            }

            .review-header {
                flex-direction: column;
            }

            .review-rating {
                margin-top: 1rem;
            }

            .modal-content {
                width: 95%;
            }
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
                Your subscription expires in <?= $daysRemaining ?> days. <a href="/Oxygym/pages/subs.php" style="color: #92400e; font-weight: bold;">Renew now</a>
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
                <h3><?= htmlspecialchars($member['Membership_Name'] ?? 'No Active Plan') ?></h3>
                <div class="subscription-grid">
                    <div class="subscription-stat">
                        <div class="stat-number"><?= $daysRemaining ?></div>
                        <div class="stat-label">Days Remaining</div>
                    </div>
                    <div class="subscription-stat">
                        <div class="stat-number">₱<?= number_format($member['Price'] ?? 0, 2) ?></div>
                        <div class="stat-label">Price per <?= $member['Duration_Days'] ?? 30 ?> days</div>
                    </div>
                    <div class="subscription-stat">
                        <div class="stat-number"><span class="status-badge">ACTIVE</span></div>
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
                    <span class="detail-value"><?= $member['Duration_Days'] ?? 30 ?> days</span>
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
                    <span class="detail-value"><span class="status-badge"><?= htmlspecialchars($member['Subscription_Status']) ?></span></span>
                </div>
            </div>
        </section>

 <!-- Reviews Section -->
        <section class="info-section">
            <h2><i class="fas fa-star"></i> Your Reviews</h2>
            
            <div class="reviews-container">
                <button class="btn btn-primary" id="addReviewBtn">
                    <i class="fas fa-plus"></i> Write a Review
                </button>

                <div id="reviewsList" class="reviews-list">
                    <p style="color: #999; text-align: center; padding: 2rem;">Loading reviews...</p>
                </div>

                <!-- Review Modal -->
                <div id="reviewModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Write a Review</h2>
                            <button class="close-btn" type="button">&times;</button>
                        </div>
                        <form id="reviewForm" method="POST">
                            <div class="form-group">
                                <label>Rating</label>
                                <div class="rating-input">
                                    <input type="radio" name="rating" value="5" id="star5">
                                    <label for="star5"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" value="4" id="star4">
                                    <label for="star4"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" value="3" id="star3">
                                    <label for="star3"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" value="2" id="star2">
                                    <label for="star2"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" value="1" id="star1">
                                    <label for="star1"><i class="fas fa-star"></i></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="reviewTitle">Review Title</label>
                                <input type="text" id="reviewTitle" name="title" placeholder="Sum up your experience..." required>
                            </div>
                            <div class="form-group">
                                <label for="reviewBody">Your Review</label>
                                <textarea id="reviewBody" name="body" placeholder="Share your experience..." required></textarea>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="btn" id="cancelReviewBtn">Cancel</button>
                                <button type="submit" class="btn btn-primary">Post Review</button>
                            </div>
                        </form>
                    </div>
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
    <script src="/Oxygym/assets\js\app.js"></script> 
</body>
</html>
