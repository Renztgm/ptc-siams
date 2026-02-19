<?php
session_start();
include('../../config/db_config.php');

// Check if student is authenticated
if (!isset($_SESSION['exam_student'])) {
    header('Location: exam_login.php');
    exit();
}

$student = $_SESSION['exam_student'];
$error = '';
$sessions = [];

// Get available exam sessions
$query = "SELECT id, session_name, exam_date, exam_start_time, exam_end_time, exam_location, capacity, 
                 (SELECT COUNT(*) FROM exam_registrations WHERE exam_session_id = exam_sessions.id) as registered_count
          FROM exam_sessions 
          WHERE status = 'scheduled' AND exam_date >= CURDATE()
          ORDER BY exam_date ASC, exam_start_time ASC";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $session_id = intval($_POST['session_id']);
    
    if (empty($session_id)) {
        $error = 'Please select an exam session';
    } else {
        // Verify session exists
        $verify_query = "SELECT id FROM exam_sessions WHERE id = ? AND status = 'scheduled'";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param('i', $session_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows == 0) {
            $error = 'Selected exam session is no longer available';
        } else {
            // Check if already registered
            $check_query = "SELECT id FROM exam_registrations WHERE admission_id = ? AND exam_session_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param('ii', $student['id'], $session_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = 'You are already registered for this session';
            } else {
                // Register student for exam session
                $register_query = "INSERT INTO exam_registrations (admission_id, exam_session_id, status) 
                                 VALUES (?, ?, 'registered')";
                $register_stmt = $conn->prepare($register_query);
                $register_stmt->bind_param('ii', $student['id'], $session_id);
                
                if ($register_stmt->execute()) {
                    $_SESSION['exam_session_id'] = $session_id;
                    $_SESSION['exam_registration_id'] = $register_stmt->insert_id;
                    header('Location: entrance_exam.php');
                    exit();
                } else {
                    $error = 'Failed to register for exam session. Please try again.';
                }
                $register_stmt->close();
            }
            $check_stmt->close();
        }
        $verify_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Exam Session - PTC Entrance Exam</title>
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
            max-width: 700px;
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
        
        .error {
            background-color: #fee;
            color: #c00;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c00;
            font-size: 14px;
        }
        
        .sessions-container {
            margin-bottom: 25px;
        }
        
        .session-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .session-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }
        
        .session-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
        }
        
        .session-card input[type="radio"]:checked + .session-content {
            color: #667eea;
        }
        
        .session-card input[type="radio"]:checked ~ .checkmark {
            background: #667eea;
            border-color: #667eea;
        }
        
        .session-card input[type="radio"]:checked ~ .checkmark:after {
            display: block;
        }
        
        .checkmark {
            position: absolute;
            top: 20px;
            right: 20px;
            height: 24px;
            width: 24px;
            background-color: white;
            border: 2px solid #ccc;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
            left: 6px;
            top: 2px;
            width: 6px;
            height: 12px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        .session-content {
            padding-right: 40px;
        }
        
        .session-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .session-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
        }
        
        .detail-icon {
            width: 20px;
            display: inline-block;
            margin-right: 8px;
            text-align: center;
        }
        
        .capacity {
            grid-column: 1 / -1;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #e9ecef;
        }
        
        .capacity.full {
            color: #c00;
            font-weight: 600;
        }
        
        .capacity.available {
            color: #2d5f2e;
            font-weight: 600;
        }
        
        .no-sessions {
            text-align: center;
            padding: 40px 20px;
            color: #999;
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
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Select Exam Session</h1>
            <p>Choose a session and register to take the exam</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="sessions-container">
                <?php if (count($sessions) > 0): ?>
                    <?php foreach ($sessions as $session): ?>
                        <?php 
                            $available = $session['capacity'] ? ($session['capacity'] - $session['registered_count']) : true;
                            $session_date = new DateTime($session['exam_date']);
                            $session_start = new DateTime($session['exam_start_time']);
                            $session_end = new DateTime($session['exam_end_time']);
                        ?>
                        <label class="session-card">
                            <input type="radio" name="session_id" value="<?php echo $session['id']; ?>" required>
                            <div class="session-content">
                                <div class="session-name"><?php echo htmlspecialchars($session['session_name']); ?></div>
                                <div class="session-details">
                                    <div class="detail-item">
                                        <span class="detail-icon">📅</span>
                                        <span><?php echo $session_date->format('M d, Y'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-icon">⏰</span>
                                        <span><?php echo $session_start->format('h:i A') . ' - ' . $session_end->format('h:i A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-icon">📍</span>
                                        <span><?php echo htmlspecialchars($session['exam_location'] ?: 'Online'); ?></span>
                                    </div>
                                    <?php if ($session['exam_link']): ?>
                                    <div class="detail-item">
                                        <span class="detail-icon">🔗</span>
                                        <span>Exam Link Provided</span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="capacity <?php echo ($available === true || $available > 0) ? 'available' : 'full'; ?>">
                                        <?php 
                                            if ($available === true) {
                                                echo '✓ Slots Available';
                                            } elseif ($available > 0) {
                                                echo '✓ ' . $available . ' slot' . ($available > 1 ? 's' : '') . ' available';
                                            } else {
                                                echo '✗ Session Full';
                                            }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <span class="checkmark"></span>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-sessions">
                        <p>No exam sessions available at this time.</p>
                        <p style="margin-top: 10px; font-size: 13px;">Please contact the admissions office to schedule your exam.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (count($sessions) > 0): ?>
            <div class="button-group">
                <button type="button" class="btn-back" onclick="window.location.href='exam_confirmation.php'">Back</button>
                <button type="submit" class="btn-submit">Register & Continue</button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
