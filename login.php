<?php
session_start();

include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studentid = $_POST['studentid'];
    $pass = $_POST['password'];

    // Use prepared statements to prevent SQL Injection
    $stmt = $conn->prepare("SELECT * FROM Stud_account WHERE studentid = ? AND password = ?");
    
    if (!$stmt) {
        $error = "Prepare Error: " . $conn->error;
        echo "<script>console.error('" . addslashes($error) . "');</script>";
        die($error);
    }
    
    if (!$stmt->bind_param("ss", $studentid, $pass)) {
        $error = "Bind Error: " . $stmt->error;
        echo "<script>console.error('" . addslashes($error) . "');</script>";
        die($error);
    }
    
    if (!$stmt->execute()) {
        $error = "Execute Error: " . $stmt->error;
        echo "<script>console.error('" . addslashes($error) . "');</script>";
        die($error);
    }
    
    $result = $stmt->get_result();
    
    if (!$result) {
        $error = "Get Result Error: " . $stmt->error;
        echo "<script>console.error('" . addslashes($error) . "');</script>";
        die($error);
    }

    if ($result->num_rows > 0) {
        // Successful login
        $_SESSION['studentid'] = $studentid;
        header("Location: Student_portal.html");
        exit();
    } else {
        // Failed login
        echo "<script>alert('Invalid Student ID or Password'); window.location.href='student.php';</script>";
    }
    $stmt->close();
}
$conn->close();
?>