<?php
// filepath: c:\xampp\htdocs\Oxygym\debug-login.php

session_start();

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head><title>Debug Login</title><style>
    body { font-family: monospace; padding: 20px; background: #1a1a2e; color: #fff; }
    .card { background: #2a2a3e; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ffcc00; }
    h2 { color: #ffcc00; }
    code { background: #1a1a2e; padding: 2px 6px; border-radius: 3px; }
    .success { color: #10b981; }
    .error { color: #ef4444; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    td { padding: 8px; border-bottom: 1px solid #444; }
    input { padding: 8px; width: 100%; margin-bottom: 10px; }
    button { background: #ffcc00; color: #000; padding: 10px 20px; cursor: pointer; border: none; border-radius: 4px; }
</style></head>";
echo "<body>";

include('includes/db_connect.php');

echo "<div class='card'>";
echo "<h2>🔐 Debug Login System</h2>";
echo "<p>Session ID: <code>" . session_id() . "</code></p>";
echo "<p>PHP Version: <code>" . phpversion() . "</code></p>";
echo "</div>";

// Check database connection
echo "<div class='card'>";
echo "<h2>📊 Database Status</h2>";
if ($conn->connect_error) {
    echo "<p class='error'>❌ Connection Error: " . $conn->connect_error . "</p>";
} else {
    echo "<p class='success'>✅ Connected to database</p>";
    
    // Check users table
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $count = $result->fetch_assoc()['count'];
    echo "<p>Total users in database: <strong>$count</strong></p>";
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Password Hash Preview</th></tr>";
    $result = $conn->query("SELECT User_ID, Username, Role, PASSWORD_HASH FROM users");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $hashPreview = substr($row['PASSWORD_HASH'], 0, 20) . "...";
            echo "<tr>";
            echo "<td>{$row['User_ID']}</td>";
            echo "<td>{$row['Username']}</td>";
            echo "<td><strong>{$row['Role']}</strong></td>";
            echo "<td><code>$hashPreview</code></td>";
            echo "</tr>";
        }
    }
    echo "</table>";
}
echo "</div>";

// Test login form
echo "<div class='card'>";
echo "<h2>🧪 Test Login</h2>";
echo "<form method='POST'>";
echo "<input type='text' name='username' placeholder='Username' value='admin'>";
echo "<input type='password' name='password' placeholder='Password' value='Admin@123'>";
echo "<button type='submit'>Test Login</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<hr>";
    echo "<h3>Testing: $username</h3>";
    
    // Query user
    $stmt = $conn->prepare("SELECT User_ID, Username, Password_Hash, Role FROM users WHERE Username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<p class='error'>❌ User not found</p>";
    } else {
        $user = $result->fetch_assoc();
        echo "<p class='success'>✅ User found: {$user['Username']}</p>";
        echo "<p>Role: <strong>{$user['Role']}</strong></p>";
        echo "<p>Password Hash: <code>" . substr($user['Password_Hash'], 0, 30) . "...</code></p>";
        
        // Test password
        if (password_verify($password, $user['Password_Hash'])) {
            echo "<p class='success'>✅ Password verified!</p>";
            echo "<p><a href='/Oxygym/api/Login.php'>Try actual login →</a></p>";
        } else {
            echo "<p class='error'>❌ Password verification failed</p>";
            echo "<p>Password provided: <code>$password</code></p>";
        }
    }
    
    $stmt->close();
}

echo "</div>";

// Check error log
echo "<div class='card'>";
echo "<h2>📋 Recent Error Log</h2>";
$logFile = 'C:\\xampp\\apache\\logs\\error.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recent = array_slice($lines, -10);
    echo "<pre style='background: #1a1a2e; padding: 10px; overflow-x: auto;'>";
    foreach ($recent as $line) {
        if (strpos($line, 'Login') !== false || strpos($line, 'oxygym') !== false) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p class='error'>Log file not found at: $logFile</p>";
}
echo "</div>";

$conn->close();

echo "</body></html>";
?>