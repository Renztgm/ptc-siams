<?php
// Get programs from database

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_config.php';

try {
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Get all programs
    $query = "SELECT program_name FROM programs ORDER BY program_name ASC";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row;
    }
    
    $conn->close();
    
    // If no programs in database, return empty array
    echo json_encode([
        'success' => true,
        'programs' => $programs
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'programs' => []
    ]);
}
?>
