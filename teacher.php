<?php
include('../includes/auth.php');
redirectIfNotLoggedIn();
redirectIfNotRole('teacher');

// Ensure session variables are set
if (!isset($_SESSION['name'])) {
    $_SESSION['name'] = 'Teacher';
}
if (!isset($_SESSION['email'])) {
    $_SESSION['email'] = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard | AttendEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2a5298;
            --primary-light: #3b6bc5;
            --primary-dark: #1e3c72;
            --accent: #2a5298;
            --light: #ffffff;
            --dark: #1a202c;
            --gray-100: #f8fafc;
            --gray-200: #f1f5f9;
            --gray-300: #e2e8f0;
            --gray-400: #cbd5e1;
            --gray-500: #94a3b8;
            --gray-600: #64748b;
            --gray-700: #475569;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            background-color: var(--gray-100);
            color: var(--gray-800);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        /* Curved Header Design */
        .header-wrapper {
            position: relative;
            margin-bottom: 3rem;
            height: 180px;
            overflow: hidden;
        }
        
        .header-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            border-radius: 0 0 40% 40% / 0 0 100px 100px;
            box-shadow: var(--card-shadow);
            z-index: 1;
        }
        
        .header-content {
            position: relative;
            z-index: 2;
            padding: 1.5rem 2rem 0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
        }
        
        .logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            background-color: var(--light);
            border-radius: 12px;
            padding: 0.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .site-name {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--light);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 30px;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-name {
            font-weight: 500;
            color: var(--light);
        }
        
        /* Navigation - Updated for better structure */
        nav {
            margin-bottom: 2rem;
            position: relative;
            z-index: 3;
            padding: 0 2rem;
        }
        
        .menu-toggle {
            display: none;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            cursor: pointer;
            font-weight: 500;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            width: 100%;
            justify-content: center;
        }
        
        .nav-menu {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            list-style: none;
            transition: var(--transition);
            background-color: var(--light);
            padding: 0.75rem;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }
        
        .nav-menu a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background-color: var(--gray-100);
            color: var(--gray-800);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            font-weight: 500;
            font-size: 0.85rem;
            border: 1px solid var(--gray-200);
        }
        
        .nav-menu a:hover {
            background-color: var(--primary-light);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-light);
        }
        
        .nav-menu a i {
            font-size: 0.9rem;
        }
        
        /* Section Headers with Filters */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .section-title {
            color: var(--primary);
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .filter-group {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-label {
            font-size: 0.85rem;
            color: var(--gray-600);
            font-weight: 500;
            white-space: nowrap;
        }
        
        .filter-input {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            font-size: 0.85rem;
            background-color: var(--light);
            transition: var(--transition);
            min-width: 150px;
        }
        
        .filter-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(42, 82, 152, 0.1);
        }
        
        /* Cards/Sections */
        .section {
            background: var(--light);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
            transition: var(--transition);
            position: relative;
            z-index: 2;
        }
        
        .section:hover {
            box-shadow: var(--shadow-lg);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--light);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-title {
            font-size: 0.9rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-description {
            font-size: 0.85rem;
            color: var(--gray-500);
        }
        
        .stat-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--primary);
            text-decoration: none;
            margin-top: 0.75rem;
            font-weight: 500;
        }
        
        .stat-link:hover {
            text-decoration: underline;
        }
        
        /* Activity List */
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .activity-icon.primary {
            color: var(--primary);
            background-color: rgba(42, 82, 152, 0.1);
        }
        
        .activity-icon.success {
            color: var(--success);
            background-color: rgba(16, 185, 129, 0.1);
        }
        
        .activity-icon.warning {
            color: var(--warning);
            background-color: rgba(245, 158, 11, 0.1);
        }
        
        .activity-content h4 {
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }
        
        .activity-content p {
            font-size: 0.85rem;
            color: var(--gray-600);
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .action-card {
            background: var(--light);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            text-align: center;
            border-top: 4px solid var(--primary);
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .action-icon {
            font-size: 1.75rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .action-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .action-description {
            font-size: 0.85rem;
            color: var(--gray-600);
            margin-bottom: 1rem;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .action-btn:hover {
            background-color: var(--primary-dark);
            color: white;
        }
        
        /* Materials List */
        .material-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-radius: 8px;
            background: var(--light);
            box-shadow: var(--card-shadow);
            margin-bottom: 1rem;
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }
        
        .material-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-lg);
        }
        
        .material-icon {
            font-size: 2rem;
            color: var(--primary);
        }
        
        .material-content {
            flex-grow: 1;
        }
        
        .material-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .material-description {
            font-size: 0.85rem;
            color: var(--gray-600);
        }
        
        .material-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        /* Upload Area */
        .upload-area {
            border: 2px dashed var(--gray-300);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background-color: var(--gray-100);
            margin-bottom: 1.5rem;
        }
        
        .upload-area:hover {
            border-color: var(--primary);
            background-color: rgba(42, 82, 152, 0.05);
        }
        
        .upload-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        /* Badges & Buttons */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-primary {
            background-color: rgba(42, 82, 152, 0.1);
            color: var(--primary);
        }
        
        .badge-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .badge-warning {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        .btn {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.4rem 0.6rem;
            font-size: 0.75rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .btn-success {
            background-color: var(--success);
            color: white;
        }
        
        .btn-warning {
            background-color: var(--warning);
            color: var(--dark);
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        /* Empty state */
        .empty-state {
            padding: 2rem 1rem;
            text-align: center;
        }
        
        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--gray-400);
        }
        
        .empty-state h3 {
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: var(--gray-500);
            font-size: 0.9rem;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--gray-200);
            padding-bottom: 0.5rem;
        }
        
        .tab {
            padding: 0.75rem 1.25rem;
            background-color: var(--gray-100);
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            border: 1px solid var(--gray-200);
            margin-bottom: -2px;
        }
        
        .tab:hover {
            background-color: var(--gray-200);
        }
        
        .tab.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .container {
                padding: 0 1rem;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .header-bg {
                border-radius: 0 0 30% 30% / 0 0 80px 80px;
            }
            
            .nav-menu a {
                flex-grow: 1;
                justify-content: center;
                text-align: center;
            }
        }
        
        @media (max-width: 768px) {
            .header-wrapper {
                height: 160px;
            }
            
            .header-content {
                padding: 1rem 1.5rem 0;
            }
            
            .user-info {
                padding: 0.4rem 0.8rem;
            }
            
            nav {
                padding: 0 1rem;
            }
            
            .menu-toggle {
                display: flex;
            }
            
            .nav-menu {
                flex-direction: column;
                max-height: 0;
                overflow: hidden;
                padding: 0;
                gap: 0.5rem;
            }
            
            .nav-menu.active {
                max-height: 500px;
                padding: 0.5rem;
            }
            
            .nav-menu a {
                width: 100%;
                justify-content: space-between;
            }
            
            .section {
                padding: 1.25rem;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .tab {
                flex-grow: 1;
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            .header-wrapper {
                height: 140px;
            }
            
            .header-bg {
                border-radius: 0 0 20% 20% / 0 0 60px 60px;
            }
            
            .logo {
                width: 40px;
                height: 40px;
            }
            
            .site-name {
                font-size: 1.25rem;
            }
            
            .user-avatar {
                width: 30px;
                height: 30px;
            }
            
            .user-name {
                font-size: 0.9rem;
            }
            
            .nav-menu a {
                font-size: 0.8rem;
                padding: 0.6rem 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="header-wrapper">
        <div class="header-bg"></div>
        <div class="header-content">
            <a href="../index.php" class="brand">
                <img src="../assets/images/logo.png" alt="AttendEase Logo" class="logo">
                <span class="site-name">AttendEase</span>
            </a>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-chalkboard-teacher" style="color: white; font-size: 0.9rem;"></i>
                </div>
                <span class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></span>
            </div>
        </div>
    </div>

    <div class="container">
        <nav>
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i> Menu
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="../attendance/session.php"><i class="fas fa-calendar-plus"></i> Create Session</a></li>
                <li><a href="../attendance/view_attendance.php"><i class="fas fa-clipboard-list"></i> Attendance Reports</a></li>
                <li><a href="../attendance/defaulter_list.php"><i class="fas fa-exclamation-triangle"></i> Defaulter List</a></li>
                <li><a href="../marks/enter.php"><i class="fas fa-edit"></i> Enter Marks</a></li>
                <li><a href="../marks/report-teacher.php"><i class="fas fa-chart-bar"></i> Marks Report</a></li>
                <li><a href="../courses/upload_material.php"><i class="fas fa-upload"></i> Upload Materials</a></li>
                <li><a href="../courses/teacher_view.php"><i class="fas fa-book"></i> View Materials</a></li>
                <li><a href="../teacher/change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <div class="tabs">
            <div class="tab active" data-tab="overview">Overview</div>
            <div class="tab" data-tab="materials">Teaching Materials</div>
            <div class="tab" data-tab="quickactions">Quick Actions</div>
        </div>

        <!-- Overview Tab -->
        <div class="tab-content active" id="overview">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3 class="stat-title">Attendance Tracking</h3>
                    <div class="stat-value">24 Sessions</div>
                    <p class="stat-description">Monitor and manage student attendance records</p>
                    <a href="../attendance/view_attendance.php" class="stat-link">
                        View Reports <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="stat-card">
                    <h3 class="stat-title">Student Performance</h3>
                    <div class="stat-value">85% Avg</div>
                    <p class="stat-description">Track and analyze student academic progress</p>
                    <a href="../marks/report-teacher.php" class="stat-link">
                        View Reports <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="stat-card">
                    <h3 class="stat-title">Teaching Resources</h3>
                    <div class="stat-value">12 Files</div>
                    <p class="stat-description">Educational materials shared with students</p>
                    <a href="../courses/teacher_view.php" class="stat-link">
                        View Materials <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-bell"></i> Recent Activity</h2>
                </div>
                
                <ul class="activity-list">
                    <li class="activity-item">
                        <div class="activity-icon primary">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="activity-content">
                            <h4>Attendance marked successfully</h4>
                            <p>Track your recent attendance sessions</p>
                        </div>
                    </li>
                    
                    <li class="activity-item">
                        <div class="activity-icon success">
                            <i class="fas fa-upload"></i>
                        </div>
                        <div class="activity-content">
                            <h4>New materials uploaded</h4>
                            <p>Students can now access your latest resources</p>
                        </div>
                    </li>
                    
                    <li class="activity-item">
                        <div class="activity-icon warning">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="activity-content">
                            <h4>Marks updated</h4>
                            <p>Review your recent grading activity</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Materials Tab -->
        <div class="tab-content" id="materials">
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-book"></i> Teaching Materials</h2>
                    <a href="../courses/upload_material.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Upload New
                    </a>
                </div>
                
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <h3>Drag & Drop files here</h3>
                    <p class="text-muted">or click to browse</p>
                    <input type="file" id="fileInput" style="display: none;" multiple>
                </div>
                
                <div class="material-item">
                    <div class="material-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="material-content">
                        <h3 class="material-title">Course Syllabus</h3>
                        <p class="material-description">Essential document outlining course objectives</p>
                    </div>
                    <div class="material-actions">
                        <a href="#" class="btn btn-primary btn-sm">
                            <i class="fas fa-download"></i>
                        </a>
                        <a href="#" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </div>
                </div>
                
                <div class="material-item">
                    <div class="material-icon">
                        <i class="fas fa-file-word"></i>
                    </div>
                    <div class="material-content">
                        <h3 class="material-title">Assignment Guidelines</h3>
                        <p class="material-description">Detailed instructions for student assignments</p>
                    </div>
                    <div class="material-actions">
                        <a href="#" class="btn btn-primary btn-sm">
                            <i class="fas fa-download"></i>
                        </a>
                        <a href="#" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Tab -->
        <div class="tab-content" id="quickactions">
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-bolt"></i> Quick Actions</h2>
                </div>
                
                <div class="quick-actions">
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <h3 class="action-title">New Attendance Session</h3>
                        <p class="action-description">Create a new attendance tracking session for your class</p>
                        <a href="../attendance/session.php" class="action-btn">
                            Create <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h3 class="action-title">Enter Marks</h3>
                        <p class="action-description">Record student marks and assessments</p>
                        <a href="../marks/enter.php" class="action-btn">
                            Enter <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 class="action-title">View Defaulters</h3>
                        <p class="action-description">Identify students with attendance issues</p>
                        <a href="../attendance/defaulter_list.php" class="action-btn">
                            View <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3 class="action-title">Generate Report</h3>
                        <p class="action-description">Create detailed academic reports</p>
                        <a href="../marks/report-teacher.php" class="action-btn">
                            Generate <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-upload"></i>
                        </div>
                        <h3 class="action-title">Upload Material</h3>
                        <p class="action-description">Share educational resources with students</p>
                        <a href="../courses/upload_material.php" class="action-btn">
                            Upload <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const menuToggle = document.getElementById('menuToggle');
            const navMenu = document.getElementById('navMenu');
            
            menuToggle.addEventListener('click', function() {
                navMenu.classList.toggle('active');
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            });
            
            // Tab functionality
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs and content
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // File upload feedback
            document.getElementById('fileInput').addEventListener('change', function(e) {
                if (this.files.length > 0) {
                    const uploadArea = document.querySelector('.upload-area');
                    const fileName = this.files[0].name;
                    uploadArea.innerHTML = `
                        <i class="fas fa-check-circle upload-icon" style="color: var(--success)"></i>
                        <h3>${fileName}</h3>
                        <p class="text-muted">Ready to upload</p>
                    `;
                }
            });
            
            // Highlight current nav item
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-menu a');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href').includes(currentPage)) {
                    link.style.backgroundColor = 'var(--primary-dark)';
                    link.style.color = 'white';
                    link.style.borderColor = 'var(--primary-dark)';
                }
            });
        });
    </script>
</body>
</html>