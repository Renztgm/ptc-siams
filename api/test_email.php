<?php
// Test Email Configuration
echo "<h1>PTC Email System Diagnostic</h1>";
echo "<pre>";

// 1. Check PHP version
echo "=== PHP Configuration ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "OS: " . php_uname() . "\n\n";

// 2. Check mail function
echo "=== Mail Function Status ===\n";
echo "mail() function enabled: " . (function_exists('mail') ? "YES" : "NO") . "\n";
echo "sendmail_path: " . ini_get('sendmail_path') . "\n";
echo "SMTP: " . ini_get('SMTP') . "\n";
echo "smtp_port: " . ini_get('smtp_port') . "\n";
echo "sendmail_from: " . ini_get('sendmail_from') . "\n\n";

// 3. Check OpenSSL/TLS (required for Gmail)
echo "=== OpenSSL/TLS Support ===\n";
echo "OpenSSL support: " . (extension_loaded('openssl') ? "YES" : "NO") . "\n";
echo "fsockopen available: " . (function_exists('fsockopen') ? "YES" : "NO") . "\n";
echo "stream functions available: " . (function_exists('stream_socket_enable_crypto') ? "YES" : "NO") . "\n\n";

// 4. Test basic mail function
echo "=== Testing mail() Function ===\n";
$testEmail = $_POST['testEmail'] ?? 'test@example.com';
if (!empty($_POST['testEmail'])) {
    $subject = "PTC Test Email - " . date('Y-m-d H:i:s');
    $body = "This is a test email from your PTC server.\n\nTimestamp: " . date('Y-m-d H:i:s') . "\nServer: " . $_SERVER['SERVER_NAME'];
    $headers = "From: arquero.sofia.tcu@gmail.com\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    
    $result = @mail($testEmail, $subject, $body, $headers);
    echo "mail() function result: " . ($result ? "SUCCESS (returned true)" : "FAILED (returned false)") . "\n";
    echo "Email sent to: $testEmail\n";
    if (!$result) {
        echo "Note: If mail returned false, check with your hosting provider if email is configured.\n";
    }
    echo "\n";
}

// 5. Check database connection
echo "=== Database Connection ===\n";
if (file_exists(__DIR__ . '/../config/db_config.php')) {
    require_once __DIR__ . '/../config/db_config.php';
    try {
        if (!$conn) {
            echo "Database Connection: FAILED\n";
            echo "Error: " . mysqli_connect_error() . "\n";
        } else {
            echo "Database Connection: SUCCESS\n";
            $conn->close();
        }
    } catch (Exception $e) {
        echo "Database Connection: ERROR - " . $e->getMessage() . "\n";
    }
} else {
    echo "db_config.php not found\n";
}
echo "\n";

// 6. Check if send_admission_email.php exists
echo "=== File Status ===\n";
echo "send_admission_email.php exists: " . (file_exists(__DIR__ . '/send_admission_email.php') ? "YES" : "NO") . "\n";
echo "Register.html exists: " . (file_exists(__DIR__ . '/../public/Register.html') ? "YES" : "NO") . "\n\n";

// 7. Recommendations
echo "=== Recommendations ===\n";
if (!extension_loaded('openssl')) {
    echo "⚠ OpenSSL is NOT enabled - SMTP to Gmail won't work!\n";
    echo "   Solution: Enable OpenSSL in your php.ini\n";
}

if (ini_get('sendmail_path') === false || empty(ini_get('sendmail_path'))) {
    echo "⚠ sendmail_path is not configured\n";
    echo "   If you're on Windows, the server should use SMTP settings\n";
}

echo "\n=== Next Steps ===\n";
echo "1. If mail() returned FALSE: Contact your hosting provider to enable email\n";
echo "2. If you see 'Failed connection': Gmail might be blocking the connection\n";
echo "3. Gmail requires:\n";
echo "   - App Password (not regular password)\n";
echo "   - 2-Factor Authentication enabled on Google Account\n";
echo "   - Port 587 or 25 open (not blocked by firewall)\n";

echo "\n";

// 8. Check recent admissions in database
echo "=== Recent Admissions in Database ===\n";
if (file_exists(__DIR__ . '/../config/db_config.php')) {
    try {
        require_once __DIR__ . '/../config/db_config.php';
        
        if ($conn) {
            $result = $conn->query("SELECT * FROM admissions ORDER BY submission_date DESC LIMIT 5");
            if ($result) {
                echo "Recent submissions:\n";
                while ($row = $result->fetch_assoc()) {
                    echo "- ID: " . $row['admission_id'] . " | Email: " . $row['email'] . " | Date: " . $row['submission_date'] . "\n";
                }
            } else {
                echo "Admissions table not found or empty\n";
            }
            $conn->close();
        }
    } catch (Exception $e) {
        echo "Could not query database\n";
    }
}

echo "</pre>";

// Show test form
echo "<hr>";
echo "<h2>Test Email Sending</h2>";
echo "<form method='POST'>";
echo "Enter your email to test: <input type='email' name='testEmail' required>";
echo " <button type='submit'>Send Test Email</button>";
echo "</form>";
?>
