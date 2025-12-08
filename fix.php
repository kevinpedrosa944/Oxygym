<?php
// filepath: c:\xampp\htdocs\Oxygym\diagnose.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head><title>Full Diagnosis</title><style>
    body { font-family: monospace; padding: 20px; background: #1a1a2e; color: #fff; }
    .box { background: #2a2a3e; padding: 15px; margin-bottom: 15px; border-left: 4px solid #ffcc00; border-radius: 4px; }
    .error { border-left-color: #ef4444; }
    .success { border-left-color: #10b981; }
    code { background: #1a1a2e; padding: 2px 6px; border-radius: 3px; }
    pre { background: #1a1a2e; padding: 10px; overflow-x: auto; border-radius: 4px; max-height: 300px; }
    h2 { color: #ffcc00; }
</style></head>";
echo "<body>";

echo "<h1>🔍 Full OxyGym Diagnosis</h1>";

// 1. Check PHP version
echo "<div class='box success'>";
echo "<h2>✅ PHP Info</h2>";
echo "<p>Version: " . phpversion() . "</p>";
echo "<p>Extensions: " . (extension_loaded('mysqli') ? '✅ MySQLi' : '❌ MySQLi missing') . "</p>";
echo "<p>JSON: " . (extension_loaded('json') ? '✅ JSON' : '❌ JSON missing') . "</p>";
echo "</div>";

// 2. Check database
echo "<div class='box'>";
echo "<h2>🗄️ Database Connection</h2>";

$conn = new mysqli("localhost", "root", "", "oxygym");

if ($conn->connect_error) {
    echo "<div class='error'>❌ Connection failed: " . $conn->connect_error . "</div>";
} else {
    echo "<div class='success'>✅ Connected successfully</div>";
    
    // Check tables
    $tables = $conn->query("SHOW TABLES");
    echo "<p>Tables found:</p><ul>";
    while ($table = $tables->fetch_row()) {
        echo "<li>" . $table[0] . "</li>";
    }
    echo "</ul>";
    
    // Check users table structure
    echo "<h3>Users Table Structure</h3>";
    $columns = $conn->query("DESCRIBE users");
    echo "<table border='1' cellpadding='10' style='width:100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($col = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // List all users
    echo "<h3>All Users in Database</h3>";
    $users = $conn->query("SELECT User_ID, Username, Role, LENGTH(Password_Hash) as hash_len FROM users");
    echo "<table border='1' cellpadding='10' style='width:100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Hash Length</th></tr>";
    while ($user = $users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['User_ID'] . "</td>";
        echo "<td>" . $user['Username'] . "</td>";
        echo "<td>" . $user['Role'] . "</td>";
        echo "<td>" . $user['hash_len'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "</div>";

// 3. Test password verification
echo "<div class='box'>";
echo "<h2>🔐 Password Verification Test</h2>";

$testPassword = "Admin@123";
$testHash = password_hash($testPassword, PASSWORD_BCRYPT);

echo "<p>Test password: <code>$testPassword</code></p>";
echo "<p>Generated hash: <code>" . substr($testHash, 0, 30) . "...</code></p>";

if (password_verify($testPassword, $testHash)) {
    echo "<p class='success'>✅ Verification works</p>";
} else {
    echo "<p class='error'>❌ Verification failed</p>";
}

// Get actual admin hash from DB
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT Password_Hash FROM users WHERE Username = 'admin' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $actualHash = $row['Password_Hash'];
        
        echo "<h3>Admin User Password Test</h3>";
        echo "<p>Database hash: <code>" . substr($actualHash, 0, 30) . "...</code></p>";
        echo "<p>Hash algorithm: <code>" . (strpos($actualHash, '$2y$') === 0 ? 'bcrypt ($2y$)' : 'Unknown') . "</code></p>";
        
        if (password_verify($testPassword, $actualHash)) {
            echo "<p class='success'>✅ Admin password verifies correctly</p>";
        } else {
            echo "<p class='error'>❌ Admin password does NOT verify</p>";
            echo "<p>This means you need to run: <a href='/Oxygym/fix-admin-password.php' target='_blank'>/Oxygym/fix-admin-password.php</a></p>";
        }
    }
    
    $stmt->close();
}

echo "</div>";

// 4. Test API files exist
echo "<div class='box'>";
echo "<h2>📁 API Files Check</h2>";

$files = [
    'api/Login.php',
    'api/Register.php',
    'api/check_session.php',
    'includes/db_connect.php',
    'includes/auth.php'
];

foreach ($files as $file) {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/Oxygym/' . $file;
    $exists = file_exists($path);
    $status = $exists ? '✅ EXISTS' : '❌ MISSING';
    echo "<p>$file: $status</p>";
    
    if ($exists) {
        $size = filesize($path);
        $lines = count(file($path));
        echo "<p style='margin-left: 20px; color: #aaa;'>Size: " . $size . " bytes, Lines: " . $lines . "</p>";
    }
}

echo "</div>";

// 5. Test Login.php directly
echo "<div class='box'>";
echo "<h2>🧪 Test Login.php Direct Call</h2>";

$loginPhpPath = $_SERVER['DOCUMENT_ROOT'] . '/Oxygym/api/Login.php';

echo "<p>File: <code>$loginPhpPath</code></p>";
echo "<p>Exists: " . (file_exists($loginPhpPath) ? '✅ YES' : '❌ NO') . "</p>";

if (file_exists($loginPhpPath)) {
    $content = file_get_contents($loginPhpPath);
    
    // Check for common issues
    echo "<h3>Content Checks</h3>";
    echo "<ul>";
    echo "<li>" . (strpos($content, 'header(\'Content-Type: application/json\')') !== false ? '✅' : '❌') . " JSON header</li>";
    echo "<li>" . (strpos($content, 'include') !== false ? '✅' : '❌') . " Includes present</li>";
    echo "<li>" . (strpos($content, 'json_encode') !== false ? '✅' : '❌') . " JSON encode used</li>";
    echo "<li>" . (preg_match('/<html|<!DOCTYPE/i', $content) ? '❌' : '✅') . " No HTML tags</li>";
    echo "</ul>";
    
    $lines = explode("\n", $content);
    echo "<h3>First 10 lines:</h3>";
    echo "<pre>";
    for ($i = 0; $i < min(10, count($lines)); $i++) {
        echo htmlspecialchars($lines[$i]) . "\n";
    }
    echo "</pre>";
}

echo "</div>";

// 6. Error log tail
echo "<div class='box'>";
echo "<h2>📋 Recent Error Log</h2>";

$logFile = 'C:\\xampp\\apache\\logs\\error.log';

if (file_exists($logFile)) {
    $lines = file($logFile);
    $recent = array_slice($lines, -20);
    
    echo "<p style='color: #aaa;'>Showing last 20 lines containing 'oxygym' or 'error':</p>";
    echo "<pre>";
    foreach ($recent as $line) {
        if (stripos($line, 'oxygym') !== false || stripos($line, 'login') !== false) {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p>Log file not found at: $logFile</p>";
}

echo "</div>";

// 7. Quick login test via curl
echo "<div class='box'>";
echo "<h2>🔗 Curl Test (Simulating Browser)</h2>";

$ch = curl_init('http://localhost/Oxygym/api/Login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'admin', 'password' => 'Admin@123']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

echo "<p>HTTP Code: <strong>$httpCode</strong></p>";
echo "<p>Content-Type: <code>$contentType</code></p>";
echo "<p>Response:</p>";
echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";

curl_close($ch);

echo "</div>";

if (isset($conn)) {
    $conn->close();
}

echo "</body></html>";
?>