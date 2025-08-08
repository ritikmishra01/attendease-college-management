<?php
date_default_timezone_set('Asia/Kolkata');
session_start();
include('includes/db.php');

$error = '';
$success = '';
$showForm = false;

// Get token from URL
$token = $_GET['token'] ?? '';

// Check if token exists and is valid
if (!$token) {
    $error = "Invalid or missing token.";
} else {
    // Fetch token data from DB
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $error = "Invalid or expired token.";
    } else {
        // Check if token expired
        if (strtotime($reset['expires_at']) < time()) {
            $error = "Token has expired. Please request a new password reset.";
        } else {
            $showForm = true;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$password || !$confirm_password) {
        $error = "Please fill in all fields.";
        $showForm = true;
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
        $showForm = true;
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
        $showForm = true;
    } else {
        // Password is valid and matches confirmation
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Get user email from token record
        $email = $reset['email'];

        // Update password in users table
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$password_hash, $email]);

        // Delete the token from password_resets table
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);

        $success = "Your password has been reset successfully! You can now <a href='login.php'>login</a>.";
        $showForm = false;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | Academic Management System</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #4361ee;
      --primary-dark: #3a56d4;
      --success-color: #4bb543;
      --error-color: #ff3333;
      --text-color: #2b2b2b;
      --light-gray: #f5f7fa;
      --border-color: #e1e5eb;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--light-gray);
      color: var(--text-color);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      line-height: 1.6;
      padding: 20px;
    }
    
    .container {
      width: 100%;
      max-width: 480px;
    }
    
    .card {
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      text-align: center;
      transition: all 0.3s ease;
    }
    
    .card:hover {
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
    }
    
    .logo {
      margin-bottom: 25px;
    }
    
    .logo img {
      height: 50px;
    }
    
    h2 {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 8px;
      color: var(--text-color);
    }
    
    .subtitle {
      font-size: 14px;
      color: #666;
      margin-bottom: 30px;
    }
    
    .form-group {
      margin-bottom: 20px;
      text-align: left;
      position: relative;
    }
    
    label {
      display: block;
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 8px;
      color: var(--text-color);
    }
    
    input {
      width: 100%;
      padding: 12px 16px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s;
    }
    
    input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }
    
    .password-toggle {
      position: absolute;
      right: 12px;
      top: 38px;
      transform: translateY(-50%);
      cursor: pointer;
      color: #666;
      font-size: 14px;
    }
    
    .btn {
      background: var(--primary-color);
      color: white;
      padding: 14px;
      border: none;
      border-radius: 8px;
      width: 100%;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s;
      margin-top: 10px;
    }
    
    .btn:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }
    
    .btn:active {
      transform: translateY(0);
    }
    
    .msg {
      margin: 20px 0;
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 14px;
      text-align: center;
    }
    
    .success {
      background-color: rgba(75, 181, 67, 0.1);
      color: var(--success-color);
      border: 1px solid var(--success-color);
    }
    
    .error {
      background-color: rgba(255, 51, 51, 0.1);
      color: var(--error-color);
      border: 1px solid var(--error-color);
    }
    
    .password-strength {
      margin-top: 5px;
      font-size: 12px;
      color: #666;
    }
    
    .strength-meter {
      height: 4px;
      background: #eee;
      border-radius: 2px;
      margin-top: 5px;
      overflow: hidden;
    }
    
    .strength-meter-fill {
      height: 100%;
      width: 0;
      background:rgb(255, 68, 68);
      transition: width 0.3s, background 0.3s;
    }
    
    @media (max-width: 480px) {
      .card {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="logo">
        <!-- Replace with your logo -->
        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="#4361ee" stroke-width="2"/>
          <path d="M12 16V16.01M12 8V12" stroke="#4361ee" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      
      <h2>Reset Your Password</h2>
      <p class="subtitle">Create a new secure password for your account</p>
      
      <?php if ($error): ?>
        <div class="msg error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="msg success"><?= $success ?></div>
      <?php endif; ?>
      
      <?php if ($showForm): ?>
        <form method="POST" id="resetForm">
          <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" name="password" id="password" required minlength="6" placeholder="Enter new password" />
            <span class="password-toggle" onclick="togglePassword('password')">Show</span>
            <div class="password-strength">Password strength: <span id="strength-text">Weak</span></div>
            <div class="strength-meter">
              <div class="strength-meter-fill" id="strength-meter"></div>
            </div>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required minlength="6" placeholder="Confirm your password" />
            <span class="password-toggle" onclick="togglePassword('confirm_password')">Show</span>
          </div>
          <button type="submit" class="btn">Reset Password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Toggle password visibility
    function togglePassword(id) {
      const input = document.getElementById(id);
      const toggle = input.nextElementSibling;
      
      if (input.type === 'password') {
        input.type = 'text';
        toggle.textContent = 'Hide';
      } else {
        input.type = 'password';
        toggle.textContent = 'Show';
      }
    }
    
    // Password strength indicator
    document.getElementById('password').addEventListener('input', function(e) {
      const password = e.target.value;
      const strengthText = document.getElementById('strength-text');
      const strengthMeter = document.getElementById('strength-meter');
      let strength = 0;
      
      // Check password length
      if (password.length >= 6) strength += 1;
      if (password.length >= 8) strength += 1;
      
      // Check for mixed case
      if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength += 1;
      
      // Check for numbers
      if (password.match(/([0-9])/)) strength += 1;
      
      // Check for special chars
      if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength += 1;
      
      // Update UI
      let strengthName = '';
      let strengthColor = '';
      
      switch(strength) {
        case 0:
        case 1:
          strengthName = 'Weak';
          strengthColor = '#ff4444';
          break;
        case 2:
          strengthName = 'Fair';
          strengthColor = '#ffbb33';
          break;
        case 3:
          strengthName = 'Good';
          strengthColor = '#00C851';
          break;
        case 4:
        case 5:
          strengthName = 'Strong';
          strengthColor = '#00C851';
          break;
      }
      
      strengthText.textContent = strengthName;
      strengthMeter.style.width = (strength * 20) + '%';
      strengthMeter.style.backgroundColor = strengthColor;
    });
  </script>
</body>
</html>