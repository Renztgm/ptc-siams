<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../public/admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #e6ffe6; /* Light green background */
    }

    /* Sidebar */
    .sidebar {
      height: 100vh;
      width: 250px;
      position: fixed;
      background-color: #006400; /* Dark green */
      color: white;
      padding-top: 20px;
      overflow-y: auto; /* Added to allow scrolling if list is long */
    }

    .sidebar h2 {
      text-align: center;
      margin-bottom: 30px;
    }

    .sidebar a,
    .dropdown-btn {
      padding: 15px 25px;
      display: block;
      color: white;
      text-decoration: none;
      background: none;
      border: none;
      width: 100%;
      text-align: left;
      cursor: pointer;
      font-size: 16px;
    }

    .sidebar a:hover,
    .dropdown-btn:hover {
      background-color: #228B22;
    }

    .dropdown-container {
      display: none;
      background-color: #2e8b57;
      padding-left: 20px;
    }

    .dropdown-container a {
      font-size: 15px;
    }

    /* Sub-dropdown styling for Sections */
    .sub-dropdown-container {
      display: none;
      background-color: #3cb371; 
      padding-left: 15px;
    }

    /* Content Area */
    .content {
      margin-left: 260px;
      padding: 30px;
      background-color: #ccffcc; /* Green background for content */
      min-height: 100vh;
    }

    .content img.logo {
      width: 120px;
      height: auto;
      display: block;
      margin: 0 auto 20px auto;
    }

    .content h1 {
      text-align: center;
      color: #004d00;
    }

    .content p {
      text-align: center;
      font-size: 18px;
    }

    /* Dropdown toggle class */
    .active {
      background-color: #228B22 !important;
    }
  </style>
</head>
<body>

  <div class="sidebar">
    <h2>Admin Dashboard</h2>

    <button class="dropdown-btn">Admission</button>
    <div class="dropdown-container">
      <a href="student_account.html">Document Verification</a>
      <a href="Faculty_Form.html">Aptitude Test Result</a>
      <a href="admin_exam_config.html">Send Exam Schedule</a>
    </div>

    <button class="dropdown-btn">Enrollment</button>
    <div class="dropdown-container">
      <a href="enrollement/enrollment_dashboard.php">📊 Enrollment Dashboard</a>
      <a href="enrollement/admin_enrollment.php">📋 Manage Documents</a>
      <a href="student_account.html">Student Portal</a>
      <a href="#l">Class Schedule</a>
      <a href="#l">LMS Payment Status</a>
      
      <button class="dropdown-btn">Official Enrolled â–¼</button>
      <div class="dropdown-container sub-dropdown-container">
        <a href="#">BSIT Section 1A-AJ</a>
        <a href="#">BSIT Section 2A-AJ</a>
        <a href="#">BSIT Section 3A-AJ</a>
        <a href="#">BSIT Section 4A-AJ</a>
      </div>

      <a href="#l">Faculty Loading</a>
    </div>
  </div>

  <div class="content">
    <img src="assets/Logo.png" alt="PTC Logo" class="logo">
    <h1>Welcome to the Admin Dashboard</h1>
    <p>Select an option from the sidebar to manage academic records and activities.</p>
  </div>

  <script>
    // Select all buttons with the dropdown-btn class
    const dropdownBtns = document.querySelectorAll(".dropdown-btn");

    dropdownBtns.forEach(btn => {
      btn.addEventListener("click", function () {
        this.classList.toggle("active");
        const dropdownContent = this.nextElementSibling;
        
        // Toggle the display property
        if (dropdownContent.style.display === "block") {
          dropdownContent.style.display = "none";
        } else {
          dropdownContent.style.display = "block";
        }
      });
    });
  </script>
  
</body>
</html>
