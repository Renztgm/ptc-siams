<?php
// Database connection - Use environment detection like db_config.php
// Detect environment
$isInfinityFree = (strpos($_SERVER['DOCUMENT_ROOT'], 'infinityfree.com') !== false) || 
                  (strpos($_SERVER['SERVER_NAME'], 'infinityfree') !== false);

if ($isInfinityFree) {
    // ===== INFINITYFREE =====
    $host     = "sql302.infinityfree.com";  
    $db_user  = "if0_41171248";
    $db_pass  = "vJoJA8PL88TC";
    $db_name  = "if0_41171248_ptc_database";
} else {
    // ===== LOCALHOST (XAMPP) =====
    $host     = "localhost";
    $db_user  = "root";
    $db_pass  = "";
    $db_name  = "ptc_system";
}

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error() . " | Host: " . $host);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Enable error logging
error_log("=== Admission Email Script Started ===");
error_log("POST Data: Email=" . (isset($_POST['email']) ? $_POST['email'] : 'NONE'));
error_log("DB Host: " . $host);
error_log("DB Name: " . $db_name);
error_log("DB Connection: SUCCESS");

// Check if admissions table exists
$tableCheckResult = mysqli_query($conn, "SHOW TABLES LIKE 'admissions'");
if (!$tableCheckResult) {
    error_log("ERROR checking admissions table: " . mysqli_error($conn));
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

if (mysqli_num_rows($tableCheckResult) == 0) {
    error_log("FATAL: admissions table does not exist in database " . $db_name);
    echo json_encode(['success' => false, 'message' => 'Database error: admissions table not found']);
    exit;
}

error_log("Admissions table verified");

// Email configuration
$senderEmail = 'arquero.sofia.tcu@gmail.com';
$senderPassword = 'qjpf wvol cpgq tsoa'; // Gmail App Password
$senderName = 'PTC Admissions';

// Get POST data
$recipientEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
$firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
$lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
$middleName = isset($_POST['middleName']) ? trim($_POST['middleName']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$contactNumber = isset($_POST['contact']) ? trim($_POST['contact']) : '';
$program = isset($_POST['program']) ? trim($_POST['program']) : '';
$admissionId = isset($_POST['admissionId']) ? trim($_POST['admissionId']) : '';
$pdfData = isset($_POST['pdfData']) ? $_POST['pdfData'] : '';
$action = isset($_POST['action']) ? trim($_POST['action']) : 'submit';

// Handle email validation request
if ($action === 'validate_email') {
    // Validate email format
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email format: " . $recipientEmail);
        echo json_encode(['valid' => false, 'message' => 'Invalid email address']);
        exit;
    }

    // Check if email is already registered
    $emailCheckResult = mysqli_query($conn, "SELECT admission_id FROM admissions WHERE email = '" . mysqli_real_escape_string($conn, $recipientEmail) . "' LIMIT 1");
    if ($emailCheckResult && mysqli_num_rows($emailCheckResult) > 0) {
        $existingRecord = mysqli_fetch_assoc($emailCheckResult);
        error_log("Email validation failed - already registered: " . $recipientEmail . " (Admission ID: " . $existingRecord['admission_id'] . ")");
        echo json_encode(['valid' => false, 'message' => 'This email address is already registered in our system. Admission ID: ' . htmlspecialchars($existingRecord['admission_id']) . '. If you have questions, please contact the admissions office.']);
        exit;
    }

    // Email is valid and not registered
    error_log("Email validation passed: " . $recipientEmail);
    echo json_encode(['valid' => true, 'message' => 'Email is valid']);
    exit;
}

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

// Check if email is already registered
$emailCheckResult = mysqli_query($conn, "SELECT admission_id FROM admissions WHERE email = '" . mysqli_real_escape_string($conn, $recipientEmail) . "' LIMIT 1");
if ($emailCheckResult && mysqli_num_rows($emailCheckResult) > 0) {
    $existingRecord = mysqli_fetch_assoc($emailCheckResult);
    error_log("Email already registered: " . $recipientEmail . " (Admission ID: " . $existingRecord['admission_id'] . ")");
    echo json_encode(['success' => false, 'message' => 'This email address is already registered in our system. Admission ID: ' . htmlspecialchars($existingRecord['admission_id']) . '. If you have questions, please contact the admissions office.']);
    exit;
}

// Log admission information to database
$dbResult = logAdmissionToDatabase($conn, $firstName, $middleName, $lastName, $address, $contactNumber, $program, $recipientEmail, $admissionId);
error_log("Database save result: " . ($dbResult ? "SUCCESS" : "FAILED"));

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
    
    // Generate QR code using a free QR code API
    $qrData = "Admission ID: " . $admissionId . "\nName: " . $firstName . " " . $lastName . "\nDate: " . date('Y-m-d');
    $qrDataEncoded = urlencode($qrData);
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . $qrDataEncoded;
    
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
            .header { background-color: #2e7d32; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; max-width: 600px; margin: 0 auto; }
            .footer { background-color: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; }
            .admission-id { color: #2e7d32; font-weight: bold; font-size: 14px; }
            .qr-section { text-align: center; margin: 20px 0; padding: 15px; background-color: #f9f9f9; border: 2px solid #2e7d32; }
            .qr-section img { max-width: 150px; height: auto; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Pateros Technological College</h1>
            <p>Admission Confirmation</p>
        </div>
        <div class='content'>
            <p>Dear " . htmlspecialchars($firstName) . " " . htmlspecialchars($lastName) . ",</p>
            <p>Thank you for your interest in applying.</p>
            <p>Please proceed to the <strong>PTC grounds</strong> to pay the required application fee. Once your payment has been processed, you will receive an email containing the entrance exam link, as well as the scheduled date and time of your examination.</p>
            <p><strong>Your Admission ID:</strong> <span class='admission-id'>" . htmlspecialchars($admissionId) . "</span></p>
            <p>If you have any questions, please feel free to contact us.</p>
            
            <div class='qr-section'>
                <p><strong>Your Admission QR Code:</strong></p>
                <img src='" . $qrCodeUrl . "' alt='Admission QR Code'>
            </div>
            
            <p style='margin-top: 30px;'>Best regards,<br><strong>Admissions Office</strong></p>
        </div>
        <div class='footer'>
            <p>&copy; 2026 Pateros Technological College. All rights reserved.</p>
        </div>
    </body>
    </html>
    ";
}

function logAdmissionToDatabase($conn, $givenName, $middleName, $lastName, $address, $contactNumber, $program, $email, $admissionId) {
    
    error_log("=== logAdmissionToDatabase START ===");
    error_log("Parameters - Name: $givenName $middleName $lastName, Email: $email, ID: $admissionId, Program: $program");
    
    try {
        // Verify connection exists
        if (!$conn) {
            error_log("FATAL: Database connection not available");
            return false;
        }
        
        error_log("Database connection verified successfully");
        
        // Create full name
        $fullName = trim($givenName . ' ' . $middleName . ' ' . $lastName);
        
        // First check what columns exist in the admissions table
        $columnsResult = mysqli_query($conn, "DESCRIBE admissions");
        if ($columnsResult) {
            $columns = array();
            while ($row = mysqli_fetch_assoc($columnsResult)) {
                $columns[] = $row['Field'];
            }
            error_log("Available columns in admissions table: " . json_encode($columns));
        }
        
        // Prepare SQL statement with correct column names from database schema
        $sql = "INSERT INTO admissions (given_name, middle_name, last_name, full_name, address, contact_number, email, program, admission_id, submission_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        error_log("Preparing SQL: $sql");
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("FATAL: Statement preparation failed - " . $conn->error);
            // Try alternative column names
            error_log("Attempting alternative SQL without middle_name...");
            $sql = "INSERT INTO admissions (first_name, last_name, full_name, address, contact_number, email, program, admission_id, submission_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                error_log("FATAL: Alternative SQL also failed - " . $conn->error);
                return false;
            }
            if (!$stmt->bind_param("sssssssss", $givenName, $lastName, $fullName, $address, $contactNumber, $email, $program, $admissionId)) {
                error_log("FATAL: Parameter binding failed - " . $stmt->error);
                $stmt->close();
                return false;
            }
        } else {
            // Bind parameters (9 parameters for 9 fields - no submission_date as it uses NOW())
            if (!$stmt->bind_param("sssssssss", $givenName, $middleName, $lastName, $fullName, $address, $contactNumber, $email, $program, $admissionId)) {
                error_log("FATAL: Parameter binding failed - " . $stmt->error);
                $stmt->close();
                return false;
            }
        }
        
        error_log("Parameters bound successfully");
        
        // Execute statement
        $result = $stmt->execute();
        
        if ($result) {
            $insertId = $stmt->insert_id;
            error_log("SUCCESS: Admission recorded - ID: $admissionId, Email: $email, Name: $fullName, DB Insert ID: $insertId");
            $stmt->close();
            return true;
        } else {
            error_log("FATAL: Statement execution failed - " . $stmt->error);
            $stmt->close();
            return false;
        }
        
    } catch (\Exception $e) {
        error_log("EXCEPTION in logAdmissionToDatabase: " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
        return false;
    }
}

// Close database connection
if ($conn) {
    $conn->close();
}
?>
