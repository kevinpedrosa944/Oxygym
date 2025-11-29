<?php
// filepath: c:\xampp\htdocs\Oxygym\fix-admin-password.php

include('includes/db_connect.php');

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head><title>Fix Admin Password</title><style>
    body { font-family: Arial; padding: 40px; background: #f5f5f5; }
    .container { max-width: 600px; margin: 0 auto; }
    .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .success { color: green; padding: 15px; background: #d1fae5; border-radius: 4px; }
    .error { color: red; padding: 15px; background: #fee2e2; border-radius: 4px; }
    code { background: #f0f0f0; padding: 5px 10px; border-radius: 3px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    td, th { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background: #f0f0f0; }
</style></head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='card'>";
echo "<h1>🔧 Fix Admin Password</h1>";

// Test password hash
$testPassword = "Admin@123";
$newHash = password_hash($testPassword, PASSWORD_BCRYPT);

echo "<h2>New Password Hash</h2>";
echo "<p>Password: <code>$testPassword</code></p>";
echo "<p>New Hash: <code>$newHash</code></p>";

// Verify it works
if (password_verify($testPassword, $newHash)) {
    echo "<p class='success'>✅ Hash verification successful!</p>";
} else {
    echo "<p class='error'>❌ Hash verification failed!</p>";
}

echo "<hr>";
echo "<h2>Updating Database...</h2>";

// Find admin user
$result = $conn->query("SELECT User_ID, Username FROM users WHERE Username = 'admin'");

if ($result->num_rows === 0) {
    echo "<p class='error'>❌ Admin user not found!</p>";
} else {
    $admin = $result->fetch_assoc();
    echo "<p>Found admin user: <strong>{$admin['Username']}</strong> (ID: {$admin['User_ID']})</p>";
    
    // Update password
    $stmt = $conn->prepare("UPDATE users SET Password_Hash = ? WHERE User_ID = ?");
    
    if (!$stmt) {
        echo "<p class='error'>❌ Prepare error: " . $conn->error . "</p>";
    } else {
        $stmt->bind_param("si", $newHash, $admin['User_ID']);
        
        if ($stmt->execute()) {
            echo "<p class='success'>✅ Password updated successfully!</p>";
            
            // Verify update
            $verifyStmt = $conn->prepare("SELECT User_ID, Username, Password_Hash FROM users WHERE User_ID = ?");
            $verifyStmt->bind_param("i", $admin['User_ID']);
            $verifyStmt->execute();
            $verifyResult = $verifyStmt->get_result();
            $verifyUser = $verifyResult->fetch_assoc();
            
            echo "<h2>Verification</h2>";
            echo "<table>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>User ID</td><td>{$verifyUser['User_ID']}</td></tr>";
            echo "<tr><td>Username</td><td>{$verifyUser['Username']}</td></tr>";
            echo "<tr><td>Password Hash</td><td><code>" . substr($verifyUser['Password_Hash'], 0, 40) . "...</code></td></tr>";
            echo "</table>";
            
            // Test verification
            if (password_verify("Admin@123", $verifyUser['Password_Hash'])) {
                echo "<p class='success'>✅ Password verification works!</p>";
            } else {
                echo "<p class='error'>❌ Password verification still fails!</p>";
            }
            
            $verifyStmt->close();
        } else {
            echo "<p class='error'>❌ Update error: " . $stmt->error . "</p>";
        }
        
        $stmt->close();
    }
}

echo "<hr>";
echo "<h2>🎉 Next Steps</h2>";
echo "<ol>";
echo "<li>Go to: <a href='/Oxygym/Login.html'>/Oxygym/Login.html</a></li>";
echo "<li>Login with: <strong>admin</strong> / <strong>Admin@123</strong></li>";
echo "<li>You should be redirected to the admin dashboard</li>";
echo "</ol>";

echo "<p><a href='/Oxygym/debug-login.php' style='display:inline-block;margin-top:20px;padding:10px 20px;background:#ffcc00;color:#000;text-decoration:none;border-radius:4px;font-weight:bold;'>Test Login Again</a></p>";

echo "</div>";
echo "</div>";

$conn->close();

echo "</body></html>";
?>