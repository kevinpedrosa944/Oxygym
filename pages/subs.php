<?php
session_start();
include('../includes/db_connect.php');
include('../includes/validate.php');

// --- BEGIN: Token-based renewal handling (new) ---
$RENEW_SECRET = 'RENEW_SECRET_v1_change_me'; // choose a secure secret and keep consistent between files
$renewTokenValid = false;
$renewMemberId = null;
$renewExp = null;

// Accept token via GET or POST (we'll validate both on their respective flows)
$reqToken = $_REQUEST['token'] ?? null;
$reqMember = $_REQUEST['member'] ?? null;
$reqExp = $_REQUEST['exp'] ?? null;

if ($reqToken && $reqMember && $reqExp) {
    if ((int)$reqExp >= time()) {
        $expected = hash_hmac('sha256', $reqMember . '|' . $reqExp, $RENEW_SECRET);
        if (hash_equals($expected, $reqToken)) {
            $renewTokenValid = true;
            $renewMemberId = (int)$reqMember;
            $renewExp = (int)$reqExp;
        }
    }
}

// Only include auth.php if there is no valid renewal token
if (!$renewTokenValid) {
    include('../includes/auth.php');
}
// --- END: Token-based renewal handling (new) ---

// --- BEGIN: Prefill member data for form (new) ---
$prefillName = '';
$prefillAddress = '';
$prefillBirthday = '';
$prefillGender = '';
$prefillEmail = '';
$prefillAge = '';
$prefillPhone = ''; // new
$selectedPlan = null; // NEW: will hold 'Standard'|'Prime'|'Premium'

// Check whether Address table exists to avoid fatal prepares
$hasAddressTable = false;
$tblRes = $conn->query("SHOW TABLES LIKE 'Address'");
if ($tblRes && $tblRes->num_rows > 0) {
	$hasAddressTable = true;
} else {
	// Try to create the Address table if it doesn't exist so address persists
	$createSql = "
		CREATE TABLE IF NOT EXISTS `Address` (
			Member_ID INT NOT NULL PRIMARY KEY,
			Address TEXT,
			CONSTRAINT fk_address_member FOREIGN KEY (Member_ID) REFERENCES Members(Member_ID) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
	";
	@ $conn->query($createSql);
	// re-check
	$tblRes = $conn->query("SHOW TABLES LIKE 'Address'");
	if ($tblRes && $tblRes->num_rows > 0) {
		$hasAddressTable = true;
	}
}

if (!empty($renewTokenValid) && !empty($renewMemberId)) {
    // Prefill from Members using member id from token
    $mStmt = $conn->prepare("SELECT First_Name, Last_Name, Email, Phone, Birthdate, Gender FROM Members WHERE Member_ID = ?");
    if ($mStmt) {
        $mStmt->bind_param("i", $renewMemberId);
        $mStmt->execute();
        $mRes = $mStmt->get_result();
        if ($mRow = $mRes->fetch_assoc()) {
            $prefillName = trim(($mRow['First_Name'] ?? '') . ' ' . ($mRow['Last_Name'] ?? ''));
            $prefillPhone = $mRow['Phone'] ?? '';
            $prefillBirthday = $mRow['Birthdate'] ?? '';
            $prefillGender = $mRow['Gender'] ?? '';
            $prefillEmail = $mRow['Email'] ?? '';
        }
        $mStmt->close();
    }
    // fetch address from Address table (only if exists)
    if ($hasAddressTable) {
        $aStmt = $conn->prepare("SELECT Address FROM Address WHERE Member_ID = ? LIMIT 1");
        if ($aStmt) {
            $aStmt->bind_param("i", $renewMemberId);
            $aStmt->execute();
            $aRes = $aStmt->get_result();
            if ($aRow = $aRes->fetch_assoc()) {
                $prefillAddress = $aRow['Address'] ?? '';
            }
            $aStmt->close();
        }
    } else {
        // keep prefillAddress empty when no Address table - user will supply it
        $prefillAddress = '';
    }
} elseif (!empty($_SESSION['username'])) {
    // choose query depending on whether Address table exists
    if ($hasAddressTable) {
        $uStmt = $conn->prepare("SELECT m.Email, m.First_Name, m.Last_Name, m.Phone, m.Birthdate, m.Gender, a.Address FROM Users u LEFT JOIN Members m ON u.Member_ID = m.Member_ID LEFT JOIN Address a ON m.Member_ID = a.Member_ID WHERE u.Username = ? LIMIT 1");
    } else {
        // no Address table: don't reference it
        $uStmt = $conn->prepare("SELECT m.Email, m.First_Name, m.Last_Name, m.Phone, m.Birthdate, m.Gender FROM Users u LEFT JOIN Members m ON u.Member_ID = m.Member_ID WHERE u.Username = ? LIMIT 1");
    }
    if ($uStmt) {
        $uStmt->bind_param("s", $_SESSION['username']);
        $uStmt->execute();
        $uRes = $uStmt->get_result();
        if ($uRow = $uRes->fetch_assoc()) {
            $prefillName = trim(($uRow['First_Name'] ?? '') . ' ' . ($uRow['Last_Name'] ?? ''));
            $prefillPhone = $uRow['Phone'] ?? '';
            $prefillBirthday = $uRow['Birthdate'] ?? '';
            $prefillGender = $uRow['Gender'] ?? '';
            $prefillEmail = $uRow['Email'] ?? '';
            // prefer Address table value when available
            if ($hasAddressTable) {
                $prefillAddress = $uRow['Address'] ?? '';
            } else {
                $prefillAddress = '';
            }
        }
        $uStmt->close();
    }
}

// Compute server-side age if birthday present
if (!empty($prefillBirthday)) {
    $bdObj = DateTime::createFromFormat('Y-m-d', $prefillBirthday);
    if ($bdObj !== false) {
        $prefillAge = $bdObj->diff(new DateTime())->y;
    }
}

// Determine current member id for selecting current plan
$currentMemberId = null;
if (!empty($renewTokenValid) && !empty($renewMemberId)) {
    $currentMemberId = $renewMemberId;
} elseif (!empty($_SESSION['username'])) {
    $tmpStmt = $conn->prepare("SELECT Member_ID FROM Users WHERE Username = ? LIMIT 1");
    if ($tmpStmt) {
        $tmpStmt->bind_param("s", $_SESSION['username']);
        $tmpStmt->execute();
        $tmpRes = $tmpStmt->get_result();
        if ($tmpRow = $tmpRes->fetch_assoc()) {
            $currentMemberId = (int)$tmpRow['Member_ID'];
        }
        $tmpStmt->close();
    }
}

// If we have a member id, try to load their active subscription to preselect the plan
if ($currentMemberId) {
    $msStmt = $conn->prepare("SELECT mt.Name FROM Subscription_History sh JOIN Membership_Types mt ON sh.Membership_ID = mt.Membership_ID WHERE sh.Member_ID = ? AND sh.Status = 'Active' AND sh.Start_Date <= CURDATE() AND sh.End_Date >= CURDATE() LIMIT 1");
    if ($msStmt) {
        $msStmt->bind_param("i", $currentMemberId);
        $msStmt->execute();
        $msRes = $msStmt->get_result();
        if ($msRow = $msRes->fetch_assoc()) {
            $selectedPlan = $msRow['Name'] ?? null;
        }
        $msStmt->close();
    }
}
// --- END: Prefill member data for form (new) ---

// Handle subscription form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan'])) {
    $plan = sanitizeInput($_POST['plan']);
    $name = sanitizeInput($_POST['name'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? ''); // new
    $birthday = sanitizeInput($_POST['birthday'] ?? '');
    $gender = sanitizeInput($_POST['gender'] ?? '');
    
    // Compute age server-side from birthday (if provided)
    $age = null;
    if (!empty($birthday)) {
        $birthDateObj = DateTime::createFromFormat('Y-m-d', $birthday);
        if ($birthDateObj !== false) {
            $age = $birthDateObj->diff(new DateTime())->y;
        }
    }

    // Map plan name to Membership_ID
    $planMap = ['Standard' => 1, 'Prime' => 2, 'Premium' => 3];
    $planId = $planMap[$plan] ?? 1;
    
    // Determine Member_ID: prefer a verified renewal token sent in POST, otherwise use session username
    $memberId = null;
    // Verify token sent in POST (if present)
    if (!empty($_POST['renew_token']) && !empty($_POST['renew_member']) && !empty($_POST['renew_exp'])) {
        $postedToken = $_POST['renew_token'];
        $postedMember = $_POST['renew_member'];
        $postedExp = (int)$_POST['renew_exp'];
        if ($postedExp >= time()) {
            $expected = hash_hmac('sha256', $postedMember . '|' . $postedExp, $RENEW_SECRET);
            if (hash_equals($expected, $postedToken)) {
                $memberId = (int)$postedMember;
            }
        }
    }

    // Fallback to session-based lookup
    if (!$memberId) {
        // If session username is missing, abort
        if (empty($_SESSION['username'])) {
            // Safety fallback: require auth if session missing and no valid token
            header("Location: /Oxygym/Login.html");
            exit();
        }
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
        } else {
            $stmt->close();
            $conn->close();
            header("Location: /Oxygym/Login.html");
            exit();
        }
        $stmt->close();
    }

    // Update member details (now preserves existing Phone unless a new phone provided)
    $emailParam = $_SESSION['email'] ?? null;
    // Update Members.Phone with provided phone (keep existing if empty)
    $updateMember = $conn->prepare("
        UPDATE Members 
        SET 
            Email = COALESCE(Email, ?), 
            Birthdate = ?, 
            Gender = ?, 
            Phone = IF(? = '', Phone, ?)
        WHERE Member_ID = ?
    ");
    if ($updateMember) {
        $updateMember->bind_param("sssssi", $emailParam, $birthday, $gender, $phone, $phone, $memberId);
        $updateMember->execute();
        $updateMember->close();
    }

    // Update Address table: insert or update depending on existence (address may be empty)
    if ($address !== '') {
        if ($hasAddressTable) {
            $addrCheck = $conn->prepare("SELECT COUNT(*) as cnt FROM Address WHERE Member_ID = ?");
            if ($addrCheck) {
                $addrCheck->bind_param("i", $memberId);
                $addrCheck->execute();
                $addrRes = $addrCheck->get_result();
                if ($addrRow = $addrRes->fetch_assoc()) {
                    if ((int)$addrRow['cnt'] > 0) {
                        $addrUpd = $conn->prepare("UPDATE Address SET Address = ? WHERE Member_ID = ?");
                        if ($addrUpd) {
                            $addrUpd->bind_param("si", $address, $memberId);
                            $addrUpd->execute();
                            $addrUpd->close();
                        }
                    } else {
                        $addrIns = $conn->prepare("INSERT INTO Address (Member_ID, Address) VALUES (?, ?)");
                        if ($addrIns) {
                            $addrIns->bind_param("is", $memberId, $address);
                            $addrIns->execute();
                            $addrIns->close();
                        }
                    }
                }
                $addrCheck->close();
            }
        } else {
            // If we couldn't create/use Address table, store address into Members.Phone as fallback
            $fallbackAddrUpd = $conn->prepare("UPDATE Members SET Phone = IF(? = '', Phone, ?) WHERE Member_ID = ?");
            if ($fallbackAddrUpd) {
                $fallbackAddrUpd->bind_param("ssi", $address, $address, $memberId);
                $fallbackAddrUpd->execute();
                $fallbackAddrUpd->close();
            }
        }
    }
    
    // If Members table has an Age column, update it safely
    $colCheck = $conn->prepare("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Members' AND COLUMN_NAME = 'Age'");
    if ($colCheck) {
        $colCheck->execute();
        $colRes = $colCheck->get_result();
        if ($colRow = $colRes->fetch_assoc()) {
            if ((int)$colRow['cnt'] > 0 && $age !== null) {
                $ageUpdate = $conn->prepare("UPDATE Members SET Age = ? WHERE Member_ID = ?");
                if ($ageUpdate) {
                    $ageInt = (int)$age;
                    $ageUpdate->bind_param("ii", $ageInt, $memberId);
                    $ageUpdate->execute();
                    $ageUpdate->close();
                }
            }
        }
        $colCheck->close();
    }
    
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
        $conn->close();
        header("Location: /Oxygym/profile.php");
        exit();
    }
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
            <?php
                // display session username if available, otherwise use prefilled name or generic label
                $displayName = $_SESSION['username'] ?? ($prefillName ?: 'valued member');
            ?>
            <p style="color: #666; margin-bottom: 1.5rem;">Welcome, <strong><?= htmlspecialchars($displayName) ?></strong>! Fill out the form to complete your subscription.</p>

            <form method="POST">
                <!-- Plan Selection -->
                <div class="form-group">
                    <label>Select Your Plan *</label>
                    <div class="plan-options">
                        <label class="plan-option">
                            <input type="radio" name="plan" value="Standard" required <?= ($selectedPlan === 'Standard') ? 'checked' : '' ?>>
                            <div class="plan-label">
                                <h3>STANDARD</h3>
                                <p>₱999/month</p>
                            </div>
                        </label>

                        <label class="plan-option">
                            <input type="radio" name="plan" value="Prime" <?= ($selectedPlan === 'Prime') ? 'checked' : '' ?>>
                            <div class="plan-label">
                                <h3>PRIME</h3>
                                <p>₱1,499/month</p>
                            </div>
                        </label>

                        <label class="plan-option">
                            <input type="radio" name="plan" value="Premium" <?= ($selectedPlan === 'Premium') ? 'checked' : '' ?>>
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
                    <input type="text" id="name" name="name" placeholder="John Doe" required value="<?= htmlspecialchars($prefillName) ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address *</label>
                    <input type="text" id="address" name="address" placeholder="123 Street, City" required value="<?= htmlspecialchars($prefillAddress) ?>">
                </div>

                <!-- New Phone field (required) -->
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" placeholder="+63 9XX XXX XXXX" required value="<?= htmlspecialchars($prefillPhone) ?>">
                </div>

                <div class="form-group">
                    <label for="birthday">Birthday *</label>
                    <input type="date" id="birthday" name="birthday" required value="<?= htmlspecialchars($prefillBirthday) ?>">
                </div>

                <div class="form-group">
                    <label for="age">Age *</label>
                    <input type="number" id="age" name="age" placeholder="25" min="0" required readonly value="<?= htmlspecialchars($prefillAge) ?>">
                </div>

                <div class="form-group">
                    <label for="gender">Gender *</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?= $prefillGender === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $prefillGender === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= $prefillGender === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>

                <!-- If this page was opened with a valid renewal token, include hidden token fields so POST can be verified -->
                <?php if (!empty($renewTokenValid) && !empty($renewMemberId) && !empty($renewExp)): ?>
                    <input type="hidden" name="renew_member" value="<?= htmlspecialchars($renewMemberId) ?>">
                    <input type="hidden" name="renew_exp" value="<?= htmlspecialchars($renewExp) ?>">
                    <input type="hidden" name="renew_token" value="<?= htmlspecialchars($reqToken) ?>">
                <?php endif; ?>

                <button type="submit" class="btn-submit">Complete Subscription</button>
            </form>
        </div>

        <div class="back-link">
            <a href="../index.html">← Back to Home</a>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        // Auto-calc age from birthday and set the age input
        (function () {
            function calcAgeFromDateString(dateString) {
                if (!dateString) return '';
                var today = new Date();
                var b = new Date(dateString);
                if (isNaN(b.getTime())) return '';
                var age = today.getFullYear() - b.getFullYear();
                var m = today.getMonth() - b.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < b.getDate())) {
                    age--;
                }
                return age;
            }

            var birthdayEl = document.getElementById('birthday');
            var ageEl = document.getElementById('age');

            function updateAge() {
                var age = calcAgeFromDateString(birthdayEl.value);
                ageEl.value = (age === '' ? '' : age);
            }

            birthdayEl.addEventListener('change', updateAge);
            document.addEventListener('DOMContentLoaded', updateAge);
        })();

        function handleLogout(event) {
            event.preventDefault();
            window.location.href = '/Oxygym/logout.php';
        }
    </script>
</body>
</html>