<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect if not logged in or not a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['username'];

// Fetch student name
$stmt = $pdo->prepare("SELECT name FROM users WHERE username = ?");
$stmt->execute([$username]);
$student = $stmt->fetch();

if (!$student) {
    echo "Student not found.";
    exit;
}

// Fetch all marks for this student
$stmt = $pdo->prepare("SELECT subject, exam_type, marks FROM marks WHERE student_username = ?");
$stmt->execute([$username]);
$marks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Marks</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #f8f9fa;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #f94144;
            --text-color: #2b2d42;
            --light-gray: #e9ecef;
            --border-radius: 10px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
        }
        
        h2 {
            color: var(--primary-color);
            margin: 0;
            font-weight: 600;
        }
        
        h3 {
            color: var(--text-color);
            font-weight: 500;
            margin-top: 0;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #3a56d4;
        }
        
        .back-link svg {
            margin-right: 8px;
        }
        
        .marks-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        tr:nth-child(even) {
            background-color: var(--secondary-color);
        }
        
        tr:hover {
            background-color: #edf2ff;
        }
        
        .marks-value {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .no-marks {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            font-style: italic;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .exam-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .midterm {
            background-color: #f8f3d0;
            color: #b38b00;
        }
        
        .final {
            background-color: #d0f8e0;
            color: #00b35c;
        }
        
        .quiz {
            background-color: #e0d0f8;
            color: #6a00b3;
        }
        
        .assignment {
            background-color: #f8d0e0;
            color: #b3005c;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            th, td {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2>Welcome, <?= htmlspecialchars($student['name']) ?>!</h2>
                <h3>Your Academic Performance</h3>
            </div>
            <a href="../dashboard/student.php" class="back-link">

                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Dashboard
            </a>
        </div>

        <div class="marks-card">
            <?php if (count($marks) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Exam Type</th>
                            <th>Marks Obtained</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($marks as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['subject']) ?></td>
                                <td>
                                    <span class="exam-badge <?= strtolower(htmlspecialchars($row['exam_type'])) ?>">
                                        <?= htmlspecialchars($row['exam_type']) ?>
                                    </span>
                                </td>
                                <td class="marks-value">
                                    <?= is_null($row['marks']) ? 'N/A' : htmlspecialchars($row['marks']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-marks">
                    <p>No marks records found yet.</p>
                    <p>Your marks will appear here once they are published by your instructors.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>