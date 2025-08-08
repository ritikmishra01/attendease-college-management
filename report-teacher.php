<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

redirectIfNotLoggedIn();
redirectIfNotRole('teacher');

// Fetch available classes and divisions
$classes = $pdo->query("SELECT DISTINCT class FROM users WHERE role = 'student' ORDER BY class")->fetchAll(PDO::FETCH_COLUMN);
$divisions = $pdo->query("SELECT DISTINCT division FROM users WHERE role = 'student' ORDER BY division")->fetchAll(PDO::FETCH_COLUMN);

$subject = $_GET['subject'] ?? '';
$exam_type = $_GET['exam_type'] ?? '';
$class = $_GET['class'] ?? '';
$division = $_GET['division'] ?? '';

$students = [];

if ($subject && $exam_type && $class && $division) {
    $stmt = $pdo->prepare("
        SELECT u.username, u.roll, u.name, m.marks
        FROM users u
        LEFT JOIN marks m ON u.username = m.student_username AND m.subject = ? AND m.exam_type = ?
        WHERE u.role = 'student' AND u.class = ? AND u.division = ?
        ORDER BY u.roll
    ");
    $stmt->execute([$subject, $exam_type, $class, $division]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Generate PDF if requested
if (isset($_GET['download'])) {
    require_once '../includes/tcpdf/tcpdf.php';
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('School Management System');
    $pdf->SetTitle('Marks Report');
    $pdf->SetSubject($subject . ' - ' . $exam_type);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', 'B', 16);
    
    // Title
    $pdf->Cell(0, 10, 'Marks Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $subject . ' - ' . $exam_type, 0, 1, 'C');
    $pdf->Cell(0, 10, 'Class: ' . $class . ' - Division: ' . $division, 0, 1, 'C');
    $pdf->Ln(10);
    
    // Create table header
    $pdf->SetFont('helvetica', 'B', 12);
    $html = '<table border="1" cellpadding="4">
        <tr>
            <th width="15%"><b>Roll No</b></th>
            <th width="55%"><b>Student Name</b></th>
            <th width="30%"><b>Marks</b></th>
        </tr>';
    
    // Add table rows
    $pdf->SetFont('helvetica', '', 10);
    foreach ($students as $student) {
        $html .= '<tr>
            <td>'.htmlspecialchars($student['roll']).'</td>
            <td>'.htmlspecialchars($student['name']).'</td>
            <td>'.(is_numeric($student['marks']) ? htmlspecialchars($student['marks']) : 'N/A').'</td>
        </tr>';
    }
    
    $html .= '</table>';
    
    // Output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output('marks_report_'.$class.'_'.$division.'_'.$subject.'_'.$exam_type.'.pdf', 'D');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks Report System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --light-gray: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .header {
            background-color: var(--secondary-color);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .table {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            font-weight: 500;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: var(--light-gray);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .back-btn {
            position: absolute;
            top: 1rem;
            left: 1rem;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: opacity 0.2s;
        }
        
        .back-btn:hover {
            opacity: 0.8;
            color: white;
        }
        
        .download-btn {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .download-btn:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        
        .report-title {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="../dashboard/teacher.php" class="back-btn">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>
        <div class="container text-center">
            <h1 class="mb-0"><i class="fas fa-file-alt me-2"></i>Marks Report System</h1>
        </div>
    </div>
    
    <div class="container">
        <div class="form-container">
            <h3 class="mb-4"><i class="fas fa-filter me-2"></i>Filter Students</h3>
            <form method="GET" action="report-teacher.php">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" 
                               value="<?= htmlspecialchars($subject) ?>" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="exam_type" class="form-label">Exam Type</label>
                        <select class="form-select" id="exam_type" name="exam_type" required>
                            <option value="">Select Exam Type</option>
                            <option value="Midterm" <?= $exam_type === 'Midterm' ? 'selected' : '' ?>>Midterm</option>
                            <option value="Final" <?= $exam_type === 'Final' ? 'selected' : '' ?>>Final</option>
                            <option value="Quiz" <?= $exam_type === 'Quiz' ? 'selected' : '' ?>>Quiz</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="class" class="form-label">Class</label>
                        <select class="form-select" id="class" name="class" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?= htmlspecialchars($cls) ?>" <?= $class === $cls ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cls) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="division" class="form-label">Division</label>
                        <select class="form-select" id="division" name="division" required>
                            <option value="">Select Division</option>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?= htmlspecialchars($div) ?>" <?= $division === $div ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($div) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>View Report
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if ($students): ?>
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="report-title">
                            <i class="fas fa-list-ol me-2"></i>
                            Marks Report
                        </h3>
                        <div class="text-muted">
                            <span class="me-3"><strong>Subject:</strong> <?= htmlspecialchars($subject) ?></span>
                            <span class="me-3"><strong>Exam Type:</strong> <?= htmlspecialchars($exam_type) ?></span>
                            <span class="me-3"><strong>Class:</strong> <?= htmlspecialchars($class) ?></span>
                            <span><strong>Division:</strong> <?= htmlspecialchars($division) ?></span>
                        </div>
                    </div>
                    <a href="?subject=<?= urlencode($subject) ?>&exam_type=<?= urlencode($exam_type) ?>&class=<?= urlencode($class) ?>&division=<?= urlencode($division) ?>&download=1" 
                       class="btn download-btn text-white">
                        <i class="fas fa-file-pdf me-2"></i>Download PDF
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Roll No</th>
                                <th>Student Name</th>
                                <th>Marks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $stu): ?>
                                <tr>
                                    <td><?= htmlspecialchars($stu['roll']) ?></td>
                                    <td><?= htmlspecialchars($stu['name']) ?></td>
                                    <td><?= is_numeric($stu['marks']) ? htmlspecialchars($stu['marks']) : 'N/A' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($subject && $exam_type && $class && $division): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-circle me-2"></i>No students found for the selected criteria.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>