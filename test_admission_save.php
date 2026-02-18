<?php
// Test database save functionality

echo "<h1>Test Admission Save</h1>";
echo "<hr>";

// Test data
$testData = [
    'firstName' => 'John',
    'middleName' => 'Michael',
    'lastName' => 'Doe',
    'email' => 'john.doe@example.com',
    'contact' => '09081234567',
    'address' => '123 Main Street, City',
    'program' => 'BS Information Technology',
    'admissionId' => 'PTC-20260218-' . rand(1000, 9999)
];

echo "<p><strong>Test Data:</strong></p>";
echo "<pre>";
print_r($testData);
echo "</pre>";

// Direct database connection (same as send_admission_email.php)
$host     = "sql302.infinityfree.com";  
$db_user  = "if0_41171248";
$db_pass  = "vJoJA8PL88TC";
$db_name  = "if0_41171248_ptc_database";

echo "<p><strong>Attempting to connect to database...</strong></p>";

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo "<p style='color: red;'><strong>✗ ERROR: Cannot connect to database</strong></p>";
    echo "Error: " . mysqli_connect_error();
    exit;
} else {
    echo "<p style='color: green;'><strong>✓ Connected to database successfully</strong></p>";
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Prepare test insert
$givenName = $testData['firstName'];
$middleName = $testData['middleName'];
$lastName = $testData['lastName'];
$email = $testData['email'];
$contactNumber = $testData['contact'];
$address = $testData['address'];
$program = $testData['program'];
$admissionId = $testData['admissionId'];
$fullName = trim($givenName . ' ' . $middleName . ' ' . $lastName);

echo "<p><strong>Preparing SQL statement...</strong></p>";

$sql = "INSERT INTO admissions (given_name, middle_name, last_name, full_name, address, contact_number, email, program, admission_id, submission_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "<p style='color: red;'><strong>✗ ERROR: Could not prepare statement</strong></p>";
    echo "Error: " . $conn->error;
    $conn->close();
    exit;
} else {
    echo "<p style='color: green;'><strong>✓ Statement prepared successfully</strong></p>";
}

echo "<p><strong>Binding parameters...</strong></p>";

if (!$stmt->bind_param("sssssssss", $givenName, $middleName, $lastName, $fullName, $address, $contactNumber, $email, $program, $admissionId)) {
    echo "<p style='color: red;'><strong>✗ ERROR: Could not bind parameters</strong></p>";
    echo "Error: " . $stmt->error;
    $stmt->close();
    $conn->close();
    exit;
} else {
    echo "<p style='color: green;'><strong>✓ Parameters bound successfully</strong></p>";
}

echo "<p><strong>Executing INSERT...</strong></p>";

if ($stmt->execute()) {
    echo "<p style='color: green;'><strong>✓ SUCCESS: Data inserted into database!</strong></p>";
    echo "<p>Insert ID: " . $stmt->insert_id . "</p>";
    echo "<p>Admission ID: " . $admissionId . "</p>";
} else {
    echo "<p style='color: red;'><strong>✗ ERROR: Could not execute statement</strong></p>";
    echo "Error: " . $stmt->error;
}

// Verify the insert
echo "<p><strong>Verifying insert...</strong></p>";
$verifyQuery = "SELECT * FROM admissions WHERE admission_id = ?";
$verifyStmt = $conn->prepare($verifyQuery);
$verifyStmt->bind_param("s", $admissionId);
$verifyStmt->execute();
$result = $verifyStmt->get_result();

if ($result->num_rows > 0) {
    echo "<p style='color: green;'><strong>✓ Verification successful! Data found in database.</strong></p>";
    $row = $result->fetch_assoc();
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "<p style='color: orange;'><strong>⚠ No matching record found for this admission ID</strong></p>";
}

// Clean up
$stmt->close();
$verifyStmt->close();
$conn->close();

echo "<hr>";
echo "<p><a href='../public/view_admissions.php'>View all admissions in database</a></p>";
?>
