<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
redirectIfNotLoggedIn();
redirectIfNotRole('student');

$student = $_SESSION['username'];

// Fetch student's division
$stmt = $pdo->prepare("SELECT division FROM users WHERE username = ?");
$stmt->execute([$student]);
$student_division = $stmt->fetchColumn();

// Fetch subjects student has attendance logs for
$stmt = $pdo->prepare("SELECT DISTINCT subject FROM attendance_logs WHERE student_username = ?");
$stmt->execute([$student]);
$subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);

$selected_subject = $_GET['subject'] ?? '';

$total_sessions = 0;
$total_present = 0;
$percentage = 0;
$present_logs = [];
$denied_logs = [];

if ($selected_subject) {
    // Only count sessions for the student's division and selected subject
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_sessions WHERE subject = ? AND division = ?");
    $stmt->execute([$selected_subject, $student_division]);
    $total_sessions = $stmt->fetchColumn();

    // Fetch present logs
    $stmt = $pdo->prepare("SELECT timestamp FROM attendance_logs WHERE student_username = ? AND subject = ?");
    $stmt->execute([$student, $selected_subject]);
    $present_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_present = count($present_logs);

    // Fetch denied logs
    $stmt = $pdo->prepare("SELECT reason, timestamp FROM attendance_denied WHERE student_username = ? AND subject = ?");
    $stmt->execute([$student, $selected_subject]);
    $denied_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate percentage
    $percentage = $total_sessions > 0 ? round(($total_present / $total_sessions) * 100, 2) : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report | AttendEase</title>
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
        
        .alert-error {
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
            color: var(--primary);
        }
        
        .percentage {
            color: var(--success);
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
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .present-badge {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .denied-badge {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
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
                    <i class="fas fa-user-graduate" style="color: white; font-size: 1rem;"></i>
                </div>
                <span class="user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Student') ?></span>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Attendance Report</h1>
                <a href="../dashboard/student.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <div class="card">
                <form method="get" action="">
                    <div class="form-group">
                        <label for="subject" class="form-label">Select Subject</label>
                        <select name="subject" id="subject" class="form-control" required>
                            <option value="">-- Select a Subject --</option>
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?= htmlspecialchars($sub) ?>" <?= $selected_subject == $sub ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sub) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fas fa-chart-bar"></i> Generate Report
                    </button>
                </form>
            </div>

            <?php if ($selected_subject): ?>
                <div class="stats-container">
                    <div class="stat-card">
                        <h3>Subject</h3>
                        <div class="stat-value"><?= htmlspecialchars($selected_subject) ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Total Sessions</h3>
                        <div class="stat-value"><?= $total_sessions ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Present Count</h3>
                        <div class="stat-value"><?= $total_present ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Attendance Percentage</h3>
                        <div class="stat-value percentage"><?= $percentage ?>%</div>
                    </div>
                </div>
                
                <div class="card">
                    <h3 class="section-title"><i class="fas fa-check-circle"></i> Present Records</h3>
                    
                    <?php if ($present_logs): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($present_logs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['timestamp']) ?></td>
                                        <td><span class="badge present-badge">Present</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-records">No attendance records found for this subject.</div>
                    <?php endif; ?>
                    
                    <h3 class="section-title" style="margin-top: 2.5rem;"><i class="fas fa-times-circle"></i> Denied Records</h3>
                    <?php if ($denied_logs): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($denied_logs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['timestamp']) ?></td>
                                        <td><?= htmlspecialchars($log['reason']) ?></td>
                                        <td><span class="badge denied-badge">Denied</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-records">No denied attendance records found for this subject.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>