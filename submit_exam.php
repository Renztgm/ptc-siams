<?php
include "db_exam.php";

$answers = [
    0, // Q1 correct index
    2, // Q2
    1, // Q3
    3, // Q4
    1, // Q5
    1, // Q6
    1, // Q7
    3, // Q8
    1, // Q9
    0, // Q10
    2, // Q11
    0, // Q12
    2, // Q13
    0, // Q14
    3, // Q15
    0, // Q16
    1, // Q17
    1, // Q18
    2, // Q19
    1  // Q20
];

$score = 0;

for ($i = 1; $i <= 20; $i++) {
    if (isset($_POST["q$i"]) && $_POST["q$i"] == $answers[$i-1]) {
        $score++;
    }
}

$fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
$email    = mysqli_real_escape_string($conn, $_POST['email']);

$sql = "INSERT INTO exam_results (fullname, email, score)
        VALUES ('$fullname', '$email', '$score')";

mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Exam Submitted</title>
</head>
<body>
    <h2>Thank you for taking the entrance exam.</h2>
    <p>Your exam has been successfully submitted.</p>
    <p><strong>The result will be processed by the administration.</strong></p>
</body>
</html>