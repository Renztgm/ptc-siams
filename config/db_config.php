<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$serverName = $_SERVER['SERVER_NAME'] ?? '';

$isInfinityFree = (strpos($documentRoot, 'infinityfree.com') !== false) ||
                  (strpos($serverName, 'infinityfree') !== false);

if ($isInfinityFree) {
    $host = "sql302.infinityfree.com";
    $db_user = "if0_41171248";
    $db_pass = "vJoJA8PL88TC";
    $db_name = "if0_41171248_ptc_database";
} else {
    $host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "ptc_system";
}

if (!extension_loaded('mysqli') || !class_exists('mysqli')) {
    $error = "MySQLi extension is not loaded. PHP=" . PHP_VERSION .
             " | SAPI=" . php_sapi_name() .
             " | ini=" . (php_ini_loaded_file() ?: 'none');
    @file_put_contents(__DIR__ . "/../db_error.log", date('Y-m-d H:i:s') . " - " . $error . "\n", FILE_APPEND);
    die($error);
}

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    $error = "Connection Error: " . $conn->connect_error . " | Host: " . $host . " | User: " . $db_user;
    @file_put_contents(__DIR__ . "/../db_error.log", date('Y-m-d H:i:s') . " - " . $error . "\n", FILE_APPEND);
    die($error);
}

// Email Configuration for System Notifications
$emailConfig = [
    'sender_email' => 'arquero.sofia.tcu@gmail.com',
    'sender_password' => 'qjpf wvol cpgq tsoa', // Gmail App Password
    'sender_name' => 'PTC Admissions',
    'from_address' => 'arquero.sofia.tcu@gmail.com'
];

?>
