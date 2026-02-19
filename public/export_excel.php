<?php
include "db.php";

$search = "";
if(isset($_GET['search'])) $search = mysqli_real_escape_string($conn, $_GET['search']);

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=entrance_exam_report.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "Full Name\tEmail\tScore\tDate Taken\t\n";

$sql = "SELECT * FROM entrance_results WHERE fullname LIKE '%$search%' OR email LIKE '%$search%' ORDER BY date_taken DESC";
$result = mysqli_query($conn, $sql);

while($row = mysqli_fetch_assoc($result)){
    echo "{$row['fullname']}\t{$row['email']}\t{$row['score']}\t{$row['date_taken']}\n";
}
?>