<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

redirectIfNotLoggedIn();
redirectIfNotRole('admin');

$message = '';

// Get all students for dropdown
$stmt = $pdo->query("SELECT id, name, username FROM users WHERE role = 'student' ORDER BY name");
$students = $stmt->fetchAll();

// Handle student selection and update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['select_student'])) {
        $student_id = intval($_POST['student_id']);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
    } elseif (isset($_POST['update_student'])) {
        $student_id = intval($_POST['student_id']);
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $division = trim($_POST['division']);
        $roll = trim($_POST['roll']);
        $password = trim($_POST['password']);

        if (!$name || !$username || !$division || !$roll) {
            $message = "All fields except password are required.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $student_id]);
            if ($stmt->rowCount() > 0) {
                $message = "Username already taken by another student.";
            } else {
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, division = ?, roll = ?, password = ? WHERE id = ? AND role = 'student'");
                    $stmt->execute([$name, $username, $division, $roll, $hashedPassword, $student_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, division = ?, roll = ? WHERE id = ? AND role = 'student'");
                    $stmt->execute([$name, $username, $division, $roll, $student_id]);
                }

                if ($stmt->rowCount()) {
                    $message = "Student updated successfully!";
                } else {
                    $message = "No changes made or update failed.";
                }
            }
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin-top: 50px;
        }
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #4e73df;
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 1.5rem;
        }
        .form-control, .form-select {
            padding: 10px;
            border-radius: 5px;
        }
        .btn-primary {
            background-color: #4e73df;
            border: none;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #3a5bc7;
        }
        .alert {
            border-radius: 5px;
        }
        .back-link {
            margin-top: 20px;
            text-align: center;
            color: #6c757d;
            display: block;
        }
        .back-link:hover {
            color: #4e73df;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header text-center">
            <h2><i class="fas fa-user-edit me-2"></i>Edit Student</h2>
        </div>
        <div class="card-body p-4">
            <?php if ($message): ?>
                <div class="alert <?= strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="row g-3 mb-4">
                <div class="col-md-8">
                    <select name="student_id" class="form-select" required>
                        <option value="">-- Select a student --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (isset($student) && $student['id'] == $s['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" name="select_student" class="btn btn-primary w-100">
                        <i class="fas fa-user-check me-2"></i>Load Student
                    </button>
                </div>
            </form>

            <?php if (isset($student) && $student): ?>
                <form method="post">
                    <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($student['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($student['username']) ?>" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="division" class="form-label">Division</label>
                            <input type="text" id="division" name="division" class="form-control" value="<?= htmlspecialchars($student['division']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="roll" class="form-label">Roll Number</label>
                            <input type="text" id="roll" name="roll" class="form-control" value="<?= htmlspecialchars($student['roll']) ?>" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter new password">
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="update_student" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Update Student
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <a href="index.php" class="back-link"><i class="fas fa-arrow-left me-1"></i> Back to Admin Dashboard</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
