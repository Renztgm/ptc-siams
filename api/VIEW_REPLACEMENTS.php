<?php
/**
 * VIEW REPLACEMENTS FOR INFINITYFREE
 * 
 * InfinityFree database users don't have CREATE VIEW permission.
 * Use these functions in your PHP code instead of database views.
 */

// Include database config
require_once __DIR__ . '/../config/db_config.php';

// ============================================
// REPLACEMENT 1: Get Latest Exam Session
// ============================================
function getLatestExamSession() {
    global $servername, $username, $password, $dbname;
    
    try {
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed");
        }
        
        $query = "SELECT * FROM exam_sessions 
                  WHERE exam_date >= CURDATE()
                  ORDER BY exam_date ASC
                  LIMIT 1";
        
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        $session = $result->fetch_assoc();
        $conn->close();
        
        return $session;
    } catch (Exception $e) {
        error_log("Error getting latest exam session: " . $e->getMessage());
        return null;
    }
}

// ============================================
// REPLACEMENT 2: Get Exam Takers Summary
// ============================================
function getExamTakersSummary() {
    global $servername, $username, $password, $dbname;
    
    try {
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed");
        }
        
        $query = "SELECT 
                    es.id,
                    es.session_name,
                    es.exam_date,
                    COUNT(er.id) as total_registered,
                    SUM(CASE WHEN er.attended = 1 THEN 1 ELSE 0 END) as attended,
                    SUM(CASE WHEN er.pass_fail = 'pass' THEN 1 ELSE 0 END) as passed,
                    SUM(CASE WHEN er.pass_fail = 'fail' THEN 1 ELSE 0 END) as failed
                  FROM exam_sessions es
                  LEFT JOIN exam_registrations er ON es.id = er.exam_id
                  GROUP BY es.id, es.session_name, es.exam_date
                  ORDER BY es.exam_date DESC";
        
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        $summaries = [];
        while ($row = $result->fetch_assoc()) {
            $summaries[] = $row;
        }
        
        $conn->close();
        
        return $summaries;
    } catch (Exception $e) {
        error_log("Error getting exam takers summary: " . $e->getMessage());
        return [];
    }
}

// ============================================
// REPLACEMENT 3: Get Program Statistics
// ============================================
function getProgramStats() {
    global $servername, $username, $password, $dbname;
    
    try {
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed");
        }
        
        $query = "SELECT 
                    p.id,
                    p.program_name,
                    COUNT(a.id) as total_applications,
                    SUM(CASE WHEN a.status = 'admitted' THEN 1 ELSE 0 END) as admitted,
                    SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    COUNT(DISTINCT er.id) as registered_for_exam
                  FROM programs p
                  LEFT JOIN admissions a ON p.program_name = a.program
                  LEFT JOIN exam_registrations er ON a.id = er.admission_id
                  GROUP BY p.id, p.program_name
                  ORDER BY p.program_name ASC";
        
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        $conn->close();
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error getting program stats: " . $e->getMessage());
        return [];
    }
}

// ============================================
// USAGE EXAMPLES
// ============================================

/*
// Example 1: Get the next exam session
$nextExam = getLatestExamSession();
if ($nextExam) {
    echo "Next exam: " . $nextExam['session_name'] . " on " . $nextExam['exam_date'];
} else {
    echo "No upcoming exams";
}

// Example 2: Get exam statistics
$summaries = getExamTakersSummary();
foreach ($summaries as $summary) {
    echo $summary['session_name'] . ": " . $summary['total_registered'] . " registered, " 
         . $summary['attended'] . " attended, " . $summary['passed'] . " passed";
}

// Example 3: Get program statistics
$stats = getProgramStats();
foreach ($stats as $stat) {
    echo $stat['program_name'] . ": " . $stat['total_applications'] . " applications, " 
         . $stat['admitted'] . " admitted";
}
*/

?>
