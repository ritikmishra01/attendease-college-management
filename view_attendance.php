<?php
include('../includes/auth.php');
include('../includes/db.php');
redirectIfNotLoggedIn();
redirectIfNotRole('teacher');

$teacher = $_SESSION['username'];

// Capture filter inputs
$filter_class = $_GET['class'] ?? '';
$filter_subject = $_GET['subject'] ?? '';
$filter_date = $_GET['date'] ?? '';
$filter_division = $_GET['division'] ?? '';

// Modify SQL with filters
$query = "SELECT * FROM attendance_sessions WHERE teacher = ?";
$params = [$teacher];

if (!empty($filter_class)) {
    $query .= " AND class LIKE ?";
    $params[] = '%' . $filter_class . '%';
}
if (!empty($filter_subject)) {
    $query .= " AND subject LIKE ?";
    $params[] = '%' . $filter_subject . '%';
}
if (!empty($filter_date)) {
    $query .= " AND DATE(created_at) = ?";
    $params[] = $filter_date;
}
if (!empty($filter_division)) {
    $query .= " AND division = ?";
    $params[] = $filter_division;
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

// Get all unique classes, subjects and divisions for filter dropdowns
$classes_stmt = $pdo->prepare("SELECT DISTINCT class FROM attendance_sessions WHERE teacher = ? ORDER BY class");
$classes_stmt->execute([$teacher]);
$all_classes = $classes_stmt->fetchAll(PDO::FETCH_COLUMN);

$subjects_stmt = $pdo->prepare("SELECT DISTINCT subject FROM attendance_sessions WHERE teacher = ? ORDER BY subject");
$subjects_stmt->execute([$teacher]);
$all_subjects = $subjects_stmt->fetchAll(PDO::FETCH_COLUMN);

$divisions_stmt = $pdo->prepare("SELECT DISTINCT division FROM attendance_sessions WHERE teacher = ? ORDER BY division");
$divisions_stmt->execute([$teacher]);
$all_divisions = $divisions_stmt->fetchAll(PDO::FETCH_COLUMN);
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
        
        .btn-secondary {
            background-color: var(--gray-300);
            color: var(--dark);
        }
        
        /* Session Card */
        .session-header {
            background-color: var(--primary);
            color: white;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .session-code {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-weight: 500;
        }
        
        .session-meta {
            color: var(--gray-300);
            font-size: 0.9rem;
            margin-top: 0.5rem;
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
        
        .stat-present {
            color: var(--success);
        }
        
        .stat-denied {
            color: var(--danger);
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
            
            .session-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
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
                <h1 class="page-title">Attendance Sessions Report</h1>
                <a href="../dashboard/teacher.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <!-- Filter Card -->
            <div class="card">
                <h3 class="section-title"><i class="fas fa-filter"></i> Filter Sessions</h3>
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="class" class="form-label">Class</label>
                        <select name="class" id="class" class="form-control">
                            <option value="">All Classes</option>
                            <?php foreach ($all_classes as $class): ?>
                                <option value="<?= htmlspecialchars($class) ?>" <?= $filter_class == $class ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="subject" class="form-label">Subject</label>
                        <select name="subject" id="subject" class="form-control">
                            <option value="">All Subjects</option>
                            <?php foreach ($all_subjects as $subject): ?>
                                <option value="<?= htmlspecialchars($subject) ?>" <?= $filter_subject == $subject ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="division" class="form-label">Division</label>
                        <select name="division" id="division" class="form-control">
                            <option value="">All Divisions</option>
                            <?php foreach ($all_divisions as $division): ?>
                                <option value="<?= htmlspecialchars($division) ?>" <?= $filter_division == $division ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($division) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
                    </div>
                    
                    <div class="filter-group filter-buttons">
                        <button type="submit" class="btn">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <?php if ($filter_class || $filter_subject || $filter_date || $filter_division): ?>
                            <a href="report.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if (empty($sessions)): ?>
                <div class="card no-records">
                    <i class="fas fa-calendar-times fa-3x mb-3" style="color: var(--gray-300);"></i>
                    <h3>No Attendance Sessions Found</h3>
                    <p class="text-muted">You haven't created any attendance sessions yet or no sessions match your filters.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($sessions as $session): ?>
                <div class="card session-card">
                    <div class="session-header">
                        <div>
                            <h3><?= htmlspecialchars($session['class']) ?> - <?= htmlspecialchars($session['subject']) ?> - Division <?= htmlspecialchars($session['division']) ?></h3>
                            <div class="session-meta">
                                <i class="far fa-calendar-alt"></i> <?= date('F j, Y g:i A', strtotime($session['created_at'])) ?>
                                <span style="margin: 0 10px">•</span>
                                <i class="fas fa-map-marker-alt"></i> <?= round($session['latitude'], 4) ?>, <?= round($session['longitude'], 4) ?>
                            </div>
                        </div>
                        <div class="session-code">
                            <i class="fas fa-hashtag"></i> <?= htmlspecialchars($session['code']) ?>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="stats-container">
                            <div class="stat-card">
                                <h3>Present Students</h3>
                                <?php
                                $sid = $session['id'];
                                $present_count = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE session_id = ?");
                                $present_count->execute([$sid]);
                                $present = $present_count->fetchColumn();
                                ?>
                                <div class="stat-value stat-present"><?= $present ?></div>
                            </div>
                            
                            <div class="stat-card">
                                <h3>Denied Students</h3>
                                <?php
                                $denied_count = $pdo->prepare("SELECT COUNT(*) FROM attendance_denied WHERE session_id = ?");
                                $denied_count->execute([$sid]);
                                $denied = $denied_count->fetchColumn();
                                ?>
                                <div class="stat-value stat-denied"><?= $denied ?></div>
                            </div>
                            
                            <div class="stat-card">
                                <h3>Total Attendance</h3>
                                <div class="stat-value"><?= $present + $denied ?></div>
                            </div>
                        </div>

                        <h4 class="section-title"><i class="fas fa-check-circle text-success"></i> Present Students</h4>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Roll No</th>
                                        <th>Username</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $success = $pdo->prepare("SELECT * FROM attendance_logs WHERE session_id = ?");
                                    $success->execute([$sid]);
                                    $records = $success->fetchAll();
                                    foreach ($records as $s): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($s['roll_number']) ?></td>
                                            <td><?= htmlspecialchars($s['student_username']) ?></td>
                                            <td><?= date('M j, g:i A', strtotime($s['timestamp'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($records) == 0): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">No students marked as present</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <h4 class="section-title" style="margin-top: 2.5rem;"><i class="fas fa-times-circle text-danger"></i> Denied Students</h4>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Roll No</th>
                                        <th>Username</th>
                                        <th>Reason</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $denied_result = $pdo->prepare("SELECT * FROM attendance_denied WHERE session_id = ?");
                                    $denied_result->execute([$sid]);
                                    $records = $denied_result->fetchAll();
                                    foreach ($records as $d): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($d['roll_number']) ?></td>
                                            <td><?= htmlspecialchars($d['student_username']) ?></td>
                                            <td title="<?= htmlspecialchars($d['reason']) ?>"><?= htmlspecialchars(substr($d['reason'], 0, 30)) ?><?= strlen($d['reason']) > 30 ? '...' : '' ?></td>
                                            <td><?= date('M j, g:i A', strtotime($d['timestamp'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($records) == 0): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">No students were denied</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Tooltip for long reasons
        document.addEventListener('DOMContentLoaded', function() {
            const reasonCells = document.querySelectorAll('td[title]');
            reasonCells.forEach(cell => {
                cell.addEventListener('mouseenter', function() {
                    if (this.offsetWidth < this.scrollWidth) {
                        this.setAttribute('data-tooltip', this.getAttribute('title'));
                    }
                });
                cell.addEventListener('mouseleave', function() {
                    this.removeAttribute('data-tooltip');
                });
            });
        });
    </script>
</body>
</html>