<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../admin.php");
    exit;
}

require_once '../../config/db_config.php';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document_file'])) {
    // Define upload directory
    $upload_dir = '../../storage/enrollment_documents/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file = $_FILES['document_file'];
    $enrollment_id = intval($_POST['enrollment_id'] ?? 0);
    $document_id = intval($_POST['document_id'] ?? 0);
    
    $response = ['success' => false, 'message' => ''];

    // Validate inputs
    if ($enrollment_id <= 0 || $document_id <= 0) {
        $response['message'] = 'Invalid enrollment or document ID';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'File upload error: ' . $file['error'];
    } elseif ($file['size'] > 5242880) { // 5MB limit
        $response['message'] = 'File size exceeds 5MB limit';
    } else {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($file['type'], $allowed_types)) {
            $response['message'] = 'Only JPG, PNG, and PDF files are allowed';
        } else {
            // Check if enrollment exists
            $enroll_check = $conn->query("SELECT id FROM enrollment_documents WHERE id = $enrollment_id");
            if ($enroll_check->num_rows === 0) {
                $response['message'] = 'Enrollment record not found';
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
                        $response['file_path'] = "../../storage/enrollment_documents/{$new_filename}";
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
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get recently admitted students (last 7 days - with "Admitted" status)
$students_query = "SELECT DISTINCT a.admission_id, a.full_name, a.email, a.contact_number, a.program 
                   FROM admissions a 
                   WHERE DATE(a.submission_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                   AND a.status = 'Admitted'
                   ORDER BY a.submission_date DESC";

$students_result = $conn->query($students_query);
$students = $students_result ? $students_result->fetch_all(MYSQLI_ASSOC) : [];

// Get enrollment documents if student is selected
$selected_student = '';
$enrollment_data = [];
$document_checklist = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'fetch_student' && isset($_POST['student_id'])) {
        $student_id = $conn->real_escape_string($_POST['student_id']);
        $selected_student = $student_id;
        $student_type = $_POST['student_type'] ?? 'New';
        $student_type = in_array($student_type, ['New', 'Transferee']) ? $student_type : 'New';
        
        // Get student enrollment data
        $enrollment_query = "SELECT * FROM enrollment_documents WHERE student_id = '$student_id' ORDER BY id DESC LIMIT 1";
        $enrollment_result = $conn->query($enrollment_query);
        
        if ($enrollment_result && $enrollment_result->num_rows > 0) {
            $enrollment_data = $enrollment_result->fetch_assoc();
            
            // Update student type if it was changed
            if ($enrollment_data['student_type'] !== $student_type) {
                $conn->query("UPDATE enrollment_documents SET student_type = '$student_type' WHERE id = '{$enrollment_data['id']}'");
                $enrollment_data['student_type'] = $student_type;
            }
            
            // Get document checklist
            $checklist_query = "SELECT rd.id, rd.document_name, rd.document_description, rd.student_type,
                               ds.id as submission_id, ds.submission_status, ds.file_path, ds.upload_date, ds.admin_notes
                               FROM required_documents rd
                               LEFT JOIN document_submissions ds ON rd.id = ds.document_id AND ds.enrollment_id = '{$enrollment_data['id']}'
                               WHERE rd.student_type = '{$enrollment_data['student_type']}' OR rd.student_type = 'Both'
                               ORDER BY rd.display_order";
            
            $checklist_result = $conn->query($checklist_query);
            if ($checklist_result) {
                $document_checklist = $checklist_result->fetch_all(MYSQLI_ASSOC);
            }
        } else {
            // Student not in enrollment system, get from admissions
            $admin_student_query = "SELECT * FROM admissions WHERE admission_id = '$student_id'";
            $admin_result = $conn->query($admin_student_query);
            
            if ($admin_result && $admin_result->num_rows > 0) {
                $student = $admin_result->fetch_assoc();
                // Create enrollment record
                $insert_query = "INSERT INTO enrollment_documents (student_id, student_name, email, contact_number, program, student_type) 
                                VALUES ('$student_id', '{$student['full_name']}', '{$student['email']}', '{$student['contact_number']}', '{$student['program']}', '$student_type')";
                
                if ($conn->query($insert_query)) {
                    $enrollment_data['id'] = $conn->insert_id;
                    $enrollment_data['student_id'] = $student_id;
                    $enrollment_data['student_name'] = $student['full_name'];
                    $enrollment_data['student_type'] = $student_type;
                    $enrollment_data['email'] = $student['email'];
                    $enrollment_data['contact_number'] = $student['contact_number'];
                    $enrollment_data['program'] = $student['program'];
                    $enrollment_data['enrollment_date'] = date('Y-m-d H:i:s');
                    
                    // Get document checklist
                    $checklist_query = "SELECT rd.id, rd.document_name, rd.document_description, rd.student_type
                                       FROM required_documents rd
                                       WHERE rd.student_type = '$student_type' OR rd.student_type = 'Both'
                                       ORDER BY rd.display_order";
                    
                    $checklist_result = $conn->query($checklist_query);
                    if ($checklist_result) {
                        $document_checklist = $checklist_result->fetch_all(MYSQLI_ASSOC);
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Enrollment Management - PTC</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        
        .header {
            display: flex;
            align-items: center;
            padding: 20px 30px;
            background-color: #1b5e20;
            color: white;
            gap: 15px;
        }
        
        .header img {
            height: 60px;
        }
        
        .header h1 {
            flex: 1;
            font-size: 24px;
        }
        
        .header .logout {
            background-color: #d32f2f;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-weight: bold;
        }
        
        .container {
            display: grid;
            grid-template-columns: 300px 1fr;
            min-height: calc(100vh - 100px);
        }
        
        .sidebar {
            background-color: #fff;
            padding: 20px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }
        
        .sidebar h3 {
            color: #2e7d32;
            margin-bottom: 15px;
            border-bottom: 2px solid #2e7d32;
            padding-bottom: 10px;
        }
        
        .student-list {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .student-item {
            padding: 10px;
            margin-bottom: 8px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .student-item:hover {
            background-color: #e8f5e9;
            border-color: #2e7d32;
        }
        
        .student-item.selected {
            background-color: #2e7d32;
            color: white;
            border-color: #1b5e20;
        }
        
        .student-name {
            font-weight: bold;
            font-size: 14px;
        }
        
        .student-id {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }
        
        .student-item.selected .student-id {
            color: #e8f5e9;
        }
        
        .content {
            padding: 30px;
            background-color: #fff;
            overflow-y: auto;
        }
        
        .no-selection {
            text-align: center;
            color: #999;
            padding: 40px;
        }
        
        .student-info {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #2e7d32;
        }
        
        .student-info h2 {
            color: #2e7d32;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .info-item {
            font-size: 14px;
        }
        
        .info-label {
            color: #666;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .info-value {
            color: #333;
            font-size: 15px;
            margin-top: 3px;
        }
        
        .student-type-selector {
            margin: 20px 0;
        }
        
        .student-type-selector label {
            display: inline-block;
            margin-right: 20px;
        }
        
        .student-type-selector input[type="radio"] {
            margin-right: 5px;
        }
        
        .documents-section h3 {
            color: #2e7d32;
            margin: 30px 0 20px 0;
            border-bottom: 2px solid #2e7d32;
            padding-bottom: 10px;
        }
        
        .document-checklist {
            display: grid;
            gap: 15px;
        }
        
        .document-item {
            display: grid;
            grid-template-columns: 30px 1fr 150px 150px;
            gap: 15px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            align-items: center;
        }
        
        .document-item.received {
            background-color: #e8f5e9;
            border-color: #2e7d32;
        }
        
        .document-item.rejected {
            background-color: #ffebee;
            border-color: #d32f2f;
        }
        
        .document-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .document-info h4 {
            color: #2e7d32;
            margin-bottom: 5px;
        }
        
        .document-info p {
            font-size: 13px;
            color: #666;
        }
        
        .document-status {
            text-align: center;
        }
        
        .document-status .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge.received {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge.verified {
            background-color: #cfe2ff;
            color: #084298;
        }
        
        .badge.rejected {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .upload-area {
            padding: 15px;
            background-color: #e3f2fd;
            border: 2px dashed #2e7d32;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .upload-area:hover {
            background-color: #c8e6c9;
        }
        
        .upload-area input[type="file"] {
            display: none;
        }
        
        .upload-area p {
            color: #2e7d32;
            font-size: 13px;
            margin: 0;
        }
        
        .admin-notes {
            padding: 8px;
            font-size: 12px;
            color: #555;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 3px;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: #2e7d32;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #1b5e20;
        }
        
        .btn-danger {
            background-color: #d32f2f;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #b71c1c;
        }
        
        .btn-secondary {
            background-color: #757575;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #616161;
        }
        
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                border-right: none;
                border-bottom: 1px solid #ddd;
                max-height: 200px;
            }
            
            .document-item {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <img src="../assets/Logo.png" alt="PTC Logo">
        <h1>Student Enrollment Document Management</h1>
        <a href="../enrollement/enrollment_dashboard.php" class="btn btn-secondary">Back to Admin Panel</a>
        <a href="../admin.php?logout=true" class="logout">Logout</a>
    </div>
    
    <!-- Main Container -->
    <div class="container">
        <!-- Sidebar - Student List -->
        <div class="sidebar">
            <h3>📋 Students</h3>
            <div class="student-list">
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): ?>
                        <div class="student-item <?php echo $student['admission_id'] === $selected_student ? 'selected' : ''; ?>" 
                             onclick="selectStudent('<?php echo htmlspecialchars($student['admission_id']); ?>', '<?php echo htmlspecialchars($student['full_name']); ?>')">
                            <div class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                            <div class="student-id">ID: <?php echo htmlspecialchars($student['admission_id']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #999;">No students found</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content">
            <?php if (empty($selected_student)): ?>
                <div class="no-selection">
                    <h3>👈 Select a student from the list to manage their enrollment documents</h3>
                </div>
            <?php else: ?>
                <!-- Student Information -->
                <div class="student-info">
                    <h2>📚 Enrollment Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Student ID</div>
                            <div class="info-value"><?php echo htmlspecialchars($enrollment_data['student_id'] ?? $selected_student); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($enrollment_data['student_name'] ?? ''); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($enrollment_data['email'] ?? ''); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Contact Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($enrollment_data['contact_number'] ?? ''); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Program</div>
                            <div class="info-value"><?php echo htmlspecialchars($enrollment_data['program'] ?? ''); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Enrollment Date</div>
                            <div class="info-value"><?php echo isset($enrollment_data['enrollment_date']) ? date('M d, Y', strtotime($enrollment_data['enrollment_date'])) : 'N/A'; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Student Type Selection -->
                <div class="student-type-selector">
                    <h3>👤 Student Type</h3>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="fetch_student">
                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($selected_student); ?>">
                        <label>
                            <input type="radio" name="student_type" value="New" 
                                <?php echo ($enrollment_data['student_type'] ?? '') === 'New' ? 'checked' : ''; ?> required>
                            <strong>New Student</strong>
                        </label>
                        <label>
                            <input type="radio" name="student_type" value="Transferee" 
                                <?php echo ($enrollment_data['student_type'] ?? '') === 'Transferee' ? 'checked' : ''; ?> required>
                            <strong>Transferee Student</strong>
                        </label>
                        <button type="submit" class="btn btn-primary" style="margin-left: 20px;">Update Type</button>
                    </form>
                </div>
                
                <!-- Documents Section -->
                <?php if (!empty($document_checklist)): ?>
                    <div class="documents-section">
                        <h3>📄 Required Documents</h3>
                        <div class="document-checklist">
                            <?php foreach ($document_checklist as $doc): ?>
                                <div class="document-item <?php echo isset($doc['submission_status']) ? strtolower($doc['submission_status']) : ''; ?>">
                                    <div>
                                        <input type="checkbox" class="document-checkbox" 
                                               <?php echo isset($doc['submission_status']) && $doc['submission_status'] !== 'Pending' ? 'checked' : ''; ?> 
                                               disabled>
                                    </div>
                                    <div class="document-info">
                                        <h4><?php echo htmlspecialchars($doc['document_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($doc['document_description']); ?></p>
                                    </div>
                                    <div class="document-status">
                                        <span class="badge <?php echo isset($doc['submission_status']) ? strtolower($doc['submission_status']) : 'pending'; ?>">
                                            <?php echo $doc['submission_status'] ?? 'Pending'; ?>
                                        </span>
                                        <?php if (isset($doc['upload_date'])): ?>
                                            <p style="font-size: 11px; margin-top: 5px; color: #666;">
                                                <?php echo date('M d, Y', strtotime($doc['upload_date'])); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if (isset($doc['admin_notes']) && !empty($doc['admin_notes'])): ?>
                                            <div class="admin-notes" title="<?php echo htmlspecialchars($doc['admin_notes']); ?>">
                                                <?php echo htmlspecialchars($doc['admin_notes']); ?>
                                            </div>
                                        <?php else: ?>
                                            <button class="btn btn-secondary" type="button"
                                                    onclick="showUploadForm(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['document_name']); ?>', '<?php echo $enrollment_data['id']; ?>')">
                                                Upload Scan
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="color: #999; margin-top: 30px;">Select a student type above to see required documents</p>
                <?php endif; ?>
                
                <!-- Summary -->
                <?php if (!empty($document_checklist)): ?>
                    <div style="margin-top: 30px; padding: 15px; background-color: #f0f0f0; border-radius: 5px;">
                        <h3>📊 Enrollment Status</h3>
                        <?php
                        $total = count($document_checklist);
                        $received = count(array_filter($document_checklist, function($d) {
                            return isset($d['submission_status']) && $d['submission_status'] !== 'Pending';
                        }));
                        $percentage = $total > 0 ? round(($received / $total) * 100) : 0;
                        ?>
                        <p><strong>Documents Received:</strong> <?php echo $received; ?> / <?php echo $total; ?> (<?php echo $percentage; ?>%)</p>
                        <div style="background-color: #fff; border: 1px solid #ddd; border-radius: 3px; overflow: hidden; height: 20px;">
                            <div style="background-color: #2e7d32; height: 100%; width: <?php echo $percentage; ?>%; transition: width 0.3s;"></div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal for Document Upload -->
    <div id="uploadModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background-color: white; margin: 50px auto; padding: 30px; border-radius: 8px; max-width: 500px;">
            <h3 style="color: #2e7d32; margin-bottom: 20px;">Upload Document</h3>
            <div id="uploadContent" style="max-height: 400px; overflow-y: auto;"></div>
            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="closeUploadModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentDocData = {};
        
        function selectStudent(studentId, studentName) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="fetch_student">
                <input type="hidden" name="student_id" value="${studentId}">
                <input type="hidden" name="student_type" value="New">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function showUploadForm(docId, docName, enrollmentId) {
            currentDocData = {
                docId: docId,
                docName: docName,
                enrollmentId: enrollmentId
            };
            
            const uploadContent = document.getElementById('uploadContent');
            uploadContent.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <p style="color: #666; margin-bottom: 10px;">Uploading: <strong>${docName}</strong></p>
                    <div style="background-color: #f9f9f9; padding: 20px; border: 2px dashed #2e7d32; border-radius: 5px; text-align: center; cursor: pointer;" 
                         id="dropZone" onclick="document.getElementById('fileInput').click()">
                        <p style="color: #2e7d32; margin: 0;">📎 Click to select file or drag and drop</p>
                        <p style="color: #999; font-size: 12px; margin: 5px 0 0 0;">Supported: JPG, PNG, PDF (Max 5MB)</p>
                    </div>
                    <input type="file" id="fileInput" style="display: none;" accept=".jpg,.jpeg,.png,.pdf" onchange="handleFileSelect(event)">
                </div>
                <div id="filePreview"></div>
                <div id="uploadStatus"></div>
            `;
            
            document.getElementById('uploadModal').style.display = 'block';
            
            // Setup drag and drop
            const dropZone = document.getElementById('dropZone');
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.style.backgroundColor = '#e8f5e9';
            });
            dropZone.addEventListener('dragleave', () => {
                dropZone.style.backgroundColor = '#f9f9f9';
            });
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.style.backgroundColor = '#f9f9f9';
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    document.getElementById('fileInput').files = files;
                    handleFileSelect({target: {files: files}});
                }
            });
        }
        
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Validate file size
            if (file.size > 5242880) {
                alert('File size exceeds 5MB limit');
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                alert('Only JPG, PNG, and PDF files are allowed');
                return;
            }
            
            const filePreview = document.getElementById('filePreview');
            filePreview.innerHTML = `
                <div style="padding: 15px; background-color: #e8f5e9; border-radius: 5px; margin-bottom: 15px;">
                    <p><strong>✅ File Selected:</strong> ${file.name}</p>
                    <p style="color: #666; font-size: 12px; margin: 5px 0 0 0;">${(file.size / 1024).toFixed(2)} KB</p>
                </div>
                <button type="button" onclick="uploadFile(event)" class="btn btn-primary" style="width: 100%;">
                    Upload Document
                </button>
            `;
        }
        
        function uploadFile(event) {
            const file = document.getElementById('fileInput').files[0];
            if (!file) {
                alert('Please select a file first');
                return;
            }
            
            const uploadStatus = document.getElementById('uploadStatus');
            uploadStatus.innerHTML = '<p style="color: #2e7d32;">⏳ Uploading...</p>';
            
            const formData = new FormData();
            formData.append('document_file', file);
            formData.append('enrollment_id', currentDocData.enrollmentId);
            formData.append('document_id', currentDocData.docId);
            
            // Use current page for upload
            fetch('admin_enrollment.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      uploadStatus.innerHTML = '<p style="color: green; font-weight: bold;">✅ Document uploaded successfully!</p>';
                      setTimeout(() => {
                          closeUploadModal();
                          location.reload();
                      }, 1500);
                  } else {
                      uploadStatus.innerHTML = `<p style="color: red;">${data.message}</p>`;
                  }
              })
              .catch(error => {
                  uploadStatus.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
              });
        }
        
        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
            currentDocData = {};
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('uploadModal');
            if (event.target === modal) {
                closeUploadModal();
            }
        }
    </script>
</body>
</html>
