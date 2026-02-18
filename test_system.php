<?php
// Debugging test for admission system

echo "<h1>PTC Admission System Debug Tests</h1>";
echo "<hr>";

// Test 1: Database Connection
echo "<h2>Test 1: Database Connection</h2>";
require_once __DIR__ . '/config/db_config.php';

if ($conn) {
    echo "<p style='color: green;'><strong>✓ Database Connection: SUCCESS</strong></p>";
    echo "<p>Host: " . (strpos($_SERVER['DOCUMENT_ROOT'], 'infinityfree') !== false ? "InfinityFree" : "Localhost") . "</p>";
} else {
    echo "<p style='color: red;'><strong>✗ Database Connection: FAILED</strong></p>";
}

// Test 2: Check if admissions table exists
echo "<h2>Test 2: Admissions Table Structure</h2>";
$result = $conn->query("DESCRIBE admissions");
if ($result) {
    echo "<p style='color: green;'><strong>✓ Admissions table exists</strong></p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>✗ Error checking admissions table: " . $conn->error . "</strong></p>";
}

// Test 3: Check sample admission record
echo "<h2>Test 3: Admission Records Count</h2>";
$count = $conn->query("SELECT COUNT(*) as total FROM admissions")->fetch_assoc()['total'];
echo "<p>Total admissions in database: <strong>$count</strong></p>";

// Test 4: Check exam_config.php
echo "<h2>Test 4: Exam Config File</h2>";
$configFile = __DIR__ . '/api/exam_config.php';
if (file_exists($configFile)) {
    echo "<p style='color: green;'><strong>✓ exam_config.php exists</strong></p>";
} else {
    echo "<p style='color: red;'><strong>✗ exam_config.php NOT found</strong></p>";
}

$configJsonFile = __DIR__ . '/api/exam_config.json';
if (file_exists($configJsonFile)) {
    echo "<p style='color: green;'><strong>✓ exam_config.json exists</strong></p>";
    $config = json_decode(file_get_contents($configJsonFile), true);
    echo "<pre>" . json_encode($config, JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<p style='color: orange;'><strong>⚠ exam_config.json NOT found (will use default)</strong></p>";
}

// Test 5: Check send_admission_email.php
echo "<h2>Test 5: Send Admission Email Script</h2>";
$emailScript = __DIR__ . '/api/send_admission_email.php';
if (file_exists($emailScript)) {
    echo "<p style='color: green;'><strong>✓ send_admission_email.php exists</strong></p>";
} else {
    echo "<p style='color: red;'><strong>✗ send_admission_email.php NOT found</strong></p>";
}

// Test 6: Check get_all_admissions.php
echo "<h2>Test 6: Get All Admissions API</h2>";
$apiScript = __DIR__ . '/api/get_all_admissions.php';
if (file_exists($apiScript)) {
    echo "<p style='color: green;'><strong>✓ get_all_admissions.php exists</strong></p>";
} else {
    echo "<p style='color: red;'><strong>✗ get_all_admissions.php NOT found</strong></p>";
}

// Test 7: File permissions
echo "<h2>Test 7: Directory Permissions</h2>";
$dirs = array(
    __DIR__ . '/storage' => 'storage',
    __DIR__ . '/storage/admissions' => 'storage/admissions',
    __DIR__ . '/api' => 'api',
);

foreach ($dirs as $path => $name) {
    if (is_writable($path)) {
        echo "<p style='color: green;'><strong>✓ $name: WRITABLE</strong></p>";
    } else {
        echo "<p style='color: orange;'><strong>⚠ $name: NOT WRITABLE</strong></p>";
    }
}

// Test 8: Summary
echo "<h2>Summary</h2>";
echo "<p>All systems should be ready for admission submissions.</p>";
echo "<p>If you see any errors above, contact your system administrator.</p>";

$conn->close();
?>
