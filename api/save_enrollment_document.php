<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

require_once '../config/db_config.php';

// Define upload directory
$upload_dir = '../storage/enrollment_documents/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // UPLOAD DOCUMENT
    if ($action === 'upload' && isset($_FILES['document_file'])) {
        $enrollment_id = intval($_POST['enrollment_id'] ?? 0);
        $document_id = intval($_POST['document_id'] ?? 0);
        $file = $_FILES['document_file'];
        
        // Validate inputs
        if ($enrollment_id <= 0 || $document_id <= 0) {
            $response['message'] = 'Invalid enrollment or document ID';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = 'File upload error: ' . $file['error'];
        } elseif ($file['size'] > 5242880) { // 5MB limit
            $response['message'] = 'File size exceeds 5MB limit';
        } else {
            // Validate file type (only images and PDFs)
            $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!in_array($file['type'], $allowed_types)) {
                $response['message'] = 'Only JPG, PNG, and PDF files are allowed';
            } else {
                // Check if enrollment exists
                $enroll_check = $conn->query("SELECT id FROM enrollment_documents WHERE id = $enrollment_id");
                if ($enroll_check->num_rows === 0) {
                    $response['message'] = 'Enrollment record not found';
                } else {
                    // Check if document requirement exists
                    $doc_check = $conn->query("SELECT id FROM required_documents WHERE id = $document_id");
                    if ($doc_check->num_rows === 0) {
                        $response['message'] = 'Document requirement not found';
                    } else {
                        // Generate unique filename
                        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $timestamp = date('YmdHis');
                        $new_filename = "{$enrollment_id}_{$document_id}_{$timestamp}.{$file_ext}";
                        $file_path = "{$upload_dir}{$new_filename}";
                        
                        // Move uploaded file
                        if (move_uploaded_file($file['tmp_name'], $file_path)) {
                            // Check if submission already exists
                            $check_submission = $conn->query(
                                "SELECT id FROM document_submissions WHERE enrollment_id = $enrollment_id AND document_id = $document_id"
                            );
                            
                            if ($check_submission->num_rows > 0) {
                                // Update existing submission
                                $result = $conn->query(
                                    "UPDATE document_submissions 
                                    SET file_path = 'enrollment_documents/$new_filename', 
                                        file_name = '{$file['name']}',
                                        upload_date = NOW(),
                                        submission_status = 'Received'
                                    WHERE enrollment_id = $enrollment_id AND document_id = $document_id"
                                );
                            } else {
                                // Create new submission record
                                $result = $conn->query(
                                    "INSERT INTO document_submissions 
                                    (enrollment_id, document_id, file_path, file_name, submission_status)
                                    VALUES ($enrollment_id, $document_id, 'enrollment_documents/$new_filename', '{$file['name']}', 'Received')"
                                );
                            }
                            
                            if ($result) {
                                $response['success'] = true;
                                $response['message'] = 'Document uploaded successfully';
                                $response['file_path'] = "storage/enrollment_documents/{$new_filename}";
                            } else {
                                $response['message'] = 'Database error: ' . $conn->error;
                                unlink($file_path); // Delete uploaded file on error
                            }
                        } else {
                            $response['message'] = 'Failed to move uploaded file';
                        }
                    }
                }
            }
        }
    }
    
    // UPDATE DOCUMENT STATUS
    elseif ($action === 'update_status') {
        $submission_id = intval($_POST['submission_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $admin_notes = $_POST['admin_notes'] ?? '';
        $verified_by = $_SESSION['admin_username'] ?? 'Unknown Admin';
        
        // Validate status
        if (!in_array($status, ['Pending', 'Received', 'Verified', 'Rejected'])) {
            $response['message'] = 'Invalid status';
        } elseif ($submission_id <= 0) {
            $response['message'] = 'Invalid submission ID';
        } else {
            $admin_notes = $conn->real_escape_string($admin_notes);
            $verified_by = $conn->real_escape_string($verified_by);
            
            $sql = "UPDATE document_submissions 
                   SET submission_status = '$status', 
                       admin_notes = '$admin_notes',
                       verified_by = '$verified_by',
                       verified_date = NOW()
                   WHERE id = $submission_id";
            
            if ($conn->query($sql)) {
                $response['success'] = true;
                $response['message'] = 'Document status updated successfully';
            } else {
                $response['message'] = 'Database error: ' . $conn->error;
            }
        }
    }
    
    // GET ENROLLMENT SUMMARY
    elseif ($action === 'get_summary') {
        $enrollment_id = intval($_POST['enrollment_id'] ?? 0);
        
        if ($enrollment_id <= 0) {
            $response['message'] = 'Invalid enrollment ID';
        } else {
            $summary = $conn->query(
                "SELECT 
                    COUNT(*) as total_docs,
                    SUM(CASE WHEN submission_status != 'Pending' THEN 1 ELSE 0 END) as received_docs,
                    SUM(CASE WHEN submission_status = 'Verified' THEN 1 ELSE 0 END) as verified_docs,
                    SUM(CASE WHEN submission_status = 'Rejected' THEN 1 ELSE 0 END) as rejected_docs
                FROM document_submissions 
                WHERE enrollment_id = $enrollment_id"
            );
            
            if ($summary && $summary->num_rows > 0) {
                $data = $summary->fetch_assoc();
                $response['success'] = true;
                $response['summary'] = $data;
            } else {
                $response['message'] = 'Enrollment not found';
            }
        }
    }
    
    // GET STUDENT DOCUMENTS
    elseif ($action === 'get_documents') {
        $enrollment_id = intval($_POST['enrollment_id'] ?? 0);
        
        if ($enrollment_id <= 0) {
            $response['message'] = 'Invalid enrollment ID';
        } else {
            $docs = $conn->query(
                "SELECT ds.id, ds.document_id, rd.document_name, ds.submission_status, 
                        ds.file_path, ds.admin_notes, ds.upload_date, ds.verified_date
                FROM document_submissions ds
                JOIN required_documents rd ON ds.document_id = rd.id
                WHERE ds.enrollment_id = $enrollment_id
                ORDER BY rd.display_order"
            );
            
            if ($docs && $docs->num_rows > 0) {
                $response['success'] = true;
                $response['documents'] = $docs->fetch_all(MYSQLI_ASSOC);
            } else {
                $response['success'] = true;
                $response['documents'] = [];
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
?>
