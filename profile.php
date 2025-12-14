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
$memberQuery = $conn->prepare("
    SELECT 
        m.Member_ID,
        m.First_Name,
        m.Last_Name,
        m.Email,
        m.Phone,
        m.Address,
        m.Gender,
        m.Birthdate,
        m.Join_Date
    FROM Users u
    LEFT JOIN Members m ON u.Member_ID = m.Member_ID
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

// Fetch latest active subscription for this member (most recent Start_Date)
$subStmt = $conn->prepare("
    SELECT sh.Start_Date, sh.End_Date, sh.Status as Subscription_Status,
           mt.Name as Membership_Name, mt.Price, mt.Duration_Days
    FROM Subscription_History sh
    LEFT JOIN Membership_Types mt ON sh.Membership_ID = mt.Membership_ID
    WHERE sh.Member_ID = ? AND sh.Status = 'Active'
    ORDER BY sh.Start_Date DESC
    LIMIT 1
");
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
                <!-- open in-page modal; visual class unchanged -->
                <button id="openReviewBtn" class="btn btn-primary" type="button">
                    <i class="fas fa-plus"></i> Write a Review
                </button>

                <div id="reviewsList" class="reviews-list">
                    <p style="color: #999; text-align: center; padding: 2rem;">Loading reviews...</p>
                </div>

                <!-- Review Modal -->
                <div id="reviewModal" class="modal" aria-hidden="true" role="dialog" aria-labelledby="reviewModalTitle">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 id="reviewModalTitle">Write a Review</h2>
                            <button class="close-btn" type="button" aria-label="Close">&times;</button>
                        </div>
                        <form id="reviewForm" method="POST" action="/Oxygym/api/review.php" novalidate>
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
    <script src="/Oxygym/assets/js/app.js"></script>
    <script>
        // modal open/close for review modal (keeps UI behavior)
        (function() {
            const openBtn = document.getElementById('openReviewBtn');
            const modal = document.getElementById('reviewModal');
            const closeBtn = modal ? modal.querySelector('.close-btn') : null;
            const cancelBtn = document.getElementById('cancelReviewBtn');
            const reviewsList = document.getElementById('reviewsList');
            const reviewForm = document.getElementById('reviewForm');

            function showModal() {
                if (!modal) return;
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }
            function hideModal() {
                if (!modal) return;
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            if (openBtn) openBtn.addEventListener('click', showModal);
            if (closeBtn) closeBtn.addEventListener('click', hideModal);
            if (cancelBtn) cancelBtn.addEventListener('click', hideModal);

            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) hideModal();
                });
            }

            // Fetch and render reviews from API
            function renderReviews(reviews) {
                if (!reviewsList) return;
                if (!reviews || reviews.length === 0) {
                    reviewsList.innerHTML = '<p style="color: #999; text-align: center; padding: 2rem;">No reviews yet. Be the first to write one.</p>';
                    return;
                }
                reviewsList.innerHTML = reviews.map(function(r) {
                    var ratingStars = '';
                    var rating = parseInt(r.rating) || 0;
                    for (var i = 1; i <= 5; i++) {
                        ratingStars += '<i class="fas fa-star" style="color:' + (i <= rating ? '#ffc107' : '#444') + ';"></i>';
                    }
                    var createdAt = r.created_at ? (new Date(r.created_at)).toLocaleDateString() : '';
                    return '<div class="review-card">' +
                                '<div class="review-header">' +
                                    '<div class="reviewer-info">' +
                                        '<div class="reviewer-avatar">' + (('<?= htmlspecialchars($member['First_Name'] ?? '') ?>'.charAt(0) || 'U').toUpperCase()) + '</div>' +
                                        '<div><h4><?= htmlspecialchars($member['First_Name'] ?? 'User') ?></h4><small>' + createdAt + '</small></div>' +
                                    '</div>' +
                                    '<div class="review-rating" aria-hidden="true">' + ratingStars + '</div>' +
                                '</div>' +
                                '<div class="review-title"><h3>' + escapeHtml(r.title || '') + '</h3></div>' +
                                '<div class="review-body"><p>' + nl2br(escapeHtml(r.body || '')) + '</p></div>' +
                                '<div class="review-footer">' + createdAt + '</div>' +
                            '</div>';
                }).join('');
            }

            function nl2br(str) {
                return (str + '').replace(/\n/g, '<br>');
            }
            function escapeHtml(text) {
                var map = {
                  '&': '&amp;',
                  '<': '&lt;',
                  '>': '&gt;',
                  '"': '&quot;',
                  "'": '&#039;'
                };
                return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
            }

            function loadReviews() {
                fetch('/Oxygym/api/review.php', { credentials: 'same-origin' })
                    .then(function(res) {
                        if (!res.ok) throw new Error('Network response was not ok');
                        return res.json();
                    })
                    .then(function(data) {
                        var reviews = [];
                        if (data && Array.isArray(data.reviews)) reviews = data.reviews;
                        renderReviews(reviews);
                    })
                    .catch(function() {
                        reviewsList.innerHTML = '<p style="color: #999; text-align: center; padding: 2rem;">Unable to load reviews.</p>';
                    });
            }

            // Submit review via API (POST JSON)
            if (reviewForm) {
                reviewForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var formData = new FormData(reviewForm);
                    var rating = formData.get('rating') || '';
                    var title = formData.get('title') || '';
                    var body = formData.get('body') || '';

                    // Simple validation
                    if (!rating || !title.trim() || !body.trim()) {
                        alert('Please provide rating, title and review body.');
                        return;
                    }

                    var payload = { rating: rating, title: title.trim(), body: body.trim() };

                    fetch('/Oxygym/api/review.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    })
                    .then(function(res) {
                        return res.json().then(function(json) {
                            if (!res.ok) throw new Error(json.error || 'Failed to post review');
                            return json;
                        });
                    })
                    .then(function(json) {
                        hideModal();
                        reviewForm.reset();
                        loadReviews();
                    })
                    .catch(function(err) {
                        alert('Error: ' + (err.message || 'Unable to post review'));
                    });
                });
            }

            // initial load
            loadReviews();
        })();
    </script>
</body>
</html>