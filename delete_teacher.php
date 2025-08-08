<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

redirectIfNotLoggedIn();
redirectIfNotRole('admin');

$message = '';
$error = '';

// Fetch all teachers
$stmt = $pdo->query("SELECT id, name, username FROM users WHERE role = 'teacher' ORDER BY name");
$teachers = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_teacher'])) {
        $delete_id = intval($_POST['teacher_id']);

        // Check teacher exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'teacher'");
        $stmt->execute([$delete_id]);
        $teacher = $stmt->fetch();

        if ($teacher) {
            // Delete teacher
            $del = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
            $del->execute([$delete_id]);
            $message = "Teacher deleted successfully.";

            // Refresh list after delete
            $stmt = $pdo->query("SELECT id, name, username FROM users WHERE role = 'teacher' ORDER BY name");
            $teachers = $stmt->fetchAll();
        } else {
            $error = "Teacher not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Teacher - Admin Panel</title>
    <style>
        :root {
            --primary-color: #3498db;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --light-gray: #f5f5f5;
            --dark-gray: #333;
            --border-radius: 4px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark-gray);
            background-color: var(--light-gray);
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: var(--border-radius);
        }
        
        h2 {
            color: var(--danger-color);
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .alert {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            box-sizing: border-box;
            background-color: white;
        }
        
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .teacher-count {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Delete Teacher</h2>
        
        <div class="teacher-count">
            <?= count($teachers) ?> teacher(s) found
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (count($teachers) > 0): ?>
            <form method="post" onsubmit="return confirmDelete();">
                <div class="form-group">
                    <label for="teacher_id">Select Teacher to Delete:</label>
                    <select id="teacher_id" name="teacher_id" required>
                        <option value="">-- Select a teacher --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>">
                                <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" name="delete_teacher" class="btn-danger">
                    Delete Teacher
                </button>
                <a href="index.php" class="back-link">← Back to Admin Dashboard</a>
            </form>
        <?php else: ?>
            <p>No teachers found to delete.</p>
            <a href="index.php" class="back-link">← Back to Admin Dashboard</a>
        <?php endif; ?>
    </div>

    <script>
        function confirmDelete() {
            const teacherSelect = document.getElementById('teacher_id');
            const teacherName = teacherSelect.options[teacherSelect.selectedIndex].text;
            
            return confirm(`Are you sure you want to delete "${teacherName}"? This action cannot be undone.`);
        }
    </script>
</body>
</html>