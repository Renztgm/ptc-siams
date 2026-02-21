<?php
// 1. Database Configuration
$host = "sql302.infinityfree.com";
$db_user = "if0_41171248";
$db_pass = "vJoJA8PL88TC";
$db_name = "if0_41171248_ptc_database";

// 2. Create Connection
$conn = new mysqli($host, $db_user, $db_pass, $db_name);


// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to fetch all exam results
$sql = "SELECT admission_id, fullname, email, score, total_questions, status, date_submitted 
        FROM exam_results 
        ORDER BY date_submitted DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aptitude Test- Results List</title>
    <style>
        /* Consistent with your entrance_exam_sample.html styling */
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 0; }
        
        .page-header { 
            background-color: #1b5e20; 
            color: white; 
            padding: 20px; 
            display: flex; 
            align-items: center; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }

        .container { 
            max-width: 1100px; 
            margin: 30px auto; 
            background: #ffffff; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
        }

        h2 { color: #1b5e20; margin-bottom: 20px; border-bottom: 3px solid #1b5e20; padding-bottom: 10px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        
        th, td { 
            padding: 12px 15px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
        }

        th { background-color: #f8f9fa; color: #333; font-weight: bold; }

        tr:hover { background-color: #f1f8e9; }

        /* Status Styling */
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .passed { background-color: #e8f5e9; color: #2e7d32; }
        .failed { background-color: #ffebee; color: #c62828; }

        .score-text { font-weight: bold; }
    </style>
</head>
<body>

<div class="page-header">
    <h1>Entrance Exam Portal</h1>
</div>

<div class="container">
    <h2>Examination Results List</h2>
    
    <table>
        <thead>
            <tr>
                <th>Admission ID</th>
                <th>Full Name</th>
                <th>Score</th>
                <th>Percentage</th>
                <th>Status</th>
                <th>Date Submitted</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // Calculate percentage
                    $percentage = ($row['score'] / $row['total_questions']) * 100;
                    
                    // Determine CSS class based on status
                    $statusClass = (strtolower($row['status']) == 'passed') ? 'passed' : 'failed';
                    
                    echo "<tr>";
                    echo "<td>" . ($row['admission_id'] ?? '<em style="color:#999">No ID</em>') . "</td>";
                    echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
                    echo "<td class='score-text'>" . $row['score'] . " / " . $row['total_questions'] . "</td>";
                    echo "<td>" . round($percentage, 2) . "%</td>";
                    echo "<td><span class='status-badge $statusClass'>" . $row['status'] . "</span></td>";
                    echo "<td>" . date("M d, Y h:i A", strtotime($row['date_submitted'])) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center;'>No examination results found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php
$conn->close();
?>