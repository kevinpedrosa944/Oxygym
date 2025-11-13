<?php
// Demo users (replace with DB query in production)
$DEMO_USERS = [
    'admin' => password_hash('12345', PASSWORD_DEFAULT),
    'user'  => password_hash('password123', PASSWORD_DEFAULT)
];
?>