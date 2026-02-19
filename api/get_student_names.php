<?php
// Get student names by email addresses

header('Content-Type: application/json');

require_once '../config/db_config.php';

// Get emails from query parameter
$emails = isset($_GET['emails']) ? $_GET['emails'] : '';

if (empty($emails)) {
    echo json_encode([
        'success' => true,
        'students' => []
    ]);
    exit;
}

try {
    // Parse emails (comma-separated)
    $emailArray = array_map('trim', explode(',', $emails));
    
    // Create placeholders for parameterized query
    $placeholders = implode(',', array_fill(0, count($emailArray), '?'));
    
    // Query to get student names
    $query = "SELECT email, CONCAT(given_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as full_name 
              FROM admissions 
              WHERE email IN ($placeholders)
              ORDER BY email";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $conn->error
        ]);
        exit;
    }
    
    // Bind parameters
    $stmt->bind_param(str_repeat('s', count($emailArray)), ...$emailArray);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $students = [];
        
        while ($row = $result->fetch_assoc()) {
            $students[] = [
                'email' => $row['email'],
                'full_name' => trim($row['full_name'])
            ];
        }
        
        echo json_encode([
            'success' => true,
            'students' => $students
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
