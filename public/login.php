<?php
session_start();
// 1. Database Configuration
$host = "sql302.infinityfree.com";
$db_user = "if0_41171248";
$db_pass = "vJoJA8PL88TC";
$db_name = "if0_41171248_ptc_database";


$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // 1. Verify Login Credentials from Stud_account
    $stmt = $conn->prepare("SELECT studentid, username FROM Stud_account WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();
        $_SESSION['studentid'] = $userData['studentid'];
        $_SESSION['username'] = $userData['username'];

        // 2. Fetch Exam Results based on the student's name/id
        // Note: In your SQL, some records use Fullname instead of ID
        $examStmt = $conn->prepare("SELECT score, total_questions, status FROM exam_results WHERE admission_id = ? OR fullname = ? LIMIT 1");
        $examStmt->bind_param("ss", $userData['studentid'], $userData['username']);
        $examStmt->execute();
        $examResult = $examStmt->get_result();
        
        if ($examRow = $examResult->fetch_assoc()) {
            $_SESSION['exam_score'] = $examRow['score'] . "/" . $examRow['total_questions'];
            $_SESSION['exam_status'] = $examRow['status'];
        } else {
            $_SESSION['exam_score'] = "N/A";
            $_SESSION['exam_status'] = "Not Taken";
        }

        header("Location: Student_portal.php");
    } else {
        echo "<script>alert('Invalid Credentials'); window.location='student.php';</script>";
    }
}
?>