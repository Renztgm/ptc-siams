<?php
// Get email sending history from database

header('Content-Type: application/json');

// Database connection
require_once __DIR__ . '/../config/db_config.php';

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get email history - students who have received exam links
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

// Query students who have received exam emails (where exam_link_sent is not null/empty)
$query = "SELECT 
    id, 
    given_name, 
    middle_name,
    last_name, 
    CONCAT(given_name, ' ', IFNULL(middle_name, ''), ' ', last_name) as full_name,
    email, 
    program, 
    admission_id,
    exam_link_sent,
    submission_date
FROM admissions 
WHERE exam_link_sent IS NOT NULL AND exam_link_sent != '' AND exam_link_sent != '0'
ORDER BY submission_date DESC 
LIMIT " . $limit;

$result = $conn->query($query);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
    exit;
}

$emails = [];
while ($row = $result->fetch_assoc()) {
    // Clean up full name (remove extra spaces)
    $row['full_name'] = trim(preg_replace('/\s+/', ' ', $row['full_name']));
    
    // Prepare timestamp - use exam_link_sent if it's a valid date, otherwise use submission_date
    $timestamp = $row['exam_link_sent'];
    if ($row['exam_link_sent'] === '1' || $row['exam_link_sent'] === 1) {
        $timestamp = $row['submission_date'];
    }
    
    $emails[] = [
        'id' => $row['id'],
        'student_name' => $row['full_name'],
        'full_name' => $row['full_name'],
        'recipient_email' => $row['email'],
        'email' => $row['email'],
        'program' => $row['program'],
        'admission_id' => $row['admission_id'],
        'sent_at' => $timestamp,
        'created_at' => $row['submission_date'],
        'status' => 'sent',
        'batch_id' => 'EXAM_LINK_' . strtoupper(str_replace('-', '_', substr($row['email'], 0, 10)))
    ];
}

echo json_encode([
    'success' => true,
    'emails' => $emails,
    'count' => count($emails),
    'message' => 'Email history loaded from database'
]);

$conn->close();
?>
