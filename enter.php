<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Only teacher access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacher = $_SESSION['username'];
$message = '';

// Fetch divisions and classes dynamically
$div_stmt = $pdo->query("SELECT DISTINCT division FROM users WHERE role = 'student' ORDER BY division");
$divisions = $div_stmt->fetchAll(PDO::FETCH_COLUMN);

$class_stmt = $pdo->query("SELECT DISTINCT class FROM users WHERE role = 'student' ORDER BY class");
$classes = $class_stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $exam_type = trim($_POST['exam_type']);
    $division = $_POST['division'] ?? '';
    $class = $_POST['class'] ?? '';
    $marksData = $_POST['marks'] ?? [];

    if (empty($subject) || empty($exam_type) || empty($division) || empty($class)) {
        $message = '<div class="alert alert-danger">Please fill all required fields.</div>';
    } else {
        try {
            $pdo->beginTransaction();
            
            foreach ($marksData as $student_username => $marks) {
                $marks = intval($marks);

                // Check if marks record exists
                $check = $pdo->prepare("SELECT id FROM marks WHERE student_username = ? AND subject = ? AND exam_type = ?");
                $check->execute([$student_username, $subject, $exam_type]);
                
                if ($check->rowCount() > 0) {
                    // Update marks
                    $update = $pdo->prepare("UPDATE marks SET marks = ?, teacher = ? WHERE student_username = ? AND subject = ? AND exam_type = ?");
                    $update->execute([$marks, $teacher, $student_username, $subject, $exam_type]);
                } else {
                    // Insert marks
                    $insert = $pdo->prepare("INSERT INTO marks (student_username, subject, exam_type, marks, teacher) VALUES (?, ?, ?, ?, ?)");
                    $insert->execute([$student_username, $subject, $exam_type, $marks, $teacher]);
                }
            }
            
            $pdo->commit();
            $message = '<div class="alert alert-success">Marks saved successfully!</div>';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = '<div class="alert alert-danger">Error saving marks: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Fetch students for selected division and class (if any)
$students = [];
if (!empty($_POST['division']) && !empty($_POST['class'])) {
    $stmt = $pdo->prepare("SELECT username, name, roll FROM users WHERE role = 'student' AND division = ? AND class = ? ORDER BY roll");
    $stmt->execute([$_POST['division'], $_POST['class']]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Marks | AttendEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2a5298;
            --primary-light: #3b6bc5;
            --primary-dark: #1e3c72;
            --light: #ffffff;
            --dark: #1a202c;
            --gray-100: #f8fafc;
            --gray-200: #f1f5f9;
            --gray-300: #e2e8f0;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            background-color: var(--gray-100);
            color: var(--dark);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        /* Impressive Header Design */
        .header-wrapper {
            position: relative;
            margin-bottom: 3rem;
            height: 220px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            position: relative;
            z-index: 2;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            background-color: var(--light);
            border-radius: 15px;
            padding: 0.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .site-name {
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--light);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1.25rem;
            border-radius: 30px;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-name {
            font-weight: 500;
            color: var(--light);
            font-size: 1rem;
        }
        
        /* Main Content */
        .main-content {
            position: relative;
            z-index: 3;
            margin-top: -50px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.8rem;
            color: var(--primary);
            font-weight: 600;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            transition: var(--transition);
            font-weight: 500;
            box-shadow: var(--card-shadow);
        }
        
        .back-link:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* Card */
        .card {
            background: var(--light);
            border-radius: 15px;
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: var(--transition);
            border: none;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: var(--primary);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.25rem 2rem;
            border-bottom: none;
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1rem;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 1.75rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 500;
            color: var(--dark);
            font-size: 1rem;
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: #fcfcfc;
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.2);
            background-color: #fff;
        }
        
        .btn {
            padding: 1rem 2rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .btn-secondary {
            background-color: var(--gray-300);
            color: var(--dark);
        }
        
        .btn-outline-secondary {
            background-color: transparent;
            border: 1px solid var(--gray-300);
            color: var(--dark);
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--gray-200);
        }
        
        .btn-success {
            background-color: var(--success);
        }
        
        .btn-success:hover {
            background-color: #0d9f6e;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        
        th {
            background-color: var(--primary);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 500;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        tr:nth-child(even) {
            background-color: var(--gray-100);
        }
        
        .marks-input {
            width: 80px;
            text-align: center;
            padding: 0.75rem;
        }
        
        .section-title {
            margin: 2rem 0 1rem;
            color: var(--primary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .filter-group {
            margin-bottom: 0;
        }
        
        .filter-buttons {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }
        
        .filter-buttons .btn {
            flex: 1;
        }
        
        .no-records {
            text-align: center;
            padding: 2rem;
            color: var(--dark);
            font-style: italic;
            background: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header-wrapper {
                height: 180px;
                clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%);
            }
            
            .header-content {
                padding: 1.5rem;
            }
            
            .logo {
                width: 50px;
                height: 50px;
            }
            
            .site-name {
                font-size: 1.5rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.5rem;
            }
            
            .back-link {
                width: 100%;
                justify-content: center;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .filter-buttons {
                flex-direction: column;
            }
            
            .filter-buttons .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .header-wrapper {
                height: 160px;
            }
            
            .logo {
                width: 45px;
                height: 45px;
            }
            
            .site-name {
                font-size: 1.3rem;
            }
            
            .user-info {
                padding: 0.5rem 1rem;
            }
            
            .user-avatar {
                width: 35px;
                height: 35px;
            }
            
            .user-name {
                font-size: 0.9rem;
            }
            
            .card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Impressive Header Section -->
    <div class="header-wrapper">
        <div class="header-content">
            <a href="../index.php" class="brand">
                <img src="../assets/images/logo.png" alt="AttendEase Logo" class="logo">
                <span class="site-name">AttendEase</span>
            </a>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-chalkboard-teacher" style="color: white; font-size: 1rem;"></i>
                </div>
                <span class="user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Teacher') ?></span>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title"><i class="fas fa-marker"></i> Marks Entry System</h1>
                <a href="../dashboard/teacher.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <?php echo $message; ?>

            <!-- Filter Card -->
            <div class="card">
                <h3 class="section-title"><i class="fas fa-filter"></i> Entry Criteria</h3>
                <form method="POST" class="filter-form">
                    <div class="filter-group">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" 
                               value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required>
                    </div>
                    
                    <div class="filter-group">
                        <label for="exam_type" class="form-label">Exam Type</label>
                        <select class="form-select" id="exam_type" name="exam_type" required>
                            <option value="">Select Exam Type</option>
                            <option value="Midterm" <?= (($_POST['exam_type'] ?? '') == 'Midterm' ? 'selected' : '') ?>>Midterm</option>
                            <option value="Final" <?= (($_POST['exam_type'] ?? '') == 'Final' ? 'selected' : '') ?>>Final</option>
                            <option value="Quiz" <?= (($_POST['exam_type'] ?? '') == 'Quiz' ? 'selected' : '') ?>>Quiz</option>
                            <option value="Assignment" <?= (($_POST['exam_type'] ?? '') == 'Assignment' ? 'selected' : '') ?>>Assignment</option>
                            <option value="Project" <?= (($_POST['exam_type'] ?? '') == 'Project' ? 'selected' : '') ?>>Project</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="division" class="form-label">Division</label>
                        <select class="form-select" id="division" name="division" required>
                            <option value="">Select Division</option>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?= htmlspecialchars($div) ?>" <?= (($_POST['division'] ?? '') == $div ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($div) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="class" class="form-label">Class</label>
                        <select class="form-select" id="class" name="class" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>" <?= (($_POST['class'] ?? '') == $c ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($c) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group filter-buttons">
                        <button type="submit" class="btn">
                            <i class="fas fa-search"></i> Show Students
                        </button>
                        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Reset
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if (!empty($_POST['division']) && !empty($_POST['class'])): ?>
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-users"></i> Students in <?= htmlspecialchars($_POST['class']) ?> (Division <?= htmlspecialchars($_POST['division']) ?>)
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="enter.php">
                            <input type="hidden" name="subject" value="<?= htmlspecialchars($_POST['subject']) ?>">
                            <input type="hidden" name="exam_type" value="<?= htmlspecialchars($_POST['exam_type']) ?>">
                            <input type="hidden" name="division" value="<?= htmlspecialchars($_POST['division']) ?>">
                            <input type="hidden" name="class" value="<?= htmlspecialchars($_POST['class']) ?>">

                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th width="15%">Roll No</th>
                                            <th width="45%">Student Name</th>
                                            <th width="40%">Marks (0-100)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $stu): ?>
                                            <?php
                                                // Get existing marks if any
                                                $stmt = $pdo->prepare("SELECT marks FROM marks WHERE student_username = ? AND subject = ? AND exam_type = ?");
                                                $stmt->execute([$stu['username'], $_POST['subject'], $_POST['exam_type']]);
                                                $existingMarks = $stmt->fetchColumn();
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($stu['roll']) ?></td>
                                                <td><?= htmlspecialchars($stu['name']) ?></td>
                                                <td>
                                                    <input type="number" class="form-control marks-input" 
                                                           name="marks[<?= htmlspecialchars($stu['username']) ?>]" 
                                                           min="0" max="100" 
                                                           value="<?= htmlspecialchars($existingMarks ?: '') ?>" 
                                                           required>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end mt-3 gap-2">
                                <button type="reset" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo"></i> Reset Marks
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Save All Marks
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="card">
                    <div class="no-records">
                        <i class="fas fa-info-circle" style="font-size: 3rem; color: var(--info); margin-bottom: 1rem;"></i>
                        <h3>No Students Found</h3>
                        <p>There are no students in <?= htmlspecialchars($_POST['class']) ?> (Division <?= htmlspecialchars($_POST['division']) ?>) or the class/division selection is invalid.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="no-records">
                        <i class="fas fa-info-circle" style="font-size: 3rem; color: var(--info); margin-bottom: 1rem;"></i>
                        <h3>Select Criteria to Begin</h3>
                        <p>Please select a subject, exam type, division, and class to view students and enter marks.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Input validation for marks
        document.addEventListener('DOMContentLoaded', function() {
            const marksInputs = document.querySelectorAll('.marks-input');
            marksInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.value < 0) this.value = 0;
                    if (this.value > 100) this.value = 100;
                });
            });
            
            // Auto-submit form when division or class changes (for initial load)
            const divisionSelect = document.getElementById('division');
            const classSelect = document.getElementById('class');
            
            if (divisionSelect && classSelect) {
                divisionSelect.addEventListener('change', function() {
                    if (this.value && document.getElementById('class').value) {
                        this.form.submit();
                    }
                });
                
                classSelect.addEventListener('change', function() {
                    if (this.value && document.getElementById('division').value) {
                        this.form.submit();
                    }
                });
            }
        });
    </script>
</body>
</html>