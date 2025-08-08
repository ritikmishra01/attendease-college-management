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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id']);
    // Delete student record
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$student_id]);

    if ($stmt->rowCount()) {
        $message = "Student deleted successfully.";
        // Refresh students list after deletion
        $stmt = $pdo->query("SELECT id, name, username FROM users WHERE role = 'student' ORDER BY name");
        $students = $stmt->fetchAll();
    } else {
        $message = "Student not found or already deleted.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Student - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 800px;
            margin-top: 50px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .card-header {
            background-color: #dc3545;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1.5rem;
        }
        .form-select {
            padding: 10px;
            border-radius: 5px;
        }
        .btn-danger {
            background-color: #dc3545;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
        }
        .btn-danger:hover {
            background-color: #bb2d3b;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #6c757d;
        }
        .back-link:hover {
            color: #4e73df;
            text-decoration: none;
        }
        .alert {
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header text-center">
                <h2><i class="fas fa-user-times me-2"></i>Delete Student</h2>
            </div>
            <div class="card-body p-4">
                <?php if ($message): ?>
                    <div class="alert <?= strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <div class="mb-4">
                        <label for="studentSelect" class="form-label fw-bold">Select Student to Delete</label>
                        <select class="form-select" id="studentSelect" name="student_id" required>
                            <option value="">-- Select a student --</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['username']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-danger mt-1">
                            Warning: This action cannot be undone. All student data will be permanently deleted.
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger btn-lg" 
                                onclick="return confirm('Are you absolutely sure you want to delete this student? All associated data will be permanently lost.')">
                            <i class="fas fa-trash-alt me-2"></i>Delete Student
                        </button>
                    </div>
                </form>

                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left me-1"></i> Back to Admin Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>