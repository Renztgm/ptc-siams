<?php
/**
 * Exam Management Backend
 * Handles exam sessions, registrations, attendance, and results
 */

header('Content-Type: application/json');

// Database configuration
require_once __DIR__ . '/../config/db_config.php';

try {
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    
    if ($_GET['action'] === 'get_sessions') {
        // Get all exam sessions
        $query = "SELECT es.id, es.session_name, es.exam_date, es.exam_start_time, 
                         es.exam_end_time, es.exam_format, es.capacity, es.status,
                         COUNT(er.id) as registered_count
                  FROM exam_sessions es
                  LEFT JOIN exam_registrations er ON es.id = er.exam_session_id
                  GROUP BY es.id
                  ORDER BY es.exam_date DESC";
        
        $result = $conn->query($query);
        
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
            exit;
        }
        
        $sessions = [];
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
        
        echo json_encode(['success' => true, 'sessions' => $sessions]);
        exit;
    }
    
    if ($_GET['action'] === 'get_registrations') {
        // Get registrations for a specific exam
        $exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
        
        if (!$exam_id) {
            echo json_encode(['success' => false, 'message' => 'Exam ID required']);
            exit;
        }
        
        $query = "SELECT er.id, a.admission_id, CONCAT(a.given_name, ' ', a.last_name) as student_name,
                         er.status, er.attendance_status, er.score, er.result
                  FROM exam_registrations er
                  JOIN admissions a ON er.admission_id = a.id
                  WHERE er.exam_session_id = $exam_id
                  ORDER BY a.last_name, a.given_name";
        
        $result = $conn->query($query);
        
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Query failed']);
            exit;
        }
        
        $registrations = [];
        while ($row = $result->fetch_assoc()) {
            $registrations[] = $row;
        }
        
        echo json_encode(['success' => true, 'registrations' => $registrations]);
        exit;
    }
    
    if ($_GET['action'] === 'get_results') {
        // Get results for a specific exam
        $exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
        
        if (!$exam_id) {
            echo json_encode(['success' => false, 'message' => 'Exam ID required']);
            exit;
        }
        
        $query = "SELECT er.id, CONCAT(a.given_name, ' ', a.last_name) as student_name,
                         er.attendance_status, er.score, er.result, er.result_date
                  FROM exam_registrations er
                  JOIN admissions a ON er.admission_id = a.id
                  WHERE er.exam_session_id = $exam_id
                  ORDER BY a.last_name, a.given_name";
        
        $result = $conn->query($query);
        $results = [];
        
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
        
        echo json_encode(['success' => true, 'results' => $results]);
        exit;
    }
    
    if ($_GET['action'] === 'get_stats') {
        // Get overall statistics
        
        // Total admitted
        $total_admitted = $conn->query("SELECT COUNT(*) as count FROM admissions WHERE status = 'admitted'")->fetch_assoc()['count'];
        
        // Registered for exam
        $registered = $conn->query("SELECT COUNT(DISTINCT admission_id) as count FROM exam_registrations WHERE status = 'registered'")->fetch_assoc()['count'];
        
        // Exam completed
        $completed = $conn->query("SELECT COUNT(*) as count FROM exam_registrations WHERE status = 'completed'")->fetch_assoc()['count'];
        
        // Passed
        $passed = $conn->query("SELECT COUNT(*) as count FROM exam_registrations WHERE result = 'passed'")->fetch_assoc()['count'];
        
        // Program stats
        $program_query = "SELECT p.program_name,
                                COUNT(DISTINCT a.id) as applications,
                                COUNT(DISTINCT er.id) as registered,
                                SUM(CASE WHEN er.status = 'completed' THEN 1 ELSE 0 END) as completed,
                                SUM(CASE WHEN er.result = 'passed' THEN 1 ELSE 0 END) as passed,
                                ROUND(100 * SUM(CASE WHEN er.result = 'passed' THEN 1 ELSE 0 END) / 
                                      NULLIF(COUNT(DISTINCT er.id), 0), 2) as pass_rate
                         FROM programs p
                         LEFT JOIN admissions a ON p.program_name = a.program
                         LEFT JOIN exam_registrations er ON a.id = er.admission_id
                         GROUP BY p.id, p.program_name";
        
        $program_result = $conn->query($program_query);
        $program_stats = [];
        
        while ($row = $program_result->fetch_assoc()) {
            $program_stats[] = [
                'program' => $row['program_name'],
                'applications' => intval($row['applications']),
                'registered' => intval($row['registered']),
                'completed' => intval($row['completed']),
                'passed' => intval($row['passed']),
                'pass_rate' => floatval($row['pass_rate']) ?: 0
            ];
        }
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_admitted' => intval($total_admitted),
                'registered_for_exam' => intval($registered),
                'exam_completed' => intval($completed),
                'passed' => intval($passed)
            ],
            'program_stats' => $program_stats
        ]);
        exit;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Security check for actions that modify data
    $adminPassword = isset($_POST['password']) ? $_POST['password'] : '';
    if ($adminPassword !== 'ptc_admin_2026') {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
        exit;
    }
    
    if ($_POST['action'] === 'create_session') {
        // Create a new exam session
        $session_name = isset($_POST['session_name']) ? $_POST['session_name'] : '';
        $exam_date = isset($_POST['exam_date']) ? $_POST['exam_date'] : '';
        $exam_start_time = isset($_POST['exam_start_time']) ? $_POST['exam_start_time'] : '';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 180;
        $capacity = isset($_POST['capacity']) ? intval($_POST['capacity']) : 100;
        
        if (empty($session_name) || empty($exam_date) || empty($exam_start_time)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }
        
        // Calculate end time
        $startTime = new DateTime($exam_date . ' ' . $exam_start_time);
        $startTime->add(new DateInterval('PT' . $duration . 'M'));
        $exam_end_time = $startTime->format('H:i:s');
        
        $stmt = $conn->prepare("INSERT INTO exam_sessions (session_name, exam_date, exam_start_time, exam_end_time, capacity)
                                VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $session_name, $exam_date, $exam_start_time, $exam_end_time, $capacity);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Exam session created']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create exam session']);
        }
        $stmt->close();
        exit;
    }
    
    if ($_POST['action'] === 'save_results') {
        // Save exam results for a student
        $registration_id = isset($_POST['registration_id']) ? intval($_POST['registration_id']) : 0;
        $attendance_status = isset($_POST['attendance_status']) ? $_POST['attendance_status'] : 'absent';
        $score = isset($_POST['score']) ? floatval($_POST['score']) : null;
        $passing_score = isset($_POST['passing_score']) ? floatval($_POST['passing_score']) : 60;
        $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
        
        if (!$registration_id) {
            echo json_encode(['success' => false, 'message' => 'Registration ID required']);
            exit;
        }
        
        // Determine pass/fail
        $result = null;
        $status = 'completed';
        
        if ($attendance_status === 'absent') {
            $result = 'failed';
        } elseif ($score !== null) {
            $result = ($score >= $passing_score) ? 'passed' : 'failed';
        }
        
        $stmt = $conn->prepare("UPDATE exam_registrations 
                               SET attendance_status = ?, 
                                   score = ?,
                                   score_percentage = ?,
                                   passing_score = ?,
                                   result = ?,
                                   remarks = ?,
                                   result_date = NOW(),
                                   status = ?
                               WHERE id = ?");
        
        $stmt->bind_param("sdddssi", $attendance_status, $score, $score, $passing_score, $result, $remarks, $status, $registration_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Results saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save results']);
        }
        $stmt->close();
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
$conn->close();
?>
