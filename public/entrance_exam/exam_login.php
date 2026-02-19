<?php
session_start();
include('../../config/db_config.php');

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admission_id = trim($_POST['admission_id']);
    
    if (empty($admission_id)) {
        $error = 'Please enter your Admission ID';
    } else {
        // Query to get student information
        $query = "SELECT id, admission_id, full_name, given_name, last_name, email, program, status FROM admissions WHERE admission_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $admission_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            
            // Check if student status allows exam
            if ($student['status'] !== 'pending') {
                $error = 'Your admission status does not allow you to take the exam. Current status: ' . htmlspecialchars($student['status']);
            } else {
                // Store student info in session
                $_SESSION['exam_student'] = $student;
                $success = true;
                // Redirect to exam pre-start page
                header('Location: exam_confirmation.php');
                exit();
            }
        } else {
            $error = 'Admission ID not found. Please check and try again.';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Login - PTC Entrance Exam</title>
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
            max-width: 500px;
            margin: 40px auto;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .header-content {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header-content h2 {
            color: #1b5e20;
            margin-bottom: 10px;
            font-size: 22px;
        }
        
        .header-content p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #1b5e20;
            background-color: #f0f8f0;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background-color: #2e7d32;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #1b5e20;
        }
        
        button:active {
            transform: scale(0.98);
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            font-size: 14px;
        }
        
        .info-box {
            background-color: #f0f8f0;
            border-left: 5px solid #1b5e20;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #1b5e20;
            line-height: 1.6;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="../assets/Logo.png" alt="PTC Logo">
        <h1>Entrance Exam Portal</h1>
    </div>
    
    <div class="container">
        <div class="header-content">
            <h2>Entrance Exam Login</h2>
            <p>PTC SIAMS - Student Portal</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <strong>👤 How to proceed:</strong><br>
            Enter your Admission ID to access the entrance exam. Your admission ID can be found in your admission confirmation email.
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="admission_id">Admission ID *</label>
                <input 
                    type="text" 
                    id="admission_id" 
                    name="admission_id" 
                    placeholder="e.g., PTC-20260218-0509" 
                    required
                    autofocus
                >
            </div>
            
            <button type="submit">Access Exam</button>
        </form>
        
        <div class="footer">
            <p>If you don't have an admission ID, please contact the admissions office.</p>
        </div>
    </div>
</body>
</html>
