<?php
// 1. Database Configuration
$host = "sql302.infinityfree.com";
$db_user = "if0_41171248";
$db_pass = "vJoJA8PL88TC";
$db_name = "if0_41171248_ptc_database";

// 2. Create Connection
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// 3. Check Connection
if ($conn->connect_error) {
    die("<div style='color:red; padding:20px;'>Connection failed: " . $conn->connect_error . "</div>");
}

// 4. Set Character Set (Crucial for InfinityFree/MariaDB)
$conn->set_charset("utf8mb4");

// 5. Fetch Data from the 'admissions' table
// Verified column names: admission_id, last_name, given_name, middle_name, email, contact_number, program, submission_date
$sql = "SELECT admission_id, last_name, given_name, middle_name, email, contact_number, program, submission_date FROM admissions ORDER BY submission_date DESC";
$result = $conn->query($sql);

// 6. Detailed Error Debugging
if (!$result) {
    die("<div style='color:red; padding:20px;'>
            <strong>SQL Error:</strong> " . $conn->error . "<br>
            <strong>Note:</strong> Ensure the table 'admissions' was successfully imported via your .sql file.
         </div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PTC Admissions Records</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f0f2f0; margin: 0; }
        .header { background-color: #1b5e20; color: white; padding: 20px; text-align: center; }
        .container { max-width: 1200px; margin: 30px auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #2e7d32; color: white; padding: 15px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; color: #333; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f8e9; }
        
        .admission-id { font-family: monospace; font-weight: bold; color: #1b5e20; }
        .search-bar { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .no-data { text-align: center; padding: 40px; color: #666; font-style: italic; }
    </style>
</head>
<body>

<div class="header">
    <h1>Pateros Technological College</h1>
    <p>Student Admissions Database</p>
</div>

<div class="container">
    <h2>Applicant Records</h2>
    
    <input type="text" id="searchInput" class="search-bar" placeholder="Search by name, ID, or program..." onkeyup="filterTable()">

    <table id="recordsTable">
        <thead>
            <tr>
                <th>Admission ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Program</th>
                <th>Date Applied</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td class='admission-id'>" . htmlspecialchars($row['admission_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['last_name'] . ", " . $row['given_name'] . " " . $row['middle_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['contact_number']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['program']) . "</td>";
                    echo "<td>" . date('Y-m-d', strtotime($row['submission_date'])) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='no-data'>No applicants found in the admissions table.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
function filterTable() {
    let input = document.getElementById("searchInput");
    let filter = input.value.toUpperCase();
    let table = document.getElementById("recordsTable");
    let tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) {
        let tds = tr[i].getElementsByTagName("td");
        let flag = false;
        for(let j = 0; j < tds.length; j++){
            if (tds[j] && tds[j].innerText.toUpperCase().indexOf(filter) > -1) {
                flag = true;
                break;
            }
        }
        tr[i].style.display = flag ? "" : "none";
    }
}
</script>

</body>
</html>