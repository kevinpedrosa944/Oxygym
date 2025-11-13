<?php
include('includes/auth.php');
$username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - OxyGym</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 3rem auto;
            padding: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .profile-info {
            margin: 1.5rem 0;
            padding: 1rem;
            background: #f3f4f6;
            border-radius: 0.5rem;
        }
        .profile-info label {
            font-weight: 600;
            color: #374151;
        }
        .profile-info p {
            margin: 0.5rem 0 0 0;
            color: #6b7280;
        }
        .logout-btn {
            display: inline-block;
            background-color: #ef4444;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            margin-top: 2rem;
            transition: background-color 0.3s;
        }
        .logout-btn:hover {
            background-color: #dc2626;
        }
        .back-link {
            text-align: center;
            margin-top: 1rem;
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
                    <li><a href="index.html#about-section">About</a></li>
                    <li><a href="index.html#plan-section">Plan</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="profile-container">
        <div class="profile-header">
            <h2>👤 My Profile</h2>
        </div>

        <div class="profile-info">
            <label>Username:</label>
            <p><?= htmlspecialchars($username) ?></p>
        </div>

        <div class="profile-info">
            <label>Membership Status:</label>
            <p>Active (Demo Data)</p>
        </div>

        <div class="profile-info">
            <label>Joined:</label>
            <p><?= date('F d, Y') ?></p>
        </div>

        <center>
            <a href="logout.php" class="logout-btn">Logout</a>
        </center>

        <div class="back-link">
            <a href="index.html">← Back to Home</a>
        </div>
    </div>
</body>
</html>
