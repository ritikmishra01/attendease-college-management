<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

redirectIfNotLoggedIn();
redirectIfNotRole('admin');

$message = '';
$message_type = '';

// Fetch all teachers for selection dropdown
$stmt = $pdo->query("SELECT id, name, username FROM users WHERE role = 'teacher' ORDER BY name");
$teachers = $stmt->fetchAll();

// Variables for the form fields
$edit_id = null;
$name = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['select_teacher'])) {
        // When teacher selected from dropdown - load their data
        $edit_id = intval($_POST['teacher_id']);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'teacher'");
        $stmt->execute([$edit_id]);
        $teacher = $stmt->fetch();

        if ($teacher) {
            $name = $teacher['name'];
            $username = $teacher['username'];
            $message = "Teacher loaded. You can now edit their details.";
            $message_type = 'info';
        } else {
            $message = "Teacher not found.";
            $message_type = 'error';
        }
    } elseif (isset($_POST['update_teacher'])) {
        // When update form submitted
        $edit_id = intval($_POST['edit_id']);
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $password = $_POST['password']; // optional, empty means no change

        if (empty($name) || empty($username)) {
            $message = "Name and username are required.";
            $message_type = 'error';
        } else {
            // Check if username already exists for other user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $edit_id]);
            if ($stmt->rowCount() > 0) {
                $message = "Username already taken by another user.";
                $message_type = 'error';
            } else {
                if (!empty($password)) {
                    // Update with new password hash
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, password = ? WHERE id = ? AND role = 'teacher'");
                    $stmt->execute([$name, $username, $hash, $edit_id]);
                } else {
                    // Update without password change
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ? WHERE id = ? AND role = 'teacher'");
                    $stmt->execute([$name, $username, $edit_id]);
                }
                $message = "Teacher updated successfully.";
                $message_type = 'success';
                // Refresh teachers list
                $stmt = $pdo->query("SELECT id, name, username FROM users WHERE role = 'teacher' ORDER BY name");
                $teachers = $stmt->fetchAll();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Teacher - Admin Panel</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
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
            color: var(--primary-color);
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
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        select, input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            box-sizing: border-box;
        }
        
        select:focus, input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }
        
        .btn {
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
        }
        
        .btn-secondary:hover {
            background-color: #27ae60;
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
        
        .divider {
            border-top: 1px solid #eee;
            margin: 20px 0;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section-title {
            font-size: 18px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Teacher</h2>
        
        <div class="teacher-count">
            <?= count($teachers) ?> teacher(s) available for editing
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= 
                $message_type === 'success' ? 'success' : 
                ($message_type === 'error' ? 'error' : 'info') 
            ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="form-section">
            <div class="form-section-title">1. Select Teacher</div>
            <form method="post" action="">
                <div class="form-group">
                    <label for="teacher_id">Choose a teacher to edit:</label>
                    <select id="teacher_id" name="teacher_id" required>
                        <option value="">-- Select a teacher --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($t['id'] == $edit_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="select_teacher" class="btn btn-primary">
                    Load Teacher Details
                </button>
            </form>
        </div>

        <?php if ($edit_id): ?>
            <div class="divider"></div>
            
            <div class="form-section">
                <div class="form-section-title">2. Edit Teacher Details</div>
                <form method="post" action="">
                    <input type="hidden" name="edit_id" value="<?= $edit_id ?>">
                    
                    <div class="form-group">
                        <label for="name">Full Name:</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">New Password (leave blank to keep current):</label>
                        <input type="password" id="password" name="password" placeholder="Enter new password if changing">
                        <small style="color: #666;">Password must be at least 8 characters long</small>
                    </div>

                    <button type="submit" name="update_teacher" class="btn btn-secondary">
                        Update Teacher
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <a href="index.php" class="back-link">← Back to Admin Dashboard</a>
    </div>
</body>
</html>