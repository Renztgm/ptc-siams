<?php
// Test file to verify admission form submission flow

// Database configuration detection
$isInfinityFree = (strpos($_SERVER['DOCUMENT_ROOT'], 'infinityfree.com') !== false) || 
                  (strpos($_SERVER['SERVER_NAME'], 'infinityfree') !== false);

if ($isInfinityFree) {
    $host = "sql302.infinityfree.com";  
    $db_user = "if0_41171248";
    $db_pass = "vJoJA8PL88TC";
    $db_name = "if0_41171248_ptc_database";
    $env = "InfinityFree";
} else {
    $host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "ptc_system";
    $env = "Localhost";
}

echo "<h1>PTC Admission Form - Test Diagnostics</h1>";
echo "<p><strong>Environment:</strong> " . $env . "</p>";
echo "<p><strong>Host:</strong> " . $host . "</p>";
echo "<p><strong>Database:</strong> " . $db_name . "</p>";

// Test database connection
$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo "<p style='color: red;'><strong>❌ Database Connection FAILED</strong></p>";
    echo "<p>Error: " . mysqli_connect_error() . "</p>";
    exit;
}

echo "<p style='color: green;'><strong>✅ Database Connection SUCCESS</strong></p>";

// Check if admissions table exists
$tableCheckResult = mysqli_query($conn, "SHOW TABLES LIKE 'admissions'");
if (!$tableCheckResult) {
    echo "<p style='color: red;'><strong>❌ Error checking for admissions table</strong></p>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    exit;
}

if (mysqli_num_rows($tableCheckResult) == 0) {
    echo "<p style='color: orange;'><strong>⚠️ Admissions table NOT FOUND</strong></p>";
    echo "<p>Available tables:</p>";
    $tableList = mysqli_query($conn, "SHOW TABLES");
    if ($tableList) {
        echo "<ul>";
        while ($row = mysqli_fetch_array($tableList)) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    }
    exit;
}

echo "<p style='color: green;'><strong>✅ Admissions table EXISTS</strong></p>";

// Check table structure
echo "<h2>Admissions Table Structure:</h2>";
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

// Count records
$countResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM admissions");
if ($countResult) {
    $row = mysqli_fetch_assoc($countResult);
    echo "<p><strong>Total admissions records:</strong> " . $row['total'] . "</p>";
}

echo "<h2>Test Submission:</h2>";
echo "<p>To test form submission, visit: <a href='public/Register.html'>Register.html</a></p>";

mysqli_close($conn);
?>
