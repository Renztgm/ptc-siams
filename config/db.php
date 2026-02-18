<?php
$host = "localhost";
$user = "root";
$pass = ""; // default in XAMPP
$db   = "PTC_Database";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>