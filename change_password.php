<?php
session_start();
include('../includes/auth.php');
include('../includes/db.php');

redirectIfNotLoggedIn();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    echo "Access denied.";
    exit;
}

$username = $_SESSION['username'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif ($new !== $confirm) {
        $error = "New password and confirm password do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ? AND role = 'teacher'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($current, $user['password'])) {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
            $update->execute([$newHash, $username]);
            $success = "Password updated successfully!";
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password - Teacher</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 500px;">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>Change Password</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
