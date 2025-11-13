<?php
include('includes/auth.php');
include('includes/db_connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $membership_id = $_POST['membership_id'];
  $member_id = $_SESSION['member_id'];

  $q = $conn->prepare("SELECT Duration_Days FROM membership_types WHERE Membership_ID=?");
  $q->bind_param("i", $membership_id);
  $q->execute();
  $duration = $q->get_result()->fetch_assoc()['Duration_Days'];

  $start = date('Y-m-d');
  $end = date('Y-m-d', strtotime("+{$duration} days"));

  $sql = "INSERT INTO subscriptions (Member_ID, Membership_ID, Start_Date, End_Date, Status)
          VALUES (?, ?, ?, ?, 'active')";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("iiss", $member_id, $membership_id, $start, $end);
  $stmt->execute();

  $conn->query("UPDATE members SET Membership_ID=$membership_id, Status='active' WHERE Member_ID=$member_id");

  echo "✅ Subscription activated! <a href='profile.php'>Back to Profile</a>";
  exit();
}

$options = $conn->query("SELECT * FROM membership_types");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Subscribe</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="form-container">
    <h2>Choose Your Plan</h2>
    <form method="POST">
      <select name="membership_id" required>
        <option value="">Select Plan</option>
        <?php while ($row = $options->fetch_assoc()) { ?>
          <option value="<?= $row['Membership_ID'] ?>">
            <?= $row['Name'] ?> - ₱<?= $row['Price'] ?> (<?= $row['Duration_Days'] ?> days)
          </option>
        <?php } ?>
      </select>
      <button type="submit">Subscribe</button>
    </form>
  </div>
</body>
</html>
