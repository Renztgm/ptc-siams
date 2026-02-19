<?php
$conn = mysqli_connect("localhost", "root", "", "entrance_exam");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>