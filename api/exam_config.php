<?php
// Exam Configuration File
// This file stores the exam details that can be modified

$examConfig = [
    'exam_date' => '2026-06-01',
    'exam_start_time' => '09:00',
    'exam_end_time' => '12:00',
    'exam_format' => 'Online',
    'exam_location' => 'Online',
    'exam_link' => '[To be provided via email]',
    'exam_link_description' => 'Will be provided 24 hours before the exam'
];

// If this is a POST request, save the updated configuration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    // Simple password protection (change this to your own password)
    $adminPassword = 'ptc_admin_2026'; // Change this!
    
    if (isset($_POST['password']) && $_POST['password'] === $adminPassword) {
        $examConfig['exam_date'] = $_POST['exam_date'] ?? $examConfig['exam_date'];
        $examConfig['exam_start_time'] = $_POST['exam_start_time'] ?? $examConfig['exam_start_time'];
        $examConfig['exam_end_time'] = $_POST['exam_end_time'] ?? $examConfig['exam_end_time'];
        $examConfig['exam_format'] = $_POST['exam_format'] ?? $examConfig['exam_format'];
        $examConfig['exam_location'] = $_POST['exam_location'] ?? $examConfig['exam_location'];
        $examConfig['exam_link'] = $_POST['exam_link'] ?? $examConfig['exam_link'];
        $examConfig['exam_link_description'] = $_POST['exam_link_description'] ?? $examConfig['exam_link_description'];
        
        // Save to config file
        file_put_contents(__DIR__ . '/exam_config.json', json_encode($examConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        echo json_encode(['success' => true, 'message' => 'Exam configuration updated successfully']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
        exit;
    }
}

// If requesting JSON, return the config
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    
    // Try to load from file first
    if (file_exists(__DIR__ . '/exam_config.json')) {
        echo file_get_contents(__DIR__ . '/exam_config.json');
    } else {
        echo json_encode($examConfig);
    }
    exit;
}

// Load from file if it exists
if (file_exists(__DIR__ . '/exam_config.json')) {
    $examConfig = json_decode(file_get_contents(__DIR__ . '/exam_config.json'), true);
}

// Return config for use in other files
return $examConfig;
?>
