<?php
// Bulk Email Script - Send Exam Link to All Admitted Students

// Email configuration
$senderEmail = 'arquero.sofia.tcu@gmail.com';
$senderPassword = 'qjpf wvol cpgq tsoa'; // Gmail App Password
$senderName = 'PTC Admissions';

// Load exam configuration
if (file_exists(__DIR__ . '/exam_config.php')) {
    $examConfig = include __DIR__ . '/exam_config.php';
} else {
    $examConfig = [];
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    // GET requests don't require password (just reading data)
    
    if ($_GET['action'] === 'get_students') {
        // Get all students from database
        try {
            if (file_exists(__DIR__ . '/../config/db_config.php')) {
                require_once __DIR__ . '/../config/db_config.php';
                if (!$conn) {
                    throw new Exception("Database connection failed: " . mysqli_connect_error());
                }
                
                $program = isset($_GET['program']) ? $_GET['program'] : '';
                $query = "SELECT id, admission_id, email, given_name, last_name, program, exam_link_sent FROM admissions";
                
                if (!empty($program)) {
                    $program = $conn->real_escape_string($program);
                    $query .= " WHERE program = '$program'";
                }
                
                $query .= " ORDER BY submission_date DESC";
                
                $result = $conn->query($query);
                
                if (!$result) {
                    throw new Exception("Query failed: " . $conn->error);
                }
                
                $students = [];
                while ($row = $result->fetch_assoc()) {
                    $students[] = $row;
                }
                
                $conn->close();
                
                echo json_encode(['success' => true, 'students' => $students, 'count' => count($students)]);
            } else {
                throw new Exception("Database configuration not found");
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Handle POST requests (require password for security)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Security check
    $adminPassword = isset($_POST['password']) ? $_POST['password'] : '';
    if ($adminPassword !== 'ptc_admin_2026') { // Change this to match your password!
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
        exit;
    }
    
    if ($_POST['action'] === 'send_emails') {
        // Send emails to selected students
        try {
            $emails = isset($_POST['emails']) ? json_decode($_POST['emails'], true) : [];
            
            if (empty($emails)) {
                throw new Exception("No students selected");
            }
            
            if (file_exists(__DIR__ . '/../config/db_config.php')) {
                require_once __DIR__ . '/../config/db_config.php';
                if (!$conn) {
                    throw new Exception("Database connection failed: " . mysqli_connect_error());
                }
                
                $sentCount = 0;
                $failedCount = 0;
                $errors = [];
                
                foreach ($emails as $email) {
                    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
                    if (!$email) {
                        $failedCount++;
                        continue;
                    }
                    
                    // Get student details
                    $stmt = $conn->prepare("SELECT given_name, last_name, admission_id FROM admissions WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $student = $result->fetch_assoc();
                    $stmt->close();
                    
                    if (!$student) {
                        $failedCount++;
                        continue;
                    }
                    
                    // Send email
                    $emailSent = sendExamLinkEmail(
                        $senderEmail,
                        $senderPassword,
                        $senderName,
                        $email,
                        $student['given_name'],
                        $student['last_name'],
                        $student['admission_id'],
                        $examConfig
                    );
                    
                    if ($emailSent) {
                        $sentCount++;
                        
                        // Log the email send
                        $logStmt = $conn->prepare("UPDATE admissions SET email_sent_date = NOW(), exam_link_sent = 1 WHERE email = ?");
                        $logStmt->bind_param("s", $email);
                        $logStmt->execute();
                        $logStmt->close();
                    } else {
                        $failedCount++;
                        $errors[] = "Failed to send to: " . $email;
                    }
                    
                    // Small delay to avoid rate limiting
                    usleep(500000); // 0.5 second delay
                }
                
                $conn->close();
                
                echo json_encode([
                    'success' => true,
                    'message' => "Sent $sentCount emails successfully",
                    'sent' => $sentCount,
                    'failed' => $failedCount,
                    'errors' => $errors
                ]);
            } else {
                throw new Exception("Database configuration not found");
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($_POST['action'] === 'schedule_emails') {
        // Schedule emails to be sent at a later time
        try {
            $emails = isset($_POST['emails']) ? json_decode($_POST['emails'], true) : [];
            $scheduleDate = isset($_POST['schedule_date']) ? $_POST['schedule_date'] : '';
            $scheduleTime = isset($_POST['schedule_time']) ? $_POST['schedule_time'] : '';
            
            if (empty($emails)) {
                throw new Exception("No students selected");
            }
            
            if (empty($scheduleDate) || empty($scheduleTime)) {
                throw new Exception("Schedule date and time are required");
            }
            
            // Create scheduled timestamp with timezone awareness
            date_default_timezone_set('Asia/Manila'); // Set to Philippines timezone
            $scheduledDateTime = new DateTime($scheduleDate . ' ' . $scheduleTime, new DateTimeZone('Asia/Manila'));
            $scheduledTimestamp = $scheduledDateTime->getTimestamp();
            
            // Validate timestamp is in the future
            if ($scheduledTimestamp <= time()) {
                throw new Exception("Scheduled time must be in the future");
            }
            
            // Create scheduled emails JSON file
            $scheduledDir = __DIR__ . '/../storage/scheduled_emails';
            if (!is_dir($scheduledDir)) {
                mkdir($scheduledDir, 0755, true);
            }
            
            // Create a unique filename for this batch
            $batchId = 'batch_' . date('YmdHis') . '_' . uniqid();
            $scheduleFile = $scheduledDir . '/' . $batchId . '.json';
            
            $scheduleData = [
                'batch_id' => $batchId,
                'scheduled_time' => $scheduledDateTime->format('Y-m-d H:i:s'),
                'scheduled_timestamp' => $scheduledTimestamp,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'pending',
                'emails' => $emails
            ];
            
            if (file_put_contents($scheduleFile, json_encode($scheduleData, JSON_PRETTY_PRINT))) {
                echo json_encode([
                    'success' => true,
                    'message' => count($emails) . ' emails scheduled for ' . $scheduledDateTime->format('Y-m-d H:i:s'),
                    'batch_id' => $batchId,
                    'count' => count($emails)
                ]);
            } else {
                throw new Exception("Failed to save scheduled email file");
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Function to send exam link email
function sendExamLinkEmail($senderEmail, $senderPassword, $senderName, $recipientEmail, $firstName, $lastName, $admissionId, $examConfig = []) {
    // Set default exam config if not provided
    $examConfig = array_merge([
        'exam_date' => '2026-06-01',
        'exam_start_time' => '09:00',
        'exam_end_time' => '12:00',
        'exam_format' => 'Online',
        'exam_location' => 'Online',
        'exam_link' => '[Exam Link Not Set]',
        'exam_link_description' => 'Check your email for the official exam link'
    ], $examConfig);

    // Try PHP mail first
    $subject = 'PTC Entrance Exam - Link and Instructions';
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $senderName . " <" . $senderEmail . ">\r\n";
    $headers .= "Reply-To: " . $senderEmail . "\r\n";
    
    $body = getExamLinkEmailTemplate($firstName, $lastName, $admissionId, $examConfig);
    
    $result = @mail($recipientEmail, $subject, $body, $headers);
    
    if ($result) {
        error_log("Exam link email sent to: " . $recipientEmail);
        return true;
    }
    
    // Fallback to SMTP if mail() fails
    error_log("mail() failed, attempting SMTP");
    return sendViaSmtp($senderEmail, $senderPassword, $senderName, $recipientEmail, $subject, $body);
}

function sendViaSmtp($senderEmail, $senderPassword, $senderName, $recipientEmail, $subject, $body) {
    try {
        $password = str_replace(' ', '', $senderPassword);
        $host = 'smtp.gmail.com';
        $port = 587;
        
        $socket = @fsockopen($host, $port, $errno, $errstr, 10);
        if (!$socket) return false;
        
        $readResponse = function($sock) {
            $response = "";
            while ($line = fgets($sock, 512)) {
                $response .= $line;
                if (preg_match('/^\d{3} /', trim($line))) break;
            }
            return $response;
        };
        
        $readResponse($socket);
        fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $readResponse($socket);
        
        fputs($socket, "STARTTLS\r\n");
        $readResponse($socket);
        
        @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        
        fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $readResponse($socket);
        
        fputs($socket, "AUTH LOGIN\r\n");
        $readResponse($socket);
        fputs($socket, base64_encode($senderEmail) . "\r\n");
        $readResponse($socket);
        fputs($socket, base64_encode($password) . "\r\n");
        $readResponse($socket);
        
        fputs($socket, "MAIL FROM:<" . $senderEmail . ">\r\n");
        $readResponse($socket);
        fputs($socket, "RCPT TO:<" . $recipientEmail . ">\r\n");
        $readResponse($socket);
        fputs($socket, "DATA\r\n");
        $readResponse($socket);
        
        $message = "From: " . $senderName . " <" . $senderEmail . ">\r\n";
        $message .= "To: " . $recipientEmail . "\r\n";
        $message .= "Subject: " . $subject . "\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $message .= $body . "\r\n\r\n.\r\n";
        
        fputs($socket, $message);
        $readResponse($socket);
        fputs($socket, "QUIT\r\n");
        @fclose($socket);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function getExamLinkEmailTemplate($firstName, $lastName, $admissionId, $examConfig) {
    $examDate = new DateTime($examConfig['exam_date']);
    $examDateStr = $examDate->format('F j, Y');
    
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .header { background-color: #2e7d32; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; max-width: 600px; margin: 0 auto; }
            .link-box { background-color: #fff3cd; border: 2px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center; }
            .exam-link { font-size: 18px; font-weight: bold; color: #2e7d32; word-break: break-all; }
            .instructions { background-color: #f9f9f9; border-left: 4px solid #2e7d32; padding: 15px; margin: 15px 0; }
            .footer { background-color: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>PTC Entrance Exam</h1>
            <p>Online Examination Link</p>
        </div>
        <div class='content'>
            <p>Dear <strong>" . htmlspecialchars($firstName . ' ' . $lastName) . "</strong>,</p>
            <p>Your entrance examination link is now ready! Below you will find all the information you need to take the exam.</p>
            
            <div class='link-box'>
                <p><strong>Your Exam Link:</strong></p>
                <p class='exam-link'><a href='" . htmlspecialchars($examConfig['exam_link']) . "' style='color: #2e7d32; text-decoration: none;'>" . htmlspecialchars($examConfig['exam_link']) . "</a></p>
                <p style='margin-top: 10px; font-size: 12px; color: #666;'>📋 Admission ID: " . htmlspecialchars($admissionId) . "</p>
            </div>
            
            <h3>📅 Exam Schedule:</h3>
            <ul>
                <li><strong>Date:</strong> " . htmlspecialchars($examDateStr) . "</li>
                <li><strong>Time:</strong> " . htmlspecialchars($examConfig['exam_start_time']) . " to " . htmlspecialchars($examConfig['exam_end_time']) . "</li>
                <li><strong>Format:</strong> " . htmlspecialchars($examConfig['exam_format']) . "</li>
                <li><strong>Duration:</strong> 3 hours</li>
            </ul>
            
            <div class='instructions'>
                <h3>⚠️ Important Instructions:</h3>
                <ol>
                    <li>Click the link above exactly at the scheduled start time</li>
                    <li>Use your Admission ID when logging in: <strong>" . htmlspecialchars($admissionId) . "</strong></li>
                    <li>Ensure you have a stable internet connection</li>
                    <li>Close all unnecessary applications and browser tabs</li>
                    <li>Do not refresh the page during the exam</li>
                    <li>Allow camera and microphone access when prompted</li>
                    <li>Have your ID ready for verification</li>
                </ol>
            </div>
            
            <p><strong>Need Help?</strong><br>
            If you experience any technical issues, please contact the PTC Admissions Office immediately at the number provided in your admission form.</p>
            
            <p style='margin-top: 30px;'>Best of luck!<br><strong>PTC Admissions Team</strong></p>
        </div>
        <div class='footer'>
            <p>&copy; 2026 Pateros Technological College. All rights reserved.</p>
        </div>
    </body>
    </html>
    ";
}
?>
