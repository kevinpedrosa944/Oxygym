<?php
session_start();
include('../includes/db_connect.php');
include('../includes/validate.php');
include('../includes/auth.php');

// Handle subscription form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan'])) {
    $plan = sanitizeInput($_POST['plan']);
    $name = sanitizeInput($_POST['name'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $birthday = sanitizeInput($_POST['birthday'] ?? '');
    $age = sanitizeInput($_POST['age'] ?? '');
    $gender = sanitizeInput($_POST['gender'] ?? '');
    
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
        
        // Update member details
        $updateMember = $conn->prepare("UPDATE Members SET Email = COALESCE(Email, ?), Birthdate = ?, Gender = ? WHERE Member_ID = ?");
        $updateMember->bind_param("sssi", $_SESSION['email'], $birthday, $gender, $memberId);
        $updateMember->execute();
        $updateMember->close();
        
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
            margin: 8rem auto 3rem;
            padding: 2rem;
        }
        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .form-section h2 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .plan-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .plan-option {
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        .plan-option input[type="radio"] {
            display: none;
        }
        .plan-option input[type="radio"]:checked + .plan-label {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }
        .plan-label {
            cursor: pointer;
        }
        .plan-label h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        .plan-label p {
            color: #667eea;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .btn-submit {
            background-color: #3b82f6;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            font-weight: 600;
        }
        .btn-submit:hover {
            background-color: #2563eb;
        }
        .back-link {
            text-align: center;
            margin-top: 2rem;
        }
        .back-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
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
        <div class="form-section">
            <h2>Complete Your Subscription</h2>
            <p style="color: #666; margin-bottom: 1.5rem;">Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>! Fill out the form to complete your subscription.</p>

            <form method="POST">
                <!-- Plan Selection -->
                <div class="form-group">
                    <label>Select Your Plan *</label>
                    <div class="plan-options">
                        <label class="plan-option">
                            <input type="radio" name="plan" value="Standard" required>
                            <div class="plan-label">
                                <h3>STANDARD</h3>
                                <p>₱999/month</p>
                            </div>
                        </label>

                        <label class="plan-option">
                            <input type="radio" name="plan" value="Prime">
                            <div class="plan-label">
                                <h3>PRIME</h3>
                                <p>₱1,499/month</p>
                            </div>
                        </label>

                        <label class="plan-option">
                            <input type="radio" name="plan" value="Premium">
                            <div class="plan-label">
                                <h3>PREMIUM</h3>
                                <p>₱14,999/year</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Personal Details -->
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" placeholder="John Doe" required>
                </div>

                <div class="form-group">
                    <label for="address">Address *</label>
                    <input type="text" id="address" name="address" placeholder="123 Street, City" required>
                </div>

                <div class="form-group">
                    <label for="birthday">Birthday *</label>
                    <input type="date" id="birthday" name="birthday" required>
                </div>

                <div class="form-group">
                    <label for="age">Age *</label>
                    <input type="number" id="age" name="age" placeholder="25" min="18" required>
                </div>

                <div class="form-group">
                    <label for="gender">Gender *</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Complete Subscription</button>
            </form>
        </div>

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