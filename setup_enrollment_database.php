<?php
// Setup database tables for Enrollment Document Management

$host = "localhost";
$db_user = "root";
$db_pass = ""; // default in XAMPP
$db_name = "ptc_system";

// Connect to database
$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 1. CREATE ENROLLMENT DOCUMENTS TABLE
$sql_enrollment = "CREATE TABLE IF NOT EXISTS enrollment_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    contact_number VARCHAR(20),
    student_type ENUM('New', 'Transferee') NOT NULL,
    program VARCHAR(100),
    year_level VARCHAR(50),
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (student_id),
    INDEX (student_type)
)";

if (mysqli_query($conn, $sql_enrollment)) {
    echo "<p style='color: green;'><strong>✅ Enrollment Documents table created successfully!</strong></p>";
} else {
    echo "<p style='color: red;'><strong>❌ Error creating Enrollment Documents table: " . mysqli_error($conn) . "</strong></p>";
}

// 2. CREATE REQUIRED DOCUMENTS TABLE
$sql_required_docs = "CREATE TABLE IF NOT EXISTS required_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_name VARCHAR(255) NOT NULL,
    student_type ENUM('New', 'Transferee', 'Both') NOT NULL,
    document_description TEXT,
    required BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    UNIQUE KEY (document_name, student_type)
)";

if (mysqli_query($conn, $sql_required_docs)) {
    echo "<p style='color: green;'><strong>✅ Required Documents table created successfully!</strong></p>";
} else {
    echo "<p style='color: red;'><strong>❌ Error creating Required Documents table: " . mysqli_error($conn) . "</strong></p>";
}

// 3. CREATE DOCUMENT SUBMISSIONS TABLE
$sql_doc_submissions = "CREATE TABLE IF NOT EXISTS document_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    document_id INT NOT NULL,
    file_path VARCHAR(255),
    file_name VARCHAR(255),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    submission_status ENUM('Pending', 'Received', 'Verified', 'Rejected') DEFAULT 'Pending',
    admin_notes TEXT,
    verified_by VARCHAR(100),
    verified_date DATETIME,
    FOREIGN KEY (enrollment_id) REFERENCES enrollment_documents(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES required_documents(id) ON DELETE CASCADE,
    UNIQUE KEY (enrollment_id, document_id)
)";

if (mysqli_query($conn, $sql_doc_submissions)) {
    echo "<p style='color: green;'><strong>✅ Document Submissions table created successfully!</strong></p>";
} else {
    echo "<p style='color: red;'><strong>❌ Error creating Document Submissions table: " . mysqli_error($conn) . "</strong></p>";
}

// 4. INSERT DEFAULT REQUIRED DOCUMENTS FOR NEW STUDENTS
echo "<h3>Inserting Required Documents...</h3>";

$docs_new = [
    ['F138 B (SHS Grade 12 Card)', 'New', 'Form 138-B: Senior High School Grade 12 Card', 1, 1],
    ['F137', 'New', 'Form 137: Required document', 1, 2],
    ['PSA Birth Certificate (Photocopy)', 'New', 'Birth Certificate photocopy from PSA', 1, 3],
    ['Certificate of Good Moral Character', 'New', 'Original Certificate of Good Moral Character', 1, 4],
    ['2x2 Picture (White Background)', 'New', 'Recent 2x2 picture with white background', 1, 5],
    ['Long Folder with Plastic Jacket', 'New', 'Long folder with plastic jacket for documents', 1, 6]
];

$docs_transferee = [
    ['Transcript of Records', 'Transferee', 'Transcript of Records from previous institution', 1, 1],
    ['Honorable Dismissal/Transfer Credentials', 'Transferee', 'Honorable Dismissal or Transfer Credentials', 1, 2],
    ['PSA Birth Certificate (Photocopy)', 'Both', 'Birth Certificate photocopy from PSA', 1, 3],
    ['2x2 Picture (White Background)', 'Both', 'Recent 2x2 picture with white background', 1, 4],
    ['Long Folder with Plastic Jacket', 'Both', 'Long folder with plastic jacket for documents', 1, 5]
];

// Merge all documents
$all_docs = array_merge($docs_new, $docs_transferee);

foreach ($all_docs as $doc) {
    // Check if document already exists
    $check_sql = "SELECT id FROM required_documents WHERE document_name = '{$doc[0]}' AND student_type = '{$doc[1]}'";
    $result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($result) == 0) {
        $insert_sql = "INSERT INTO required_documents (document_name, student_type, document_description, required, display_order) 
                      VALUES ('{$doc[0]}', '{$doc[1]}', '{$doc[2]}', {$doc[3]}, {$doc[4]})";
        
        if (mysqli_query($conn, $insert_sql)) {
            echo "<p style='color: green;'>✅ Added: {$doc[0]} ({$doc[1]})</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Could not add: {$doc[0]} - " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Already exists: {$doc[0]} ({$doc[1]})</p>";
    }
}

// Show table structure
echo "<h2>Enrollment Documents Table Structure:</h2>";
$columnsResult = mysqli_query($conn, "DESCRIBE enrollment_documents");
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

echo "<h2>Required Documents Table:</h2>";
$docsResult = mysqli_query($conn, "SELECT * FROM required_documents ORDER BY student_type, display_order");
if ($docsResult) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Document Name</th><th>Student Type</th><th>Description</th></tr>";
    while ($row = mysqli_fetch_assoc($docsResult)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['document_name'] . "</td>";
        echo "<td>" . $row['student_type'] . "</td>";
        echo "<td>" . substr($row['document_description'], 0, 50) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
}

mysqli_close($conn);
?>
