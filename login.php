<?php
session_start();
include('includes/db.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === '' || $password === '') {
        $error = "Please fill in both username and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                if ($user['role'] === 'admin') {
                    header('Location: admin/admin.php');
                } elseif ($user['role'] === 'teacher') {
                    header('Location: dashboard/teacher.php');
                } elseif ($user['role'] === 'student') {
                    header('Location: dashboard/student.php');
                }
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login | AttendEase</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #1e3c72, #2a5298);
      color: #212529;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 40px 20px;
      line-height: 1.6;
    }
    .login-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 6px 30px rgba(0, 0, 0, 0.15);
      width: 100%;
      max-width: 460px;
      padding: 40px;
      text-align: center;
    }
    .login-header img.logo {
      width: 80px;
      margin-bottom: 20px;
    }
    h1 {
      font-weight: 700;
      font-size: 2rem;
      color: #2a5298;
      margin-bottom: 8px;
    }
    .login-subtitle {
      color: #6c757d;
      font-size: 0.95rem;
      margin-bottom: 24px;
    }
    .form-group {
      margin-bottom: 20px;
      position: relative;
    }
    .form-control {
      width: 100%;
      padding: 12px 14px;
      font-size: 1rem;
      border: 1px solid #ced4da;
      border-radius: 10px;
      transition: border-color 0.3s;
    }
    .form-control:focus {
      border-color: #2a5298;
      outline: none;
      box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.2);
    }
    .toggle-password {
      position: absolute;
      top: 50%;
      right: 16px;
      transform: translateY(-50%);
      cursor: pointer;
      color: #6c757d;
    }
    .btn {
      width: 100%;
      padding: 14px;
      font-size: 1rem;
      font-weight: 600;
      border-radius: 10px;
      border: none;
      background-color: #2a5298;
      color: #fff;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    .btn:hover {
      background-color: #1e3c72;
    }
    .alert-danger {
      background-color: rgba(247, 37, 133, 0.1);
      border: 1px solid rgba(247, 37, 133, 0.2);
      color: #f72585;
      padding: 12px;
      margin-bottom: 16px;
      border-radius: 10px;
    }
    footer {
      margin-top: 30px;
      text-align: center;
      color: #f1f1f1;
      font-size: 0.85rem;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="login-header">
      <img src="assets/images/logo.png" alt="Logo" class="logo" />
      <h1>AttendEase</h1>
      <p class="login-subtitle">Digital Presence. Real Impact.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="login-form">
      <div class="form-group">
        <input type="text" class="form-control" name="username" placeholder="Username" required>
      </div>

      <div class="form-group">
        <input type="password" class="form-control" name="password" id="passwordInput" placeholder="Password" required>
        <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
      </div>

      <div style="text-align: right; margin-bottom: 20px;">
        <a href="forgot-password.php" style="font-size: 0.9rem; color: #2a5298; text-decoration: none;">Forgot Password?</a>
      </div>

      <button type="submit" class="btn">Login</button>
    </form>
  </div>

  <footer>
    <p>RITIK MISHRA - Committed to Academic Excellence</p>
    <p>&copy; <?= date('Y') ?> AttendEase. All rights reserved.</p>
  </footer>

  <script>
    const togglePassword = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("passwordInput");

    togglePassword.addEventListener("click", function () {
      const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
      this.classList.toggle("fa-eye-slash");
    });
  </script>
</body>
</html>
