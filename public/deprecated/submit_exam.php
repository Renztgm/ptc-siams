<?php
session_start();
require_once __DIR__ . "/../config/db_config.php";

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
$exam_registration_id = intval($_POST['exam_registration_id'] ?? 0);
$exam_session_id = intval($_POST['exam_session_id'] ?? 0);

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

// Update exam_registrations table
if ($exam_registration_id > 0) {
    $update_registration = "UPDATE exam_registrations 
                          SET score = ?, 
                              score_percentage = ?, 
                              passing_score = ?,
                              result = ?, 
                              attendance_status = 'completed', 
                              attendance_time = NOW(),
                              result_date = NOW(),
                              status = 'completed'
                          WHERE id = ? AND admission_id = ?";
    
    $stmt = $conn->prepare($update_registration);
    if ($stmt) {
        $stmt->bind_param('dddsii', $score, $percentage, $passing_score, $result, $exam_registration_id, $student['id']);
        if (!$stmt->execute()) {
            $errors[] = "Failed to update exam registration: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $errors[] = "Failed to prepare registration statement: " . $conn->error;
    }
}

// Also save to exam_results for backward compatibility
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

$saveError = count($errors) > 0 ? implode("; ", $errors) : '';
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .success-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .status-passed {
            color: #4caf50;
        }
        
        .status-failed {
            color: #f44336;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        
        .result-status {
            font-size: 24px;
            font-weight: 600;
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        
        .result-status.passed {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .result-status.failed {
            background: #ffebee;
            color: #c62828;
        }
        
        .score-details {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .score-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .score-item:last-child {
            border-bottom: none;
        }
        
        .score-label {
            color: #666;
            font-weight: 600;
        }
        
        .score-value {
            color: #333;
            font-weight: 600;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
            color: #1565c0;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .error-box {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
            color: #c62828;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-logout {
            background: #e9ecef;
            color: #333;
            border: 2px solid #dee2e6;
        }
        
        .btn-logout:hover {
            background: #dee2e6;
        }
        
        button:active, a.button:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon <?php echo $result === 'Passed' ? 'status-passed' : 'status-failed'; ?>">
            <?php echo $result === 'Passed' ? '✓' : '✗'; ?>
        </div>
        
        <h1>Exam Submitted Successfully</h1>
        
        <div class="result-status <?php echo strtolower($result); ?>">
            Result: <?php echo htmlspecialchars($result); ?>
        </div>
        
        <div class="score-details">
            <div class="score-item">
                <span class="score-label">Total Score:</span>
                <span class="score-value"><?php echo $score; ?> out of <?php echo $totalQuestions; ?></span>
            </div>
            <div class="score-item">
                <span class="score-label">Percentage:</span>
                <span class="score-value"><?php echo $percentage; ?>%</span>
            </div>
            <div class="score-item">
                <span class="score-label">Passing Score:</span>
                <span class="score-value">75%</span>
            </div>
        </div>
        
        <div class="info-box">
            <strong>ℹ️ What's Next?</strong><br>
            Your exam has been recorded and your results will be reviewed by the administration. 
            You will receive an email notification with your final status.
        </div>
        
        <?php if (!empty($saveError)): ?>
            <div class="error-box">
                <strong>⚠️ Note:</strong> <?php echo htmlspecialchars($saveError); ?>
            </div>
        <?php endif; ?>
        
        <div class="button-group">
            <a href="exam_login.php" class="button btn-logout">Back to Home</a>
            <button type="button" class="btn-home" onclick="window.location.href='index.html'">Exit</button>
        </div>
    </div>
</body>
</html>