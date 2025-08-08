
<?php
include('../includes/auth.php');
include('../includes/db.php');
redirectIfNotLoggedIn();
redirectIfNotRole('teacher');

$teacher = $_SESSION['username'];

// Get all classes taught by this teacher for dropdown
$class_stmt = $pdo->prepare("SELECT DISTINCT class FROM attendance_sessions WHERE teacher = ? ORDER BY class");
$class_stmt->execute([$teacher]);
$all_classes = $class_stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle PDF download
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_pdf'])) {
    require('../libs/fpdf/fpdf.php');
    
    $subject = $_POST['subject'];
    $division = $_POST['division'];
    $class = $_POST['class'];

    // Get total sessions
    $sess_stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_sessions 
                               WHERE subject = ? AND teacher = ? AND division = ? AND class = ?");
    $sess_stmt->execute([$subject, $teacher, $division, $class]);
    $total_sessions = $sess_stmt->fetchColumn();

    $defaulters = [];
    if ($total_sessions > 0) {
        $stu_stmt = $pdo->prepare("SELECT username, name, roll FROM users 
                                  WHERE role = 'student' AND division = ? AND class = ?");
        $stu_stmt->execute([$division, $class]);
        $students = $stu_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($students as $stu) {
            $username = $stu['username'];
            $att_stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs 
                WHERE student_username = ? 
                AND session_id IN (
                    SELECT id FROM attendance_sessions 
                    WHERE subject = ? AND teacher = ? AND division = ? AND class = ?
                )");
            $att_stmt->execute([$username, $subject, $teacher, $division, $class]);
            $present = $att_stmt->fetchColumn();
            $percentage = ($present / $total_sessions) * 100;

            if ($percentage < 75) {
                $defaulters[] = [
                    'roll' => $stu['roll'],
                    'name' => $stu['name'],
                    'username' => $username,
                    'percentage' => round($percentage, 2)
                ];
            }
        }
    }

    // Create enhanced PDF with professional styling
    class PDF extends FPDF {
        private $title;
        private $subject;
        private $division;
        private $class;
        private $teacher;
        private $total_sessions;
        
        function __construct($title, $subject, $division, $class, $teacher, $total_sessions) {
            parent::__construct('P', 'mm', 'A4');
            $this->title = $title;
            $this->subject = $subject;
            $this->division = $division;
            $this->class = $class;
            $this->teacher = $teacher;
            $this->total_sessions = $total_sessions;
        }
        
        function Header() {
            // Logo
            $this->Image('../assets/images/logo.png', 15, 10, 25);
            
            // Header text
            $this->SetFont('Helvetica', 'B', 16);
            $this->SetTextColor(42, 82, 152);
            $this->Cell(0, 10, 'AttendEase - Attendance Defaulter Report', 0, 1, 'C');
            
            // Line
            $this->SetDrawColor(42, 82, 152);
            $this->SetLineWidth(0.5);
            $this->Line(15, 40, 195, 40);
            
            // Report details
            $this->SetFont('Helvetica', '', 10);
            $this->SetTextColor(100, 100, 100);
            $this->SetY(45);
            $this->Cell(0, 6, 'Generated: ' . date('F j, Y g:i A'), 0, 1, 'R');
            
            // Main title
            $this->SetFont('Helvetica', 'B', 14);
            $this->SetTextColor(42, 82, 152);
            $this->SetY(50);
            $this->Cell(0, 8, $this->title, 0, 1, 'C');
            
            // Subtitle
            $this->SetFont('Helvetica', 'I', 12);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 6, 'Attendance below 75%', 0, 1, 'C');
            
            // Metadata table
            $this->SetY(65);
            $this->SetFont('Helvetica', '', 10);
            $this->SetFillColor(240, 240, 240);
            $this->SetDrawColor(200, 200, 200);
            
            // Header row
            $this->SetFont('Helvetica', 'B', 10);
            $this->Cell(47.5, 8, 'Subject', 1, 0, 'C', true);
            $this->Cell(47.5, 8, 'Division', 1, 0, 'C', true);
            $this->Cell(47.5, 8, 'Class', 1, 0, 'C', true);
            $this->Cell(47.5, 8, 'Teacher', 1, 1, 'C', true);
            
            // Data row
            $this->SetFont('Helvetica', '', 10);
            $this->Cell(47.5, 8, $this->subject, 1, 0, 'C');
            $this->Cell(47.5, 8, $this->division, 1, 0, 'C');
            $this->Cell(47.5, 8, $this->class, 1, 0, 'C');
            $this->Cell(47.5, 8, $this->teacher, 1, 1, 'C');
            
            // Summary row
            $this->SetFont('Helvetica', 'B', 10);
            $this->Cell(95, 8, 'Total Sessions Conducted:', 1, 0, 'R', true);
            $this->SetFont('Helvetica', '', 10);
            $this->Cell(95, 8, $this->total_sessions, 1, 1, 'C');
            
            $this->Ln(10);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Helvetica', 'I', 8);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }
        
        function DefaulterTable($header, $data) {
            // Colors, line width and bold font
            $this->SetFillColor(42, 82, 152);
            $this->SetTextColor(255);
            $this->SetDrawColor(42, 82, 152);
            $this->SetLineWidth(0.3);
            $this->SetFont('Helvetica', 'B', 10);
            
            // Header
            $w = array(20, 60, 40, 30);
            for($i=0;$i<count($header);$i++) {
                $this->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
            }
            $this->Ln();
            
            // Color and font restoration
            $this->SetFillColor(240, 240, 240);
            $this->SetTextColor(0);
            $this->SetFont('Helvetica', '', 10);
            
            // Data
            $fill = false;
            foreach($data as $row) {
                $this->Cell($w[0], 8, $row['roll'], 'LR', 0, 'C', $fill);
                $this->Cell($w[1], 8, $row['name'], 'LR', 0, 'L', $fill);
                $this->Cell($w[2], 8, $row['username'], 'LR', 0, 'L', $fill);
                
                // Set color based on percentage
                if ($row['percentage'] < 50) {
                    $this->SetTextColor(220, 53, 69); // Red
                } elseif ($row['percentage'] < 65) {
                    $this->SetTextColor(255, 193, 7); // Yellow
                } else {
                    $this->SetTextColor(40, 167, 69); // Green
                }
                
                $this->Cell($w[3], 8, $row['percentage'].'%', 'LR', 0, 'C', $fill);
                $this->SetTextColor(0);
                $this->Ln();
                $fill = !$fill;
            }
            // Closing line
            $this->Cell(array_sum($w), 0, '', 'T');
        }
        
        function SummarySection($count) {
            $this->Ln(15);
            $this->SetFont('Helvetica', 'B', 12);
            $this->SetTextColor(42, 82, 152);
            $this->Cell(0, 8, 'Report Summary:', 0, 1);
            
            $this->SetFont('Helvetica', '', 10);
            $this->MultiCell(0, 6, "This report identifies students with attendance below 75% in {$this->subject} for class {$this->class} (Division {$this->division}). The report was generated on " . date('F j, Y') . " by {$this->teacher}.");
            
            $this->Ln(5);
            $this->SetFont('Helvetica', 'B', 10);
            $this->Cell(60, 8, 'Total Defaulters Identified:', 0, 0);
            $this->SetFont('Helvetica', '', 10);
            $this->Cell(0, 8, $count, 0, 1);
            
            $this->Ln(5);
            $this->SetFont('Helvetica', 'I', 10);
            $this->SetTextColor(100, 100, 100);
            $this->MultiCell(0, 6, "Note: Students with attendance below 75% may face restrictions in examinations as per university regulations. Please advise these students to improve their attendance.");
            
            // Add official stamp area
            $this->Ln(15);
            $this->SetFont('Helvetica', 'B', 10);
            $this->Cell(0, 8, 'For Office Use:', 0, 1);
            $this->SetFont('Helvetica', '', 10);
            $this->Cell(60, 20, 'Verified by:', 0, 0, 'C');
            $this->Cell(60, 20, 'Date:', 0, 0, 'C');
            $this->Cell(60, 20, 'Stamp:', 0, 1, 'C');
            $this->Line($this->GetX()+15, $this->GetY()-15, $this->GetX()+75, $this->GetY()-15);
            $this->Line($this->GetX()+85, $this->GetY()-15, $this->GetX()+145, $this->GetY()-15);
            $this->Line($this->GetX()+155, $this->GetY()-15, $this->GetX()+215, $this->GetY()-15);
        }
    }

    // Instantiate PDF with professional settings
    $title = "Defaulter List - {$subject} (Class {$class})";
    $pdf = new PDF($title, $subject, $division, $class, $_SESSION['name'], $total_sessions);
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 25);
    
    // Add table
    $header = array('Roll No', 'Name', 'Username', 'Attendance %');
    $pdf->DefaulterTable($header, $defaulters);
    
    // Add summary section
    $pdf->SummarySection(count($defaulters));
    
    // Output PDF
    $pdf->Output('D', "AttendEase_Defaulter_List_{$subject}_Class{$class}_Division{$division}.pdf");
    exit;
}

// Handle CSV download
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_csv'])) {
    $subject = $_POST['subject'];
    $division = $_POST['division'];
    $class = $_POST['class'];

    // Get total sessions
    $sess_stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_sessions 
                               WHERE subject = ? AND teacher = ? AND division = ? AND class = ?");
    $sess_stmt->execute([$subject, $teacher, $division, $class]);
    $total_sessions = $sess_stmt->fetchColumn();

    $defaulters = [];
    if ($total_sessions > 0) {
        $stu_stmt = $pdo->prepare("SELECT username, name, roll FROM users 
                                  WHERE role = 'student' AND division = ? AND class = ?");
        $stu_stmt->execute([$division, $class]);
        $students = $stu_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($students as $stu) {
            $username = $stu['username'];
            $att_stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs 
                WHERE student_username = ? 
                AND session_id IN (
                    SELECT id FROM attendance_sessions 
                    WHERE subject = ? AND teacher = ? AND division = ? AND class = ?
                )");
            $att_stmt->execute([$username, $subject, $teacher, $division, $class]);
            $present = $att_stmt->fetchColumn();
            $percentage = ($present / $total_sessions) * 100;

            if ($percentage < 75) {
                $defaulters[] = [
                    'roll' => $stu['roll'],
                    'name' => $stu['name'],
                    'username' => $username,
                    'percentage' => round($percentage, 2)
                ];
            }
        }
    }

    // Output CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=defaulter_list.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Roll No', 'Name', 'Username', 'Attendance %', 'Class', 'Division', 'Subject']);
    foreach ($defaulters as $d) {
        fputcsv($output, [$d['roll'], $d['name'], $d['username'], $d['percentage'], $class, $division, $subject]);
    }
    fclose($output);
    exit;
}

// If GET request, show the form and result
$subject = $_GET['subject'] ?? '';
$division = $_GET['division'] ?? '';
$class = $_GET['class'] ?? '';
$defaulters = [];
$total_sessions = 0;

if ($subject && $division && $class) {
    $sess_stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_sessions 
                               WHERE subject = ? AND teacher = ? AND division = ? AND class = ?");
    $sess_stmt->execute([$subject, $teacher, $division, $class]);
    $total_sessions = $sess_stmt->fetchColumn();

    if ($total_sessions > 0) {
        $stu_stmt = $pdo->prepare("SELECT username, name, roll FROM users 
                                  WHERE role = 'student' AND division = ? AND class = ?");
        $stu_stmt->execute([$division, $class]);
        $students = $stu_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($students as $stu) {
            $username = $stu['username'];
            $att_stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs 
                WHERE student_username = ? 
                AND session_id IN (
                    SELECT id FROM attendance_sessions 
                    WHERE subject = ? AND teacher = ? AND division = ? AND class = ?
                )");
            $att_stmt->execute([$username, $subject, $teacher, $division, $class]);
            $present = $att_stmt->fetchColumn();
            $percentage = ($present / $total_sessions) * 100;

            if ($percentage < 75) {
                $defaulters[] = [
                    'roll' => $stu['roll'],
                    'name' => $stu['name'],
                    'username' => $username,
                    'percentage' => round($percentage, 2)
                ];
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
    <title>Defaulter List | AttendEase</title>
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
        
        .form-control {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: #fcfcfc;
        }
        
        .form-control:focus {
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
        
        .btn-success {
            background-color: var(--success);
        }
        
        .btn-success:hover {
            background-color: #0d9f6e;
        }
        
        /* Stats Cards */
        .stats-container {
            display: flex;
            gap: 20px;
            margin: 2rem 0;
            flex-wrap: wrap;
        }
        
        .stat-card {
            flex: 1;
            min-width: 200px;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: var(--shadow-lg);
            text-align: center;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 1rem;
            color: var(--dark);
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            margin: 0.5rem 0;
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
        
        .percentage-low {
            color: var(--danger);
            font-weight: 600;
        }
        
        .percentage-medium {
            color: var(--warning);
            font-weight: 600;
        }
        
        .percentage-high {
            color: var(--success);
            font-weight: 600;
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
        
        .download-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            justify-content: flex-end;
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
            
            .stats-container {
                flex-direction: column;
            }
            
            .stat-card {
                min-width: 100%;
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
            
            .download-buttons {
                flex-direction: column;
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
                <h1 class="page-title"><i class="fas fa-user-clock"></i> Defaulter List Management</h1>
                <a href="../dashboard/teacher.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <!-- Filter Card -->
            <div class="card">
                <h3 class="section-title"><i class="fas fa-filter"></i> Filter Criteria</h3>
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" 
                               value="<?= htmlspecialchars($subject) ?>" required>
                    </div>
                    
                    <div class="filter-group">
                        <label for="division" class="form-label">Division</label>
                        <input type="text" class="form-control" id="division" name="division" 
                               value="<?= htmlspecialchars($division) ?>" required>
                    </div>
                    
                    <div class="filter-group">
                        <label for="class" class="form-label">Class</label>
                        <select class="form-control" id="class" name="class" required>
                            <option value="">Select Class</option>
                            <?php foreach ($all_classes as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>" <?= $class == $c ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group filter-buttons">
                        <button type="submit" class="btn">
                            <i class="fas fa-search"></i> Show Defaulters
                        </button>
                        <?php if ($subject || $division || $class): ?>
                            <a href="defaulter_list.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if ($subject && $division && $class): ?>
                <div class="card">
                    <div class="stats-container">
                        <div class="stat-card">
                            <h3>Total Sessions</h3>
                            <div class="stat-value"><?= $total_sessions ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <h3>Defaulters Found</h3>
                            <div class="stat-value"><?= count($defaulters) ?></div>
                        </div>
                    </div>

                    <?php if (count($defaulters) > 0): ?>
                        <div class="download-buttons">
                            <form method="post" action="defaulter_list.php">
                                <input type="hidden" name="subject" value="<?= htmlspecialchars($subject) ?>">
                                <input type="hidden" name="division" value="<?= htmlspecialchars($division) ?>">
                                <input type="hidden" name="class" value="<?= htmlspecialchars($class) ?>">
                                <input type="hidden" name="download_csv" value="1">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-file-csv"></i> Download CSV
                                </button>
                            </form>
                            <form method="post" action="defaulter_list.php">
                                <input type="hidden" name="subject" value="<?= htmlspecialchars($subject) ?>">
                                <input type="hidden" name="division" value="<?= htmlspecialchars($division) ?>">
                                <input type="hidden" name="class" value="<?= htmlspecialchars($class) ?>">
                                <input type="hidden" name="download_pdf" value="1">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-file-pdf"></i> Download PDF
                                </button>
                            </form>
                        </div>
                        
                        <h4 class="section-title"><i class="fas fa-exclamation-triangle"></i> Students with Attendance Below 75%</h4>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Roll No</th>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th class="text-end">Attendance %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($defaulters as $d): 
                                        $percentageClass = '';
                                        if ($d['percentage'] < 50) {
                                            $percentageClass = 'percentage-low';
                                        } elseif ($d['percentage'] < 65) {
                                            $percentageClass = 'percentage-medium';
                                        } else {
                                            $percentageClass = 'percentage-high';
                                        }
                                    ?>
                                    <tr>
                                        <td><?= $d['roll'] ?></td>
                                        <td><?= $d['name'] ?></td>
                                        <td><?= $d['username'] ?></td>
                                        <td class="text-end <?= $percentageClass ?>">
                                            <?= $d['percentage'] ?>%
                                                                                    </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-records">
                            <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--success); margin-bottom: 1rem;"></i>
                            <h3>No Defaulters Found</h3>
                            <p>All students in <?= htmlspecialchars($class) ?> (Division <?= htmlspecialchars($division) ?>) 
                            have attendance above 75% in <?= htmlspecialchars($subject) ?>.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="no-records">
                        <i class="fas fa-info-circle" style="font-size: 3rem; color: var(--info); margin-bottom: 1rem;"></i>
                        <h3>Select Filters to View Defaulters</h3>
                        <p>Please select a subject, division, and class to view students with attendance below 75%.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Simple client-side validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.filter-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const subject = document.getElementById('subject').value.trim();
                    const division = document.getElementById('division').value.trim();
                    const classSelect = document.getElementById('class').value;
                    
                    if (!subject || !division || !classSelect) {
                        e.preventDefault();
                        alert('Please fill in all filter fields');
                    }
                });
            }
        });
    </script>
</body>
</html>