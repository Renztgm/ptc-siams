<?php
/**
 * Email Scheduler API - Run scheduled emails check
 * Can be called from frontend or task scheduler
 */

header('Content-Type: application/json');

// Suppress warning about exit codes
error_reporting(E_ALL & ~E_WARNING);

require_once __DIR__ . '/send_exam_link_bulk.php';
require_once __DIR__ . '/../config/db_config.php';

$response = [
    'success' => false,
    'message' => '',
    'sent' => 0,
    'failed' => 0,
    'processed_batches' => 0
];

try {
    // Set timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    
    // Email configuration
    $senderEmail = 'arquero.sofia.tcu@gmail.com';
    $senderPassword = 'qjpf wvol cpgq tsoa';
    $senderName = 'PTC Admissions';
    
    // Load exam configuration
    $examConfig = [];
    if (file_exists(__DIR__ . '/exam_config.php')) {
        $examConfig = include __DIR__ . '/exam_config.php';
    }
    
    // Check for scheduled emails
    $scheduledDir = __DIR__ . '/../storage/scheduled_emails';
    
    if (!is_dir($scheduledDir)) {
        $response['message'] = 'No scheduled emails directory found';
        $response['success'] = true;
        echo json_encode($response);
        exit;
    }
    
    $files = glob($scheduledDir . '/batch_*.json');
    $currentTime = time();
    $sentCount = 0;
    $failedCount = 0;
    $processedBatches = 0;
    
    if (empty($files)) {
        $response['message'] = 'No scheduled email batches found';
        $response['success'] = true;
        echo json_encode($response);
        exit;
    }
    
    foreach ($files as $scheduleFile) {
        $scheduleData = json_decode(file_get_contents($scheduleFile), true);
        
        if (!$scheduleData) {
            continue;
        }
        
        // Skip if not pending
        if ($scheduleData['status'] !== 'pending') {
            continue;
        }
        
        // Check if scheduled time has passed
        if ($scheduleData['scheduled_timestamp'] > $currentTime) {
            continue; // Not ready yet
        }
        
        // Time to send this batch!
        $processedBatches++;
        
        try {
            if (!$conn) {
                throw new Exception("Database connection failed");
            }
            
            foreach ($scheduleData['emails'] as $email) {
                $email = filter_var($email, FILTER_VALIDATE_EMAIL);
                if (!$email) {
                    $failedCount++;
                    continue;
                }
                
                // Get student details
                $stmt = $conn->prepare("SELECT given_name, last_name, admission_id FROM admissions WHERE email = ?");
                if (!$stmt) {
                    $failedCount++;
                    continue;
                }
                
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
                    
                    // Update database
                    $logStmt = $conn->prepare("UPDATE admissions SET email_sent_date = NOW(), exam_link_sent = 1 WHERE email = ?");
                    if ($logStmt) {
                        $logStmt->bind_param("s", $email);
                        $logStmt->execute();
                        $logStmt->close();
                    }
                } else {
                    $failedCount++;
                }
                
                usleep(500000); // 0.5 second delay
            }
            
            // Mark batch as sent
            $scheduleData['status'] = 'sent';
            $scheduleData['sent_at'] = date('Y-m-d H:i:s');
            file_put_contents($scheduleFile, json_encode($scheduleData, JSON_PRETTY_PRINT));
            
        } catch (Exception $e) {
            $failedCount += count($scheduleData['emails'] ?? []);
        }
    }
    
    $response['success'] = true;
    $response['sent'] = $sentCount;
    $response['failed'] = $failedCount;
    $response['processed_batches'] = $processedBatches;
    
    if ($processedBatches > 0) {
        $response['message'] = "Sent $sentCount emails from $processedBatches batch(es)";
    } else {
        $response['message'] = 'No batches ready to send yet';
    }
    
    if ($conn) {
        $conn->close();
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
