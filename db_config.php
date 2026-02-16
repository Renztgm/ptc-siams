<?php
$host = "localhost";
$db_user = "root"; // Default XAMPP user
$db_pass = "";     // Default XAMPP password is empty
$db_name = "ptc_system";

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>