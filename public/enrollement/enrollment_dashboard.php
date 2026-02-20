<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../admin.php");
    exit;
}

require_once '../../config/db_config.php';

// Get enrollment and document statistics (last 7 days - Admitted students only)
$stats_query = "SELECT 
    COUNT(DISTINCT ed.id) as total_enrollments,
    COUNT(DISTINCT CASE WHEN ds.submission_status = 'Verified' THEN ds.enrollment_id END) as complete_enrollments,
    COUNT(ds.id) as total_submissions,
    COUNT(CASE WHEN ds.submission_status = 'Received' THEN 1 END) as pending_reviews,
    COUNT(CASE WHEN ds.submission_status = 'Verified' THEN 1 END) as verified_submissions
FROM enrollment_documents ed
LEFT JOIN document_submissions ds ON ed.id = ds.enrollment_id
LEFT JOIN admissions a ON ed.student_id = a.admission_id
WHERE DATE(ed.enrollment_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
AND a.status = 'Admitted'";

$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [];

// Get recent submissions
$recent_query = "SELECT 
    ed.student_id, ed.student_name, rd.document_name, 
    ds.submission_status, ds.upload_date
FROM document_submissions ds
JOIN enrollment_documents ed ON ds.enrollment_id = ed.id
JOIN required_documents rd ON ds.document_id = rd.id
ORDER BY ds.upload_date DESC
LIMIT 10";

$recent_result = $conn->query($recent_query);
$recent_submissions = $recent_result ? $recent_result->fetch_all(MYSQLI_ASSOC) : [];

// Get enrollments needing action (last 7 days - Admitted students only)
$action_query = "SELECT 
    ed.id as enrollment_id,
    ed.student_id,
    ed.student_name,
    COUNT(DISTINCT rd.id) as total_required,
    COUNT(DISTINCT CASE WHEN ds.submission_status != 'Pending' THEN ds.id END) as received_count
FROM enrollment_documents ed
JOIN required_documents rd ON (rd.student_type = ed.student_type OR rd.student_type = 'Both')
LEFT JOIN document_submissions ds ON ed.id = ds.enrollment_id AND rd.id = ds.document_id
LEFT JOIN admissions a ON ed.student_id = a.admission_id
WHERE DATE(ed.enrollment_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
AND a.status = 'Admitted'
GROUP BY ed.id, ed.student_id, ed.student_name
HAVING received_count < total_required
ORDER BY received_count ASC
LIMIT 15";

$action_result = $conn->query($action_query);
$action_items = $action_result ? $action_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Enrollment Dashboard - PTC Admin</title>
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
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }
        
        .btn-primary {
            background-color: #2e7d32;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #1b5e20;
        }
        
        .btn-secondary {
            background-color: #757575;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #616161;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #2e7d32;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            color: #2e7d32;
            font-size: 32px;
            font-weight: bold;
        }
        
        .stat-card.secondary {
            border-left-color: #ff9800;
        }
        
        .stat-card.secondary .number {
            color: #ff9800;
        }
        
        .stat-card.warning {
            border-left-color: #f44336;
        }
        
        .stat-card.warning .number {
            color: #f44336;
        }
        
        h2 {
            color: #2e7d32;
            margin: 30px 0 20px 0;
            border-bottom: 2px solid #2e7d32;
            padding-bottom: 10px;
        }
        
        .table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background-color: #f5f5f5;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #ddd;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .badge {
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
        
        .progress-bar {
            width: 100px;
            height: 20px;
            background-color: #f0f0f0;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background-color: #2e7d32;
            transition: width 0.3s;
        }
        
        .action-link {
            color: #2e7d32;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 13px;
            }
            
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <img src="../assets/Logo.png" alt="PTC Logo">
        <h1>Enrollment Document Dashboard</h1>
        <a href="../admin.php?logout=true" class="logout">Logout</a>
    </div>
    
    <!-- Container -->
    <div class="container">
        <!-- Navigation Buttons -->
        <div class="nav-buttons">
            <a href="admin_enrollment.php" class="btn btn-primary">Manage Documents</a>
            <a href="../admin_dashboard.php" class="btn btn-secondary">Back to Admin Panel</a>
        </div>
        
        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Enrollments</h3>
                <div class="number"><?php echo $stats['total_enrollments'] ?? 0; ?></div>
            </div>
            <div class="stat-card secondary">
                <h3>Complete Enrollments</h3>
                <div class="number"><?php echo $stats['complete_enrollments'] ?? 0; ?></div>
            </div>
            <div class="stat-card warning">
                <h3>Pending Reviews</h3>
                <div class="number"><?php echo $stats['pending_reviews'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Verified Documents</h3>
                <div class="number"><?php echo $stats['verified_submissions'] ?? 0; ?></div>
            </div>
        </div>
        
        <!-- Enrollments Needing Action -->
        <h2>📋 Enrollments Needing Action</h2>
        <div class="table-container">
            <?php if (count($action_items) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Progress</th>
                            <th>Documents Received</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($action_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($item['student_name']); ?></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo round(($item['received_count'] / $item['total_required']) * 100); ?>%"></div>
                                    </div>
                                </td>
                                <td><strong><?php echo $item['received_count']; ?> / <?php echo $item['total_required']; ?></strong></td>
                                <td>
                                    <a href="admin_enrollment.php?student_id=<?php echo urlencode($item['student_id']); ?>" class="action-link">
                                        Upload Documents →
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>🎉 All enrollments are complete!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Submissions -->
        <h2>📤 Recent Document Submissions</h2>
        <div class="table-container">
            <?php if (count($recent_submissions) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Document</th>
                            <th>Status</th>
                            <th>Upload Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_submissions as $submission): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($submission['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($submission['document_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo strtolower($submission['submission_status']); ?>">
                                        <?php echo $submission['submission_status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($submission['upload_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No recent submissions</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
