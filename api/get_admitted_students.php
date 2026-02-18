<?php
// Get admitted students from database for admin panels

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_config.php';

try {
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Get filter parameters
    $program = isset($_GET['program']) ? trim($_GET['program']) : '';
    
    // Build query - get both pending and admitted students
    $query = "SELECT id, admission_id, given_name, last_name, middle_name,
                     CONCAT(given_name, ' ', last_name) as full_name,
                     email, program, exam_link_sent, email_sent_date
              FROM admissions
              WHERE status IN ('pending', 'admitted')";
    
    $params = [];
    $types = '';
    
    // Add program filter if specified
    if (!empty($program)) {
        $query .= " AND program = ?";
        $params[] = $program;
        $types .= 's';
    }
    
    $query .= " ORDER BY last_name, given_name ASC";
    
    // Prepare and execute
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $students = [];
    
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'count' => count($students)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'students' => []
    ]);
}
?>
