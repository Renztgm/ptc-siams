<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Login - PTC</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #2e7d32;
      color: white;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 100vh;
    }
    .header {
      width: 100%;
      background-color: #1b5e20;
      text-align: center;
      padding: 30px 20px;
      box-sizing: border-box;
      border-bottom: 4px solid gold;
    }
    .header img { height: 100px; margin-bottom: 15px; }
    .header h1 { margin: 5px 0; font-size: 28px; }
    .login-container {
      background-color: white;
      color: black;
      padding: 40px;
      border-radius: 10px;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.4);
      margin: 40px 0;
    }
    .login-container h2 { color: #2e7d32; text-align: center; margin-bottom: 20px; }
    label { display: block; margin-top: 15px; font-weight: bold; }
    input { width: 100%; padding: 12px; margin-top: 8px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
    button {
      background-color: #2e7d32; color: white; border: none; padding: 12px;
      border-radius: 5px; cursor: pointer; width: 100%; margin-top: 25px;
      font-size: 16px; font-weight: bold; transition: 0.3s;
    }
    button:hover { background-color: #1b5e20; transform: translateY(-2px); }
    .footer { margin-top: 20px; font-size: 13px; color: gray; text-align: center; }
  </style>
</head>
<body>
  <div class="header">
    <img src="Logo.png" alt="PTC Logo">
    <h1>PATEROS TECHNOLOGICAL COLLEGE</h1>
    <p style="color: gold; font-size: 24px; font-weight: bold; margin-top: 10px;">SMART ACADEMIC MANAGEMENT SYSTEM</p>
  </div>
  <div class="login-container">
    <h2>Student Login</h2>
    <form action="login.php" method="POST">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" placeholder="Enter Username" required>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Enter Password" required>
      <button type="submit">Login</button>
    </form>
    <div class="footer">
      <p>Need help? Contact registrar@ptc.edu.ph</p>
    </div>
  </div>
</body>
</html>