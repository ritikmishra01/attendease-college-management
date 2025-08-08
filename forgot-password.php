<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'includes/PHPMailer/src/Exception.php';
require 'includes/PHPMailer/src/PHPMailer.php';
require 'includes/PHPMailer/src/SMTP.php';
include('includes/db.php');

date_default_timezone_set('Asia/Kolkata');

$error = '';
$success = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);

    // STEP 1: Check if username exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && !empty($user['email'])) {
        $email = $user['email'];

        // STEP 2: Check for existing reset for THIS email
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND expires_at > NOW()");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            $error = "A reset link has already been sent to this account. Please check your email.";
        } else {
            $token = bin2hex(random_bytes(16));
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            // Delete old tokens for this email
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            // Insert new token
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires_at]);

            $resetLink = "http://localhost/college-attendance-system/reset-password.php?token=$token";

            // Send Email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ritikloq0@gmail.com'; // Replace with your Gmail
                $mail->Password = 'hltzacavzwthkcjp';     // Replace with app password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('ritikloq0@gmail.com', 'Academic Management System');
                $mail->addAddress($email, $user['name']);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "Hi <strong>{$user['name']}</strong>,<br><br>
                    Click the link below to reset your password:<br>
                    <a href='$resetLink'>$resetLink</a><br><br>
                    This link will expire in 30 minutes.";

                $mail->send();
                $success = "Password reset link has been sent to your registered email.";
            } catch (Exception $e) {
                $error = "Mailer Error: {$mail->ErrorInfo}";
            }
        }
    } else {
        // STEP 3: Username does not exist OR email missing
        $error = "Username not found in the system.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | Academic Management System</title>
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
    }
    
    .container {
      width: 100%;
      max-width: 480px;
      padding: 0 20px;
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
    
    .back-to-login {
      margin-top: 25px;
      font-size: 14px;
      color: #666;
    }
    
    .back-to-login a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 500;
    }
    
    .back-to-login a:hover {
      text-decoration: underline;
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
      
      <h2>Forgot Password?</h2>
      <p class="subtitle">Enter your username to receive a password reset link</p>
      
      <?php if ($success): ?>
        <div class="msg success"><?= htmlspecialchars($success) ?></div>
      <?php elseif ($error): ?>
        <div class="msg error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      
      <form method="POST">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>" required placeholder="Enter your username">
        </div>
        <button type="submit" class="btn">Send Reset Link</button>
      </form>
      
      <div class="back-to-login">
        Remember your password? <a href="login.php">Sign in</a>
      </div>
    </div>
  </div>
</body>
</html>
