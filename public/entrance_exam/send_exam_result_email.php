<?php
/**
 * Send Exam Result Email to Student
 * 
 * This file handles sending email notifications to students after exam submission
 * It can be called from submit_exam.php or other exam-related scripts
 */

// Load email configuration from db_config
require_once __DIR__ . '/../../config/db_config.php';

function sendExamResultEmail($fullname, $email, $admission_id, $score, $totalQuestions, $percentage, $result) {
    global $emailConfig;
    
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return array('success' => false, 'message' => 'Invalid email address');
    }
    
    $to = $email;
    $subject = "PTC Entrance Exam Result - " . ($result === 'Passed' ? 'Congratulations!' : 'Results');
    
    // Prepare email content based on result
    $result_message = $result === 'Passed' 
        ? "Congratulations! You have <strong>PASSED</strong> the entrance examination."
        : "Thank you for taking the entrance examination. Unfortunately, you have not passed on this attempt. Please contact the admissions office for further information.";
    
    $email_body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .header { background-color: #2e7d32; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; max-width: 600px; margin: 0 auto; }
            .result-box { background-color: " . ($result === 'Passed' ? '#d4edda' : '#f8d7da') . "; border: 2px solid " . ($result === 'Passed' ? '#28a745' : '#dc3545') . "; padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center; }
            .result-text { font-size: 20px; font-weight: bold; color: " . ($result === 'Passed' ? '#155724' : '#721c24') . "; }
            .details { background-color: #f9f9f9; border-left: 4px solid #2e7d32; padding: 15px; margin: 15px 0; }
            .details ul { margin: 10px 0; padding-left: 20px; list-style: none; }
            .details li { margin: 8px 0; }
            .next-steps { background-color: #f9f9f9; border-left: 4px solid #2e7d32; padding: 15px; margin: 15px 0; }
            .footer { background-color: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>PTC Entrance Exam Results</h1>
            <p>Your Official Score Report</p>
        </div>
        <div class='content'>
            <p>Dear <strong>" . htmlspecialchars($fullname) . "</strong>,</p>
            <p>Thank you for taking the PTC Entrance Examination. Your results are now available.</p>
            
            <div class='result-box'>
                <p class='result-text'>" . ($result === 'Passed' ? '✓ PASSED' : '✗ DID NOT PASS') . "</p>
                <p>" . $result_message . "</p>
            </div>
            
            <div class='details'>
                <h3>📋 Your Exam Results:</h3>
                <ul>
                    <li><strong>Admission ID:</strong> " . htmlspecialchars($admission_id) . "</li>
                    <li><strong>Score Achieved:</strong> " . $score . " out of " . $totalQuestions . "</li>
                    <li><strong>Percentage:</strong> " . $percentage . "%</li>
                    <li><strong>Passing Score Required:</strong> 75%</li>
                </ul>
            </div>
            
            <div class='next-steps'>
                <h3>📧 What's Next?</h3>
                " . ($result === 'Passed' 
                    ? "<p>Congratulations on your passing score! The admissions office will contact you shortly with information about program enrollment and next steps.</p>"
                    : "<p>If you wish to retake the examination, please contact the admissions office to schedule another attempt. We encourage you to prepare thoroughly for your next examination.</p>") . "
                <p><strong>Questions?</strong> Contact the PTC Admissions Office:</p>
                <ul style='margin: 10px 0; padding-left: 20px;'>
                    <li>Email: admissions@ptc.edu.ph</li>
                    <li>Phone: (02) 8614-4944</li>
                </ul>
            </div>
            
            <p style='margin-top: 30px;'>Best regards,<br><strong>PTC Admissions Team</strong></p>
        </div>
        <div class='footer'>
            <p>&copy; 2026 Pateros Technological College. All rights reserved.</p>
            <p>This is an automated email. Please do not reply to this address.</p>
        </div>
    </body>
    </html>
    ";
    
    // Set email headers for HTML content
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    
    // Try to use server's mail configuration
    // Note: You may need to update this email address to match your server's configuration
    // Set email headers with configuration
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: " . $emailConfig['sender_name'] . " <" . $emailConfig['from_address'] . ">\r\n";
    $headers .= "Reply-To: " . $emailConfig['sender_email'] . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    // Attempt to send the email
    try {
        // Use mail() function - may require proper server configuration
        $mail_sent = @mail($to, $subject, $email_body, $headers);
        
        if ($mail_sent) {
            return array(
                'success' => true, 
                'message' => 'Email sent successfully'
            );
        } else {
            // If mail() fails, try SMTP fallback with Gmail
            error_log("mail() failed for $to, attempting SMTP fallback");
            return sendExamResultEmailViaSMTP($fullname, $email, $admission_id, $score, $totalQuestions, $percentage, $result);
        }
    } catch (Exception $e) {
        return array(
            'success' => false, 
            'message' => 'Error sending email: ' . $e->getMessage(),
            'exam_recorded' => true
        );
    }
}

/**
 * Send exam result email via SMTP (Gmail fallback)
 */
function sendExamResultEmailViaSMTP($fullname, $email, $admission_id, $score, $totalQuestions, $percentage, $result) {
    global $emailConfig;
    
    try {
        $senderEmail = $emailConfig['sender_email'];
        $senderPassword = $emailConfig['sender_password'];
        $senderName = $emailConfig['sender_name'];
        
        $password = str_replace(' ', '', $senderPassword); // Remove spaces from password
        $host = 'smtp.gmail.com';
        $port = 587;
        
        // Connect to SMTP server with timeout
        $socket = @fsockopen($host, $port, $errno, $errstr, 5);
        if (!$socket) {
            error_log("SMTP Connection Error ($errno): $errstr");
            return array(
                'success' => false, 
                'message' => 'Email delivery temporarily unavailable.',
                'note' => 'Your exam has been recorded. Results will be emailed when service is restored.'
            );
        }
        
        error_log("SMTP: Connected to $host:$port");
        
        // Set socket to non-blocking for better control
        stream_set_blocking($socket, false);
        stream_set_timeout($socket, 5);
        
        $readResponse = function($sock, $label = '') {
            $response = '';
            $start = time();
            while (true) {
                $line = @fgets($sock, 512);
                if ($line === false) {
                    if (time() - $start > 5) break;
                    usleep(100000);
                    continue;
                }
                $response .= $line;
                if (preg_match('/^\d{3} /', trim($line))) break;
            }
            if ($label && trim($response)) error_log("SMTP $label: " . trim(substr($response, 0, 100)));
            return $response;
        };
        
        // Read greeting
        stream_set_blocking($socket, true);
        $greeting = $readResponse($socket, 'Greeting');
        if (empty($greeting)) {
            error_log("SMTP: No greeting received");
            fclose($socket);
            throw new Exception('No SMTP greeting');
        }
        
        // EHLO
        $wrote = fputs($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'ptc.edu.ph') . "\r\n");
        if ($wrote === false) {
            error_log("SMTP: Failed to send EHLO");
            fclose($socket);
            throw new Exception('EHLO failed');
        }
        $readResponse($socket, 'EHLO');
        
        // STARTTLS
        fputs($socket, "STARTTLS\r\n");
        @$readResponse($socket, 'STARTTLS');
        
        // Enable TLS - try multiple methods
        $tlsMethods = [STREAM_CRYPTO_METHOD_TLS_CLIENT];
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $tlsMethods[] = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        }
        
        $tlsEnabled = false;
        foreach ($tlsMethods as $method) {
            if (@stream_socket_enable_crypto($socket, true, $method)) {
                $tlsEnabled = true;
                error_log("SMTP: TLS enabled");
                break;
            }
        }
        
        if (!$tlsEnabled) {
            error_log("SMTP: TLS negotiation failed");
            @fclose($socket);
            throw new Exception('TLS negotiation failed');
        }
        
        // Post-TLS EHLO
        fputs($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'ptc.edu.ph') . "\r\n");
        @$readResponse($socket, 'EHLO-2');
        
        // AUTH LOGIN
        fputs($socket, "AUTH LOGIN\r\n");
        @$readResponse($socket, 'AUTH');
        
        fputs($socket, base64_encode($senderEmail) . "\r\n");
        @$readResponse($socket, 'Username');
        
        fputs($socket, base64_encode($password) . "\r\n");
        $authResp = @$readResponse($socket, 'Password');
        
        if (strpos($authResp, '235') === false && strpos($authResp, '2.7.0') === false) {
            error_log("SMTP: Authentication failed");
            @fclose($socket);
            throw new Exception('Authentication failed');
        }
        
        // Mail From
        fputs($socket, "MAIL FROM:<" . $senderEmail . ">\r\n");
        @$readResponse($socket, 'MAIL FROM');
        
        // Rcpt To
        fputs($socket, "RCPT TO:<" . $email . ">\r\n");
        @$readResponse($socket, 'RCPT TO');
        
        // Data
        fputs($socket, "DATA\r\n");
        @$readResponse($socket, 'DATA');
        
        // Build message
        $subject = "PTC Entrance Exam Result - " . ($result === 'Passed' ? 'Congratulations!' : 'Results');
        $html_body = getExamResultEmailBody($fullname, $email, $admission_id, $score, $totalQuestions, $percentage, $result);
        
        $message = "From: " . $senderName . " <" . $senderEmail . ">\r\n";
        $message .= "To: " . $email . "\r\n";
        $message .= "Subject: " . $subject . "\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $message .= $html_body . "\r\n\r\n.\r\n";
        
        fputs($socket, $message);
        @$readResponse($socket, 'Send');
        
        fputs($socket, "QUIT\r\n");
        @fclose($socket);
        
        error_log("Exam result email sent via SMTP to: $email");
        return array(
            'success' => true, 
            'message' => 'Email sent successfully via SMTP'
        );
        
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return array(
            'success' => false, 
            'message' => 'Email could not be sent at this time.',
            'note' => 'Your exam has been recorded. Contact support if you do not receive your results.'
        );
    }
}

/**
 * Generate HTML email body for exam result
 */
function getExamResultEmailBody($fullname, $email, $admission_id, $score, $totalQuestions, $percentage, $result) {
    $result_message = $result === 'Passed' 
        ? "Congratulations! You have <strong>PASSED</strong> the entrance examination."
        : "Thank you for taking the entrance examination. Unfortunately, you have not passed on this attempt. Please contact the admissions office for further information.";
    
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .header { background-color: #2e7d32; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; max-width: 600px; margin: 0 auto; }
            .result-box { background-color: " . ($result === 'Passed' ? '#d4edda' : '#f8d7da') . "; border: 2px solid " . ($result === 'Passed' ? '#28a745' : '#dc3545') . "; padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center; }
            .result-text { font-size: 20px; font-weight: bold; color: " . ($result === 'Passed' ? '#155724' : '#721c24') . "; }
            .details { background-color: #f9f9f9; border-left: 4px solid #2e7d32; padding: 15px; margin: 15px 0; }
            .details ul { margin: 10px 0; padding-left: 20px; list-style: none; }
            .details li { margin: 8px 0; }
            .next-steps { background-color: #f9f9f9; border-left: 4px solid #2e7d32; padding: 15px; margin: 15px 0; }
            .footer { background-color: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>PTC Entrance Exam Results</h1>
            <p>Your Official Score Report</p>
        </div>
        <div class='content'>
            <p>Dear <strong>" . htmlspecialchars($fullname) . "</strong>,</p>
            <p>Thank you for taking the PTC Entrance Examination. Your results are now available.</p>
            
            <div class='result-box'>
                <p class='result-text'>" . ($result === 'Passed' ? '✓ PASSED' : '✗ DID NOT PASS') . "</p>
                <p>" . $result_message . "</p>
            </div>
            
            <div class='details'>
                <h3>📋 Your Exam Results:</h3>
                <ul>
                    <li><strong>Admission ID:</strong> " . htmlspecialchars($admission_id) . "</li>
                    <li><strong>Score Achieved:</strong> " . $score . " out of " . $totalQuestions . "</li>
                    <li><strong>Percentage:</strong> " . $percentage . "%</li>
                    <li><strong>Passing Score Required:</strong> 75%</li>
                </ul>
            </div>
            
            <div class='next-steps'>
                <h3>📧 What's Next?</h3>
                " . ($result === 'Passed' 
                    ? "<p>Congratulations on your passing score! The admissions office will contact you shortly with information about program enrollment and next steps.</p>"
                    : "<p>If you wish to retake the examination, please contact the admissions office to schedule another attempt. We encourage you to prepare thoroughly for your next examination.</p>") . "
                <p><strong>Questions?</strong> Contact the PTC Admissions Office:</p>
                <ul style='margin: 10px 0; padding-left: 20px;'>
                    <li>Email: admissions@ptc.edu.ph</li>
                    <li>Phone: (02) 8614-4944</li>
                </ul>
            </div>
            
            <p style='margin-top: 30px;'>Best regards,<br><strong>PTC Admissions Team</strong></p>
        </div>
        <div class='footer'>
            <p>&copy; 2026 Pateros Technological College. All rights reserved.</p>
            <p>This is an automated email. Please do not reply to this address.</p>
        </div>
    </body>
    </html>
    ";
}
?>
