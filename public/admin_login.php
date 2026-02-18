<?php
session_start();
include "../config/db_config.php"; // Use proper config with InfinityFree support

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username']; //
    $password = $_POST['password']; //

    // Use prepared statements to find the user
    $sql = "SELECT * FROM Admin_account WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // SIMPLIFIED: Direct comparison instead of password_verify
            if ($password === $row['password']) { 
                $_SESSION['user'] = $row['username'];
                header("Location: admin_dashboard.html"); //
                exit();
            } else {
                echo "<script>alert('Invalid Password'); window.location='admin.php';</script>";
            }
        } else {
            echo "<script>alert('User not found'); window.location='admin.php';</script>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('Database error: " . mysqli_error($conn) . "'); window.location='admin.php';</script>";
    }
}
?>