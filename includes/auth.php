<?php
if (!isset($_SESSION['username'])) {
    header("Location: /Oxygym/Login.html");
    exit();
}
?>