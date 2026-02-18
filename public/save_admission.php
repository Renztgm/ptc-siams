<?php
include "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname   = mysqli_real_escape_string($conn, $_POST['fullname']);
    $address    = mysqli_real_escape_string($conn, $_POST['address']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $contact    = mysqli_real_escape_string($conn, $_POST['contact']);
    $year_level = mysqli_real_escape_string($conn, $_POST['year_level']);
    $program    = mysqli_real_escape_string($conn, $_POST['program']);

    $sql = "INSERT INTO admission2 
            (fullname, address, email, contact, year_level, program)
            VALUES 
            ('$fullname', '$address', '$email', '$contact', '$year_level', '$program')";

    if (mysqli_query($conn, $sql)) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Application Submitted</title>
            <script>
                window.onload = function () {
                    let takeExam = confirm(
                        "Application submitted successfully!\n\nWould you like to take the entrance exam now?"
                    );

                    if (takeExam) {
                        window.location.href = "exam.php";
                    } else {
                        window.location.href = "mainpage.html";
                    }
                };
            </script>
        </head>
        <body>
        </body>
        </html>
        <?php
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
