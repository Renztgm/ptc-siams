<?php
session_start();

// Check if student is authenticated
if (!isset($_SESSION['exam_student'])) {
    header('Location: exam_login.php');
    exit();
}

$student = $_SESSION['exam_student'];

$proceed = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm'])) {
        $proceed = true;
        header('Location: exam_session_selection.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Information - PTC Entrance Exam</title>
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
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .info-section {
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .info-section h2 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
            min-width: 150px;
        }
        
        .info-value {
            color: #333;
            font-size: 16px;
            word-break: break-all;
        }
        
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #856404;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        
        button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-back {
            background: #e9ecef;
            color: #333;
            border: 2px solid #dee2e6;
        }
        
        .btn-back:hover {
            background: #dee2e6;
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .instructions {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #1565c0;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Confirm Your Information</h1>
            <p>Please verify your details before starting the exam</p>
        </div>
        
        <div class="instructions">
            <strong>ℹ️ Important:</strong> Please verify that all your information is correct. If any details are incorrect, please contact the admissions office.
        </div>
        
        <div class="info-section">
            <h2>Student Information</h2>
            <div class="info-row">
                <span class="info-label">Full Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($student['full_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Admission ID:</span>
                <span class="info-value"><?php echo htmlspecialchars($student['admission_id']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($student['email']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Program:</span>
                <span class="info-value"><?php echo htmlspecialchars($student['program']); ?></span>
            </div>
        </div>
        
        <div class="warning-box">
            ⚠️ <strong>Important:</strong> Once you start the exam, you will have a limited time to complete it. Closing the browser or refreshing the page may result in loss of your exam data.
        </div>
        
        <form method="POST" action="">
            <div class="button-group">
                <button type="button" class="btn-back" onclick="window.location.href='exam_login.php'">Back</button>
                <button type="submit" class="btn-confirm" name="confirm" value="yes">Next</button>
            </div>
        </form>
    </div>
</body>
</html>
