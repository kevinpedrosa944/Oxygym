<?php
include('includes/auth.php');
// include('includes/db_connect.php'); // TODO: Add DB later

$username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html>
<head>
  <title>My Profile</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="profile-container">
    <h2>👤 Welcome, <?= htmlspecialchars($username) ?></h2>
    <p><em>Profile data coming soon (DB integration)</em></p>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</body>
</html>
