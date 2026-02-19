<?php
session_start();

// Check if student is authenticated
if (!isset($_SESSION['exam_student'])) {
    header('Location: exam_login.php');
    exit();
}

$student = $_SESSION['exam_student'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Entrance Examination</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      background-color: #f5f5f5;
    }

    .page-header {
      background-color: #1b5e20;
      color: white;
      padding: 20px;
      display: flex;
      align-items: center;
      gap: 15px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .page-header img {
      height: 50px;
    }

    .page-header h1 {
      font-size: 24px;
      font-weight: 700;
    }

    .exam-container {
      max-width: 900px;
      margin: 30px auto;
      background: #ffffff;
      padding: 30px 40px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .exam-header {
      text-align: center;
      border-bottom: 3px solid #1b5e20;
      padding-bottom: 15px;
      margin-bottom: 25px;
    }

    .exam-header h2 {
      margin: 0;
      color: #1b5e20;
      font-size: 24px;
    }

    .exam-header p {
      margin: 5px 0;
      font-size: 14px;
    }

    .section-title {
      background-color: #1b5e20;
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
      background-color: #2e7d32;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .submit-btn:hover {
      background-color: #1b5e20;
    }

    .timer {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #fff3cd;
      border: 2px solid #ffc107;
      padding: 15px 20px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: bold;
      color: #856404;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 1000;
      min-width: 150px;
      text-align: center;
    }

    .timer-warning {
      color: #d32f2f;
      background: #ffebee;
      border-color: #d32f2f;
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
<div class="page-header">
  <img src="../assets/Logo.png" alt="PTC Logo">
  <h1>Entrance Exam Portal</h1>
</div>

<form action="submit_exam.php" method="post">
  <div class="exam-container">

  <div class="exam-header">
    <h2>Entrance Examination</h2>
    <p><strong>Instructions:</strong> Read each question carefully. Choose the best answer.</p>
  </div>

  <input type="hidden" name="admission_id" id="hidden_admission_id" value="<?php echo htmlspecialchars($student['admission_id']); ?>">
  <input type="hidden" name="start_time" id="start_time" value="">
  <input type="hidden" name="end_time" id="end_time" value="">

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
// Create floating timer element
const timerDiv = document.createElement('div');
timerDiv.id = 'timer';
timerDiv.className = 'timer';
timerDiv.innerHTML = '⏱️ Time Remaining: <span id="timeRemaining">3:00:00</span>';
document.body.appendChild(timerDiv);

// Set exam start time
const startTime = new Date();
document.getElementById('start_time').value = startTime.toISOString();

// 3 hours = 10800 seconds
const EXAM_DURATION = 3 * 60 * 60 * 1000; // 3 hours in milliseconds
const endTime = new Date(startTime.getTime() + EXAM_DURATION);
document.getElementById('end_time').value = endTime.toISOString();

// Timer countdown
function updateTimer() {
    const now = new Date();
    const timeLeft = endTime - now;
    
    if (timeLeft <= 0) {
        // Time's up - auto-submit the form
        alert('Time limit exceeded! The exam will be automatically submitted.');
        document.querySelector('form').submit();
        return;
    }
    
    const hours = Math.floor(timeLeft / (1000 * 60 * 60));
    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
    
    const timeDisplay = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    document.getElementById('timeRemaining').textContent = timeDisplay;
    
    // Change color to red when less than 30 minutes
    const timerElement = document.getElementById('timer');
    if (timeLeft < 30 * 60 * 1000) {
        timerElement.classList.add('timer-warning');
    }
}

// Update timer every second
setInterval(updateTimer, 1000);
updateTimer(); // Initial call

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
