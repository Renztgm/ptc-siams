<?php
/**
 * Email Scheduler - Check and Send Scheduled Emails
 * This script checks for emails scheduled to be sent and sends them automatically
 * 
 * Usage: Run this script periodically via cron job, Windows Task Scheduler, or manually
 * Example: php scheduler_check_emails.php
 */

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

// Load required functions
require_once __DIR__ . '/send_exam_link_bulk.php';

// Set up error logging
error_log("=== Email Scheduler Started at " . date('Y-m-d H:i:s') . " ===");

// Check for scheduled emails
$scheduledDir = __DIR__ . '/../storage/scheduled_emails';

if (!is_dir($scheduledDir)) {
    error_log("Scheduler: No scheduled_emails directory found");
    exit(0);
}

$files = glob($scheduledDir . '/batch_*.json');
$currentTime = time();
$sentCount = 0;
$failedCount = 0;

error_log("Scheduler: Found " . count($files) . " scheduled email batches");

foreach ($files as $scheduleFile) {
    $scheduleData = json_decode(file_get_contents($scheduleFile), true);
    
    // Check if this batch should be sent
    if ($scheduleData['status'] !== 'pending') {
        error_log("Scheduler: Skipping batch {$scheduleData['batch_id']} (status: {$scheduleData['status']})");
        continue;
    }
    
    // Check if scheduled time has passed
    if ($scheduleData['scheduled_timestamp'] > $currentTime) {
        error_log("Scheduler: Batch {$scheduleData['batch_id']} not ready yet (scheduled for {$scheduleData['scheduled_time']})");
        continue;
    }
    
    // Time to send this batch!
    error_log("Scheduler: Processing batch {$scheduleData['batch_id']} - " . count($scheduleData['emails']) . " emails");
    
    try {
        if (file_exists(__DIR__ . '/../config/db_config.php')) {
            require_once __DIR__ . '/../config/db_config.php';
            if (!$conn) {
                throw new Exception("Database connection failed: " . mysqli_connect_error());
            }
            
            foreach ($scheduleData['emails'] as $email) {
                $email = filter_var($email, FILTER_VALIDATE_EMAIL);
                if (!$email) {
                    error_log("Scheduler: Invalid email address in batch {$scheduleData['batch_id']}");
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
                    error_log("Scheduler: Student not found for email: $email");
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
                    error_log("Scheduler: Email sent to {$email}");
                    
                    // Log the email send
                    $logStmt = $conn->prepare("UPDATE admissions SET email_sent_date = NOW(), exam_link_sent = 1 WHERE email = ?");
                    $logStmt->bind_param("s", $email);
                    $logStmt->execute();
                    $logStmt->close();
                } else {
                    $failedCount++;
                    error_log("Scheduler: Failed to send email to {$email}");
                }
                
                // Small delay to avoid rate limiting
                usleep(500000); // 0.5 second delay
            }
            
            $conn->close();
            
            // Mark batch as sent
            $scheduleData['status'] = 'sent';
            $scheduleData['sent_at'] = date('Y-m-d H:i:s');
            
            file_put_contents($scheduleFile, json_encode($scheduleData, JSON_PRETTY_PRINT));
            error_log("Scheduler: Batch {$scheduleData['batch_id']} marked as sent");
            
        } else {
            throw new Exception("Database configuration not found");
        }
    } catch (Exception $e) {
        error_log("Scheduler: Error processing batch {$scheduleData['batch_id']}: " . $e->getMessage());
        $failedCount += count($scheduleData['emails']);
    }
}

error_log("=== Email Scheduler Completed - Sent: $sentCount, Failed: $failedCount ===");

// Return status for Task Scheduler
if ($sentCount > 0 || $failedCount > 0) {
    exit(0); // Success
} else {
    exit(0); // No emails to send is also success
}
?>
