
//<?php

<?php
// 1. Database Configuration
$host = "sql302.infinityfree.com";
$db_user = "if0_41171248";
$db_pass = "vJoJA8PL88TC";
$db_name = "if0_41171248_ptc_database";


// 2. Create Connection
$conn = new mysqli($host, $db_user, $db_pass, $db_name);



if ($conn->connect_error) {
    die("<div style='color:red; padding:20px;'>Connection failed: " . $conn->connect_error . "</div>");
}

$conn->set_charset("utf8mb4");

// 2. Define correct answers
$answers = [
    'q1' => 1, 'q2' => 1, 'q3' => 2,
    'q4' => 2, 'q5' => 2, 'q6' => 2
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic input retrieval
    $admission_id = trim($_POST['admission_id'] ?? ''); // Added to match SQL structure
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    $score = 0;
    $total = count($answers);

    // Calculate score
    for ($i = 1; $i <= $total; $i++) {
        $key = "q$i";
        if (isset($_POST[$key]) && (int)$_POST[$key] === $answers[$key]) {
            $score++;
        }
    }

    // Pass threshold (75%)
    $pass_threshold = (int)ceil(0.75 * $total);
    $status = ($score >= $pass_threshold) ? "Passed" : "Failed";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $email = ''; }

    // 3. Corrected Prepared Statement to include admission_id
    // Matches table: id, admission_id, fullname, email, score, total_questions, status...
    $stmt = $conn->prepare("INSERT INTO exam_results (admission_id, fullname, email, score, total_questions, status) VALUES (?, ?, ?, ?, ?, ?)");
    
    $dbError = '';
    if ($stmt) {
        // "sssiis" corresponds to: string, string, string, integer, integer, string
        $stmt->bind_param("sssiis", $admission_id, $fullname, $email, $score, $total, $status);
        if (!$stmt->execute()) {
            $dbError = $stmt->error;
        }
        $stmt->close();
    } else {
        $dbError = $conn->error;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Exam Result</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; padding-top: 50px; }
            .card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center; width: 420px; }
            .result-header { font-size: 24px; margin-bottom: 10px; }
            .score-box { font-size: 48px; font-weight: bold; margin: 20px 0; }
            .Passed { color: #27ae60; }
            .Failed { color: #c0392b; }
            .error { color: #c0392b; background: #fdecea; padding: 10px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="card">
            <h2 class="result-header">Exam Result</h2>
            <p>Name: <strong><?php echo htmlspecialchars($fullname); ?></strong></p>
            <p>Admission ID: <strong><?php echo htmlspecialchars($admission_id); ?></strong></p>
            
            <div class="score-box <?php echo $status; ?>">
                <?php echo "$score / $total"; ?>
            </div>
            
            <h3 class="<?php echo $status; ?>">Status: <?php echo $status; ?></h3>

            <?php if ($dbError): ?>
                <p class="error">Database error: <?php echo htmlspecialchars($dbError); ?></p>
            <?php else: ?>
                <p style="color: #27ae60;">✓ Record successfully saved.</p>
            <?php endif; ?>
            
            <hr>
            <p><small>Please prepare to scan: SHS ID, Form I-37, Class Card, and Birth Certificate.</small></p>
        </div>
    </body>
    </html>
    <?php 
} else {
    header("Location: Entrance_Exam.html");
    exit();
}
?>