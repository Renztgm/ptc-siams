<?php
// Get all admissions from database (pending, admitted, rejected, etc.)

header('Content-Type: application/json');

// Direct database connection
$host     = "sql302.infinityfree.com";  
$db_user  = "if0_41171248";
$db_pass  = "vJoJA8PL88TC";
$db_name  = "if0_41171248_ptc_database";

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}
    
    // Get filter parameters
    $program = isset($_GET['program']) ? trim($_GET['program']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    
    // Build query - get ALL admissions
    $query = "SELECT id, admission_id, given_name, last_name, middle_name, full_name,
                     email, contact_number, address, program, status,
                     exam_link_sent, email_sent_date, submission_date, admission_date
              FROM admissions
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Add status filter if specified
    if (!empty($status)) {
        $query .= " AND status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    // Add program filter if specified
    if (!empty($program)) {
        $query .= " AND program = ?";
        $params[] = $program;
        $types .= 's';
    }
    
    $query .= " ORDER BY submission_date DESC, last_name ASC";
    
    // Prepare and execute
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => "Prepare failed: " . $conn->error]);
        $conn->close();
        exit;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => "Execute failed: " . $stmt->error]);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $result = $stmt->get_result();
    $admissions = [];
    
    while ($row = $result->fetch_assoc()) {
        $admissions[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'total' => count($admissions),
        'data' => $admissions
    ]);
?>
