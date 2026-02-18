<?php
/**
 * Test Scheduled Email Functionality
 * Helps diagnose issues with email scheduling
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_config.php';

$response = [
    'success' => false,
    'message' => '',
    'diagnostics' => []
];

try {
    // Set timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    // 1. Check scheduled emails directory
    $scheduledDir = __DIR__ . '/../storage/scheduled_emails';
    $response['diagnostics']['scheduled_dir_exists'] = is_dir($scheduledDir);
    
    if (!is_dir($scheduledDir)) {
        if (@mkdir($scheduledDir, 0755, true)) {
            $response['diagnostics']['scheduled_dir_created'] = true;
        } else {
            $response['diagnostics']['scheduled_dir_created'] = false;
        }
    }
    
    // 2. Check for scheduled email files
    $files = glob($scheduledDir . '/batch_*.json');
    $response['diagnostics']['scheduled_files_count'] = count($files);
    $response['diagnostics']['scheduled_files'] = [];
    
    $currentTime = time();
    
    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);
        $timeRemaining = $data['scheduled_timestamp'] - $currentTime;
        
        $response['diagnostics']['scheduled_files'][] = [
            'file' => basename($file),
            'batch_id' => $data['batch_id'],
            'status' => $data['status'],
            'scheduled_for' => $data['scheduled_time'],
            'time_remaining_seconds' => $timeRemaining,
            'current_time' => date('Y-m-d H:i:s'),
            'should_send' => $timeRemaining <= 0 && $data['status'] === 'pending',
            'email_count' => count($data['emails'] ?? [])
        ];
    }
    
    // 3. Test database connection
    if ($conn) {
        $response['diagnostics']['database_connected'] = true;
        
        // Check admissions table
        $result = $conn->query("SELECT COUNT(*) as count FROM admissions WHERE status IN ('pending', 'admitted')");
        if ($result) {
            $row = $result->fetch_assoc();
            $response['diagnostics']['total_students'] = $row['count'];
        }
    } else {
        $response['diagnostics']['database_connected'] = false;
        $response['diagnostics']['db_error'] = $conn ? '' : mysqli_connect_error();
    }
    
    // 4. Test email configuration
    $response['diagnostics']['email_config'] = [
        'sender_email_set' => !empty('arquero.sofia.tcu@gmail.com'),
        'sender_password_set' => !empty('qjpf wvol cpgq tsoa')
    ];
    
    $response['success'] = true;
    $response['message'] = 'Diagnostic check completed';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
