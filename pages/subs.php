<?php
session_start();
include('../includes/db_connect.php');
include('../includes/validate.php');
include('../includes/auth.php');

// Fetch existing member details to prefill the form
$firstName = $lastName = $phone = $address = $birthday = $gender = '';
$memberId = null;

$fetchStmt = $conn->prepare("
    SELECT m.Member_ID, m.First_Name, m.Last_Name, m.Phone, m.Address, m.Birthdate, m.Gender
    FROM Users u
    LEFT JOIN Members m ON u.Member_ID = m.Member_ID
    WHERE u.Username = ?
    LIMIT 1
");
if ($fetchStmt) {
    $fetchStmt->bind_param("s", $_SESSION['username']);
    $fetchStmt->execute();
    $fetchResult = $fetchStmt->get_result();
    if ($fetchResult && $fetchResult->num_rows > 0) {
        $mrow = $fetchResult->fetch_assoc();
        $memberId = $mrow['Member_ID'];
        $firstName = $mrow['First_Name'] ?? '';
        $lastName = $mrow['Last_Name'] ?? '';
        $phone = $mrow['Phone'] ?? '';
        $address = $mrow['Address'] ?? '';
        $birthday = $mrow['Birthdate'] ?? '';
        $gender = $mrow['Gender'] ?? '';
    }
    $fetchStmt->close();
}

// Handle subscription form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan'])) {
    // sanitize inputs
    $plan = sanitizeInput($_POST['plan']);
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $birthday = sanitizeInput($_POST['birthday'] ?? '');
    $age = sanitizeInput($_POST['age'] ?? '');
    $gender = sanitizeInput($_POST['gender'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
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
        
        // Update member details (store first/last name, phone, address, birthdate, gender)
        $updateMember = $conn->prepare("UPDATE Members SET First_Name = ?, Last_Name = ?, Phone = ?, Address = ?, Birthdate = ?, Gender = ? WHERE Member_ID = ?");
        if ($updateMember) {
            $updateMember->bind_param("ssssssi", $firstName, $lastName, $phone, $address, $birthday, $gender, $memberId);
            $updateMember->execute();
            $updateMember->close();
        }
        
        // Mark any existing active subscriptions as Expired before inserting a new one
        $expireStmt = $conn->prepare("UPDATE Subscription_History SET Status = 'Expired' WHERE Member_ID = ? AND Status = 'Active'");
        if ($expireStmt) {
            $expireStmt->bind_param("i", $memberId);
            $expireStmt->execute();
            $expireStmt->close();
        }

        // Get duration and price for the selected plan to calculate the correct end date
        $durationDays = 30; // Default to 30 days
        $price = 0;
        $durationStmt = $conn->prepare("SELECT Duration_Days, Price FROM Membership_Types WHERE Membership_ID = ?");
        if ($durationStmt) {
            $durationStmt->bind_param("i", $planId);
            $durationStmt->execute();
            $durationResult = $durationStmt->get_result();
            if ($durationResult->num_rows > 0) {
                $durationRow = $durationResult->fetch_assoc();
                $durationDays = (int)$durationRow['Duration_Days'];
                $price = (float)$durationRow['Price'];
            }
            $durationStmt->close();
        }
        
        // Insert subscription record with the correct end date
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+$durationDays days"));
        
        $subStmt = $conn->prepare("INSERT INTO Subscription_History (Member_ID, Membership_ID, Start_Date, End_Date, Status) 
                                   VALUES (?, ?, ?, ?, 'Active')");
        
        if ($subStmt) {
            $subStmt->bind_param("iiss", $memberId, $planId, $startDate, $endDate);
            $subStmt->execute();
            $subscriptionId = $subStmt->insert_id;
            $subStmt->close();

            // Log the transaction
            $transStmt = $conn->prepare("INSERT INTO Transactions (Member_ID, Subscription_ID, Amount, Transaction_Date, Status) VALUES (?, ?, ?, NOW(), 'Completed')");
            if ($transStmt) {
                $transStmt->bind_param("iid", $memberId, $subscriptionId, $price);
                $transStmt->execute();
                $transStmt->close();
            }
            
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
        /* Subscription options as buttons */
        .plan-option {
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1.2rem 1rem;
            cursor: pointer;
            text-align: center;
            transition: all 0.25s ease;
            background: #fff;
        }

        .plan-option:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .plan-option input[type="radio"] {
            display: none;
        }

        /* Selected state = button pressed */
        .plan-option input[type="radio"]:checked + .plan-label {
            background-color: #eff6ff;
            border-radius: 0.5rem;
            padding: 0.6rem;
        }

        /* Stronger visual feedback when selected */
        .plan-option:has(input[type="radio"]:checked) {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }

        .plan-label {
            cursor: pointer;
            display: block;
        }

        .plan-label h3 {
            margin-bottom: 0.4rem;
        }

        .plan-label p {
            font-weight: bold;
            font-size: 1.2rem;
        }
</style>


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
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" placeholder="John" required value="<?= htmlspecialchars($firstName) ?>">
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" placeholder="Doe" required value="<?= htmlspecialchars($lastName) ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" placeholder="+63 9xx xxx xxxx" required value="<?= htmlspecialchars($phone) ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address *</label>
                    <input type="text" id="address" name="address" placeholder="123 Street, City" required value="<?= htmlspecialchars($address) ?>">
                </div>

                <div class="form-group">
                    <label for="birthday">Birthday *</label>
                    <input type="date" id="birthday" name="birthday" required value="<?= htmlspecialchars($birthday) ?>">
                </div>

                <div class="form-group">
                    <label for="age">Age *</label>
                    <input type="number" id="age" name="age" placeholder="25" min="18" required value="" readonly>
                </div>

                <div class="form-group">
                    <label for="gender">Gender *</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
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

        // Auto-calculate age from birthdate and populate the age input
        (function() {
            const birthdayEl = document.getElementById('birthday');
            const ageEl = document.getElementById('age');

            function calculateAge(birthdate) {
                if (!birthdate) return '';
                const today = new Date();
                const b = new Date(birthdate);
                let age = today.getFullYear() - b.getFullYear();
                const m = today.getMonth() - b.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < b.getDate())) {
                    age--;
                }
                return age >= 0 ? age : '';
            }

            function updateAge() {
                const val = birthdayEl.value;
                const age = calculateAge(val);
                if (age !== '') {
                    ageEl.value = age;
                } else {
                    ageEl.value = '';
                }
            }

            birthdayEl.addEventListener('change', updateAge);

            // Initialize on load if there's a prefilled birthday
            document.addEventListener('DOMContentLoaded', function() {
                if (birthdayEl.value) {
                    updateAge();
                }
            });
        })();
    </script>
</body>
</html>