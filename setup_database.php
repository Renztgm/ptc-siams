<?php
// Create admissions table if it doesn't exist

$host = "localhost";
$db_user = "root";
$db_pass = ""; // default in XAMPP
$db_name = "ptc_system";

// Connect to database
$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// SQL to create table
$sql = "CREATE TABLE IF NOT EXISTS admissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    given_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    contact_number VARCHAR(20),
    email VARCHAR(100) NOT NULL,
    program VARCHAR(100),
    admission_id VARCHAR(50) UNIQUE,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (admission_id),
    INDEX (email)
)";

if (mysqli_query($conn, $sql)) {
    echo "<p style='color: green;'><strong>✅ Admissions table created successfully!</strong></p>";
} else {
    echo "<p style='color: red;'><strong>❌ Error creating table: " . mysqli_error($conn) . "</strong></p>";
}

// Check table structure
echo "<h2>Current Table Structure:</h2>";
$columnsResult = mysqli_query($conn, "DESCRIBE admissions");
if ($columnsResult) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = mysqli_fetch_assoc($columnsResult)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

mysqli_close($conn);
?>
