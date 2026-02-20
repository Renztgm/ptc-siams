<?php
session_start();
require_once __DIR__ . "/../../config/db_config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: exam_login.php');
    exit;
}

// Check if student session exists
if (!isset($_SESSION['exam_student'])) {
    header('Location: exam_login.php');
    exit;
}

$student = $_SESSION['exam_student'];

// Get exam timing information
$admission_id = $_POST['admission_id'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';

// Calculate exam duration
$exam_duration_minutes = 0;
if (!empty($start_time) && !empty($end_time)) {
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $interval = $start->diff($end);
    $exam_duration_minutes = ($interval->h * 60) + $interval->i;
    
    // Check if exam exceeded 3 hours (180 minutes)
    $actual_end = new DateTime();
    $current_start = new DateTime($start_time);
    $actual_interval = $current_start->diff($actual_end);
    $actual_duration_minutes = ($actual_interval->h * 60) + $actual_interval->i;
    
    if ($actual_duration_minutes > 180) {
        // Exam exceeded 3 hours - auto submit with current score
    }
}

// Answer key
$answers = [
    1, // Q1 = B. doesn't
    1, // Q2 = B. Fast
    2, // Q3 = C. 100
    2, // Q4 = C. 25
    2, // Q5 = C. Heart
    2  // Q6 = C. Jose Rizal
];

$score = 0;
$totalQuestions = count($answers);

// Calculate score
for ($i = 1; $i <= $totalQuestions; $i++) {
    if (isset($_POST["q$i"]) && $_POST["q$i"] == $answers[$i-1]) {
        $score++;
    }
}

$percentage = round(($score / $totalQuestions) * 100, 2);
$passing_score = 15; // Example: 75% of 20 questions = 15
$result = $percentage >= 75 ? 'Passed' : 'Failed';

$fullname = $_POST['fullname'] ?? '';
$email    = $_POST['email'] ?? '';

$errors = [];

// Store result to exam_results table (simplified system - no session tracking)
// Try to insert with admission_id and timing info
$insert_result = "INSERT INTO exam_results (admission_id, fullname, email, score, total_questions, status, start_time, end_time, exam_duration_minutes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($insert_result);
if ($stmt) {
    $stmt->bind_param('sssiisssi', $admission_id, $fullname, $email, $score, $totalQuestions, $result, $start_time, $end_time, $exam_duration_minutes);
    if (!$stmt->execute()) {
        // If the query fails (columns might not exist), try without the new columns
        $insert_result = "INSERT INTO exam_results (fullname, email, score, total_questions, status)
                         VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_result);
        if ($stmt) {
            $stmt->bind_param('ssiis', $fullname, $email, $score, $totalQuestions, $result);
            if (!$stmt->execute()) {
                $errors[] = "Failed to insert exam result: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Failed to prepare result statement: " . $conn->error;
        }
    } else {
        $stmt->close();
    }
} else {
    // Fallback: insert without new columns
    $insert_result = "INSERT INTO exam_results (fullname, email, score, total_questions, status)
                     VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_result);
    if ($stmt) {
        $stmt->bind_param('ssiis', $fullname, $email, $score, $totalQuestions, $result);
        if (!$stmt->execute()) {
            $errors[] = "Failed to insert exam result: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $errors[] = "Failed to prepare result statement: " . $conn->error;
    }
}

$saveError = count($errors) > 0 ? implode("; ", $errors) : '';

// Update admission table if student passed the exam
if ($result === 'Passed' && !empty($admission_id) && empty($saveError)) {
    $current_datetime = date('Y-m-d H:i:s');
    $update_admission = "UPDATE admissions SET status = 'admitted', admission_date = ? WHERE admission_id = ?";
    $stmt_update = $conn->prepare($update_admission);
    if ($stmt_update) {
        $stmt_update->bind_param('ss', $current_datetime, $admission_id);
        if (!$stmt_update->execute()) {
            $errors[] = "Failed to update admission status: " . $stmt_update->error;
            $saveError = implode("; ", $errors);
        }
        $stmt_update->close();
    } else {
        $errors[] = "Failed to prepare admission update: " . $conn->error;
        $saveError = implode("; ", $errors);
    }
}

// Send email to student with exam result
require_once 'send_exam_result_email.php';

$email_result = array('success' => false, 'message' => '');
if (!empty($email) && empty($saveError)) {
    $email_result = sendExamResultEmail($fullname, $email, $admission_id, $score, $totalQuestions, $percentage, $result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - PTC Entrance Exam</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        
        .header {
            background-color: #1b5e20;
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header img {
            height: 50px;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 700;
        }
        
        .container {
            max-width: 700px;
            margin: 40px auto;
            padding: 0 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
        }
        
        h1 {
            color: #1b5e20;
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: 700;
        }
        
        .result-status {
            font-size: 18px;
            font-weight: 600;
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        
        .result-status.passed {
            background: #d4edda;
            color: #155724;
        }
        
        .result-status.failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .info-box {
            background: #f0f8f0;
            border-left: 5px solid #1b5e20;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            text-align: left;
            color: #1b5e20;
            font-size: 15px;
            line-height: 1.8;
        }
        
        .error-box {
            background: #f8d7da;
            border-left: 5px solid #dc3545;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
            color: #721c24;
            font-size: 14px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        button, a.button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            display: inline-block;
        }
        
        .btn-home {
            background-color: #2e7d32;
            color: white;
        }
        
        .btn-home:hover {
            background-color: #1b5e20;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(27, 94, 32, 0.3);
        }
        
        .btn-logout {
            background: white;
            color: #1b5e20;
            border: 2px solid #1b5e20;
        }
        
        .btn-logout:hover {
            background: #f0f8f0;
            border-color: #2e7d32;
        }
        
        button:active, a.button:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="../assets/Logo.png" alt="PTC Logo">
        <h1>Entrance Exam - Result Confirmation</h1>
    </div>
    
    <div class="container">
        <h1>Exam Submitted Successfully</h1>
        
        <div class="info-box">
            <strong>ℹ️ Your Results</strong><br>
            Your exam has been successfully submitted and recorded in our system.
        </div>
        
        <?php if ($email_result['success']): ?>
            <div class="info-box" style="background-color: #d4edda; color: #155724; border-left-color: #28a745;">
                <strong>✓ Email Sent</strong><br>
                Your exam results have been sent to <strong><?php echo htmlspecialchars($email); ?></strong>
            </div>
        <?php elseif (!empty($email_result['message'])): ?>
            <div class="error-box">
                <strong>⚠️ Email Status:</strong> <?php echo htmlspecialchars($email_result['message']); ?>
                <?php if (!empty($email_result['note'])): ?>
                    <br><span style="font-size: 12px;"><?php echo htmlspecialchars($email_result['note']); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($saveError)): ?>
            <div class="error-box">
                <strong>⚠️ System Note:</strong> <?php echo htmlspecialchars($saveError); ?>
            </div>
        <?php endif; ?>
        
        <div class="button-group">
            <a href="exam_login.php" class="button btn-logout">Back to Home</a>
            <button type="button" class="btn-home" onclick="window.location.href='../../index.html'">Exit</button>
        </div>
    </div>
</body>
</html>
