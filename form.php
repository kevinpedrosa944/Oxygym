<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $plan = $_POST['plan'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $birthday = $_POST['birthday'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];

    // Example: simple validation
    if (!empty($name) && !empty($address) && !empty($birthday) && !empty($age) && !empty($gender)) {
        // Here you can save to database, send email, etc.
        echo "<h2>Registration Successful!</h2>";
        echo "<p>Plan: $plan</p>";
        echo "<p>Name: $name</p>";
        echo "<p>Address: $address</p>";
        echo "<p>Birthday: $birthday</p>";
        echo "<p>Age: $age</p>";
        echo "<p>Gender: $gender</p>";
    } else {
        echo "<h2>Registration Failed. Please fill all fields.</h2>";
    }
} else {
    echo "<h2>Invalid Access</h2>";
}
?>
