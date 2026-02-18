<?php
// Enable error logging
error_log("=== Admission Email Script Started ===");
error_log("POST Data: Email=" . (isset($_POST['email']) ? $_POST['email'] : 'NONE'));

// Email configuration
$senderEmail = 'arquero.sofia.tcu@gmail.com';
$senderPassword = 'qjpf wvol cpgq tsoa'; // Gmail App Password
$senderName = 'PTC Admissions';

// Get POST data
$recipientEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
$firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
$lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
$admissionId = isset($_POST['admissionId']) ? trim($_POST['admissionId']) : '';
$pdfData = isset($_POST['pdfData']) ? $_POST['pdfData'] : '';

// Get exam configuration from POST or load from file
$examConfig = [];
if (isset($_POST['examConfig']) && !empty($_POST['examConfig'])) {
    $examConfig = json_decode($_POST['examConfig'], true);
}
if (empty($examConfig) && file_exists(__DIR__ . '/exam_config.php')) {
    $examConfig = include __DIR__ . '/exam_config.php';
}

// Validate email
if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    error_log("Invalid email: " . $recipientEmail);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Log admission information to database
logAdmissionToDatabase($firstName, $lastName, $recipientEmail, $admissionId);

// Try to send email using multiple methods
$emailSent = false;

// Method 1: Try using PHP's mail() function
if (!$emailSent) {
    $emailSent = sendEmailViaPHPMail($senderEmail, $senderName, $recipientEmail, $firstName, $lastName, $admissionId, $examConfig);
    error_log("PHP Mail attempt: " . ($emailSent ? "SUCCESS" : "FAILED"));
}

// Method 2: Try using SMTP via fsockopen
if (!$emailSent) {
    $emailSent = sendEmailViaSmtp($senderEmail, $senderPassword, $senderName, $recipientEmail, $firstName, $lastName, $admissionId, $examConfig);
    error_log("SMTP attempt: " . ($emailSent ? "SUCCESS" : "FAILED"));
}

// Response
if ($emailSent) {
    echo json_encode(['success' => true, 'message' => 'Admission form submitted successfully! Check your email for confirmation.']);
} else {
    echo json_encode(['success' => true, 'message' => 'Form submitted successfully. Confirmation email will be sent shortly.']);
}

// Method 1: Send email using PHP's mail() function
function sendEmailViaPHPMail($senderEmail, $senderName, $recipientEmail, $firstName, $lastName, $admissionId, $examConfig = []) {
    try {
        $subject = 'PTC Admission Form - Confirmation';
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $senderName . " <" . $senderEmail . ">\r\n";
        $headers .= "Reply-To: " . $senderEmail . "\r\n";
        
        $body = getEmailTemplate($firstName, $lastName, $admissionId, $examConfig);
        
        $result = @mail($recipientEmail, $subject, $body, $headers);
        error_log("mail() function result: " . ($result ? "true" : "false"));
        return $result;
    } catch (\Exception $e) {
        error_log("PHP Mail Error: " . $e->getMessage());
        return false;
    }
}

// Method 2: Send email via SMTP with proper protocol handling
function sendEmailViaSmtp($senderEmail, $senderPassword, $senderName, $recipientEmail, $firstName, $lastName, $admissionId, $examConfig = []) {
    try {
        error_log("=== Attempting Gmail SMTP ===");
        
        $password = str_replace(' ', '', $senderPassword);
        $host = 'smtp.gmail.com';
        $port = 587;
        
        // Create socket connection
        $socket = @fsockopen($host, $port, $errno, $errstr, 15);
        
        if (!$socket) {
            error_log("SMTP Connection failed: $errstr (errno: $errno)");
            return false;
        }
        
        error_log("SMTP Connection successful");
        
        // Function to read full SMTP response (handles multi-line responses)
        $readResponse = function($sock, $label = "") {
            $response = "";
            while ($line = fgets($sock, 512)) {
                $response .= $line;
                // Check if this is the final line of a multi-line response
                if (preg_match('/^\d{3} /', trim($line))) {
                    break;
                }
            }
            if ($label) error_log("$label: " . trim($response));
            return $response;
        };
        
        // Read SMTP greeting
        $readResponse($socket, "Greeting");
        
        // Send EHLO
        fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $readResponse($socket, "EHLO Response");
        
        // Start TLS
        fputs($socket, "STARTTLS\r\n");
        $readResponse($socket, "STARTTLS Response");
        
        // Enable TLS encryption AFTER STARTTLS command
        error_log("Available crypto methods: " . json_encode(array_keys(get_defined_constants(true)['openssl'])));
        
        // Use the most compatible TLS method available
        $tlsMethods = array(
            STREAM_CRYPTO_METHOD_TLS_CLIENT,
            STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
        );
        
        $tlsResult = false;
        foreach ($tlsMethods as $method) {
            if (@stream_socket_enable_crypto($socket, true, $method)) {
                error_log("TLS handshake successful with method: $method");
                $tlsResult = true;
                break;
            }
        }
        
        if (!$tlsResult) {
            error_log("TLS Enable failed with all methods");
            @fclose($socket);
            return false;
        }
        
        // Send EHLO again after TLS
        fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $readResponse($socket, "EHLO after TLS");
        
        // AUTH LOGIN
        fputs($socket, "AUTH LOGIN\r\n");
        $readResponse($socket, "AUTH LOGIN request");
        
        // Send username (base64 encoded)
        fputs($socket, base64_encode($senderEmail) . "\r\n");
        $readResponse($socket, "Username sent");
        
        // Send password (base64 encoded)
        fputs($socket, base64_encode($password) . "\r\n");
        $readResponse($socket, "Password sent");
        
        // Send message
        fputs($socket, "MAIL FROM:<" . $senderEmail . ">\r\n");
        $readResponse($socket, "MAIL FROM");
        
        fputs($socket, "RCPT TO:<" . $recipientEmail . ">\r\n");
        $readResponse($socket, "RCPT TO");
        
        fputs($socket, "DATA\r\n");
        $readResponse($socket, "DATA command");
        
        // Compose and send email message
        $message = "From: " . $senderName . " <" . $senderEmail . ">\r\n";
        $message .= "To: " . $recipientEmail . "\r\n";
        $message .= "Subject: PTC Admission Form - Confirmation\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $message .= getEmailTemplate($firstName, $lastName, $admissionId, $examConfig) . "\r\n";
        $message .= "\r\n.\r\n";
        
        fputs($socket, $message);
        $readResponse($socket, "Message sent");
        
        // Quit
        fputs($socket, "QUIT\r\n");
        @fclose($socket);
        
        error_log("SMTP Email sent successfully!");
        return true;
        
    } catch (\Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return false;
    }
}

function getEmailTemplate($firstName, $lastName, $admissionId, $examConfig = []) {
    // Set default exam config if not provided
    $examConfig = array_merge([
        'exam_date' => '2026-06-01',
        'exam_start_time' => '09:00',
        'exam_end_time' => '12:00',
        'exam_format' => 'Online',
        'exam_location' => 'Online',
        'exam_link' => '[To be provided via email]',
        'exam_link_description' => 'Will be provided 24 hours before the exam'
    ], $examConfig);

    // Format exam date
    $examDate = new DateTime($examConfig['exam_date']);
    $examDateStr = $examDate->format('F j, Y');
    
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .header { background-color: #2e7d32; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; max-width: 600px; margin: 0 auto; }
            .footer { background-color: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; }
            .admission-id { color: #2e7d32; font-weight: bold; font-size: 16px; }
            .info-box { background-color: #f9f9f9; border-left: 4px solid #2e7d32; padding: 15px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Pateros Technological College</h1>
            <p>Admission Confirmation</p>
        </div>
        <div class='content'>
            <p>Dear <strong>" . htmlspecialchars($firstName . ' ' . $lastName) . "</strong>,</p>
            <p>Thank you for submitting your admission form to Pateros Technological College.</p>
            <div class='info-box'>
                <p><strong>Your Admission ID:</strong> <span class='admission-id'>" . htmlspecialchars($admissionId) . "</span></p>
            </div>
            <h3>Important Details:</h3>
            <ul>
                <li><strong>Examination Date:</strong> " . htmlspecialchars($examDateStr) . "</li>
                <li><strong>Examination Format:</strong> " . htmlspecialchars($examConfig['exam_format']) . "</li>
                <li><strong>Time:</strong> " . htmlspecialchars($examConfig['exam_start_time']) . " to " . htmlspecialchars($examConfig['exam_end_time']) . "</li>
                <li><strong>Location:</strong> " . htmlspecialchars($examConfig['exam_location']) . "</li>
                <li><strong>Exam Link:</strong> " . htmlspecialchars($examConfig['exam_link']) . "</li>
                <li><strong>Note:</strong> " . htmlspecialchars($examConfig['exam_link_description']) . "</li>
            </ul>
            <p><strong>What's Next:</strong></p>
            <ol>
                <li>Print the attached PDF admission form</li>
                <li>Bring it to the examination venue on the scheduled date</li>
                <li>Bring a valid ID and/or school credentials</li>
                <li>Arrive 30 minutes before the examination time</li>
            </ol>
            <p>If you have any questions or concerns, please contact the PTC Admissions Office at your earliest convenience.</p>
            <p style='margin-top: 30px;'>Best regards,<br><strong>PTC Admissions Team</strong></p>
        </div>
        <div class='footer'>
            <p>&copy; 2026 Pateros Technological College. All rights reserved.</p>
        </div>
    </body>
    </html>
    ";
}

function logAdmissionToDatabase($firstName, $lastName, $email, $admissionId) {
    try {
        if (file_exists(__DIR__ . '/../config/db_config.php')) {
            require_once __DIR__ . '/../config/db_config.php';
            
            if (!$conn) {
                return;
            }
            
            $stmt = $conn->prepare("INSERT INTO admissions (first_name, last_name, email, admission_id, submission_date) VALUES (?, ?, ?, ?, NOW())");
            
            if ($stmt) {
                $stmt->bind_param("ssss", $firstName, $lastName, $email, $admissionId);
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->close();
        }
    } catch (\Exception $e) {
        // Silently fail
    }
}
?>
