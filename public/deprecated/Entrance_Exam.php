<?php
session_start();

// Check if student is authenticated and registered for exam
if (!isset($_SESSION['exam_student'])) {
    header('Location: exam_login.php');
    exit();
}

if (!isset($_SESSION['exam_session_id'])) {
    header('Location: exam_session_selection.php');
    exit();
}

$student = $_SESSION['exam_student'];
$exam_registration_id = $_SESSION['exam_registration_id'] ?? 0;
$exam_session_id = $_SESSION['exam_session_id'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Entrance Examination</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f9f4;
      margin: 0;
      padding: 0;
    }

    .exam-container {
      max-width: 900px;
      margin: 30px auto;
      background: #ffffff;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .exam-header {
      text-align: center;
      border-bottom: 3px solid #006400;
      padding-bottom: 15px;
      margin-bottom: 25px;
    }

    .exam-header h1 {
      margin: 0;
      color: #006400;
    }

    .exam-header p {
      margin: 5px 0;
      font-size: 14px;
    }

    .section-title {
      background-color: #006400;
      color: #fff;
      padding: 8px 15px;
      border-radius: 5px;
      margin: 25px 0 15px;
      font-size: 18px;
    }

    .question {
      margin-bottom: 15px;
    }

    .question p {
      margin: 0 0 8px;
      font-weight: bold;
    }

    .choices label {
      display: block;
      padding: 4px 0;
      cursor: pointer;
    }

    .student-info {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }

    .student-info input {
      padding: 8px;
      width: 100%;
      border: 1px solid #ccc;
      border-radius: 5px;
      background-color: #f0f0f0;
      color: #666;
    }

    .submit-btn {
      display: block;
      margin: 30px auto 0;
      padding: 12px 40px;
      font-size: 16px;
      background-color: #006400;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .submit-btn:hover {
      background-color: #228b22;
    }

    @media print {
      body {
        background: none;
      }
      .exam-container {
        box-shadow: none;
        margin: 0;
      }
      .submit-btn {
        display: none;
      }
    }
  </style>
</head>

<body>
<form action="submit_exam.php" method="post">
  <div class="exam-container">

  <div class="exam-header">
    <h1>ENTRANCE EXAMINATION</h1>
    <p><strong>Instructions:</strong> Read each question carefully. Choose the best answer.</p>
  </div>

  <input type="hidden" name="exam_registration_id" value="<?php echo htmlspecialchars($exam_registration_id); ?>">
  <input type="hidden" name="exam_session_id" value="<?php echo htmlspecialchars($exam_session_id); ?>">

  <div class="student-info">
    <input type="text" placeholder="Admission ID" name="admission_id" id="admission_id" value="<?php echo htmlspecialchars($student['admission_id']); ?>" required readonly>
    <input type="text" placeholder="Full Name" name="fullname" id="fullname" value="<?php echo htmlspecialchars($student['full_name']); ?>" required readonly>
    <input type="email" placeholder="Email Address" name="email" id="email" value="<?php echo htmlspecialchars($student['email']); ?>" required readonly>
  </div>

  <!-- ENGLISH -->
  <div class="section-title">PART I – ENGLISH</div>

  <div class="question">
    <p>1. She ___ like coffee.</p>
    <div class="choices">
      <label><input type="radio" name="q1" value="0" required> A. don't</label>
      <label><input type="radio" name="q1" value="1"> B. doesn't</label>
      <label><input type="radio" name="q1" value="2"> C. don't likes</label>
      <label><input type="radio" name="q1" value="3"> D. doesn't likes</label>
    </div>
  </div>

  <div class="question">
    <p>2. What is the synonym of rapid?</p>
    <div class="choices">
      <label><input type="radio" name="q2" value="0" required> A. Slow</label>
      <label><input type="radio" name="q2" value="1"> B. Fast</label>
      <label><input type="radio" name="q2" value="2"> C. Weak</label>
      <label><input type="radio" name="q2" value="3"> D. Late</label>
    </div>
  </div>

  <!-- MATHEMATICS -->
  <div class="section-title">PART II – MATHEMATICS</div>

  <div class="question">
    <p>3. What is 25 × 4?</p>
    <div class="choices">
      <label><input type="radio" name="q3" value="0" required> A. 50</label>
      <label><input type="radio" name="q3" value="1"> B. 75</label>
      <label><input type="radio" name="q3" value="2"> C. 100</label>
      <label><input type="radio" name="q3" value="3"> D. 125</label>
    </div>
  </div>

  <div class="question">
    <p>4. Solve: 12 + 18 − 5 =</p>
    <div class="choices">
      <label><input type="radio" name="q4" value="0" required> A. 20</label>
      <label><input type="radio" name="q4" value="1"> B. 23</label>
      <label><input type="radio" name="q4" value="2"> C. 25</label>
      <label><input type="radio" name="q4" value="3"> D. 30</label>
    </div>
  </div>

  <!-- SCIENCE -->
  <div class="section-title">PART III – SCIENCE</div>

  <div class="question">
    <p>5. What organ pumps blood throughout the body?</p>
    <div class="choices">
      <label><input type="radio" name="q5" value="0" required> A. Brain</label>
      <label><input type="radio" name="q5" value="1"> B. Lungs</label>
      <label><input type="radio" name="q5" value="2"> C. Heart</label>
      <label><input type="radio" name="q5" value="3"> D. Liver</label>
    </div>
  </div>

  <!-- GENERAL INFORMATION -->
  <div class="section-title">PART IV – GENERAL INFORMATION</div>

  <div class="question">
    <p>6. Who is the national hero of the Philippines?</p>
    <div class="choices">
      <label><input type="radio" name="q6" value="0" required> A. Andres Bonifacio</label>
      <label><input type="radio" name="q6" value="1"> B. Emilio Aguinaldo</label>
      <label><input type="radio" name="q6" value="2"> C. Jose Rizal</label>
      <label><input type="radio" name="q6" value="3"> D. Apolinario Mabini</label>
    </div>
  </div>

  <button type="submit" class="submit-btn">Submit Exam</button>

<script>
document.querySelector(".submit-btn").addEventListener("click", function (e) {
    if (!confirm("Are you sure you want to submit the exam? You cannot make changes after submission.")) {
        e.preventDefault();
    }
});
</script>

  </div>

</form>

</body>
</html>
