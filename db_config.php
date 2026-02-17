<?php

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Detect environment
// Check if running on InfinityFree by looking at server path or domain
$isInfinityFree = (strpos($_SERVER['DOCUMENT_ROOT'], 'infinityfree.com') !== false) || 
                  (strpos($_SERVER['SERVER_NAME'], 'infinityfree') !== false);

if ($isInfinityFree) {
    // ===== INFINITYFREE =====
    $host     = "sql302.infinityfree.com";  
    $db_user  = "if0_41171248";
    $db_pass  = "vJoJA8PL88TC";
    $db_name  = "if0_41171248_ptc_database";
} else {
    // ===== LOCALHOST (XAMPP) =====
    $host     = "localhost";
    $db_user  = "root";
    $db_pass  = "";
    $db_name  = "ptc_system";
}

// Create connection
$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    $error = "Connection Error: " . mysqli_connect_error() . " | Host: " . $host . " | User: " . $db_user;
    
    // Log to file
    file_put_contents("db_error.log", date('Y-m-d H:i:s') . " - " . $error . "\n", FILE_APPEND);
    
    // Display in console and page
    echo "<script>console.error('" . addslashes($error) . "');</script>";
    die($error);
}

?>
