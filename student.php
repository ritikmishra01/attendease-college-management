<?php
include('../includes/auth.php');
redirectIfNotLoggedIn();
redirectIfNotRole('student');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Dashboard | <?= htmlspecialchars($_SESSION['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --accent: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #ef233c;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7ff;
            color: var(--dark);
            min-height: 100vh;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            min-height: 100vh;
            position: fixed;
            width: 280px;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 30px;
            width: calc(100% - 280px);
        }
        
        .user-profile {
            text-align: center;
            padding: 30px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 15px;
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .user-role {
            font-size: 0.85rem;
            opacity: 0.8;
            background: rgba(255, 255, 255, 0.1);
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-item {
            margin-bottom: 5px;
            
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 25px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 3px solid white;
        }
        
        .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 12px;
            font-size: 1.1rem;
        }
        
        .logout-link {
            color: rgba(255, 255, 255, 0.6);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 10px;
        }
        
        .logout-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .dashboard-header {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title h1 {
            font-weight: 600;
            font-size: 1.8rem;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .page-title p {
            color: var(--gray);
            margin-bottom: 0;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .stats-card .icon.attendance {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }
        
        .stats-card .icon.marks {
            background: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }
        
        .stats-card .icon.courses {
            background: rgba(248, 150, 30, 0.1);
            color: var(--warning);
        }
        
        .stats-card h3 {
            font-weight: 600;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }
        
        .stats-card p {
            color: var(--gray);
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        
        .btn-custom {
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-primary-custom {
            background: var(--primary);
            border: none;
            color: white !important;
        }
        
        .btn-primary-custom:hover {
            background: var(--secondary);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
            color: white !important;
        }
        
        .recent-activities {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--card-shadow);
        }
        
        .activity-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(67, 97, 238, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary);
            font-size: 1rem;
        }
        
        .activity-content h4 {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .activity-content p {
            color: var(--gray);
            font-size: 0.85rem;
            margin-bottom: 0;
        }
        
        .activity-time {
            color: var(--gray);
            font-size: 0.8rem;
            margin-left: auto;
            white-space: nowrap;
        }
        
        /* White text for buttons */
        .btn-primary-custom, 
        .btn-primary-custom:hover,
        .btn-primary-custom:focus,
        .btn-outline-primary:hover,
        .btn-outline-primary:focus {
            color: white !important;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 240px;
            }
            
            .main-content {
                margin-left: 240px;
                width: calc(100% - 240px);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="user-profile">
           <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=random" class="user-avatar">
            <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
            <div class="user-role">Student</div>
        </div>
        
        <div class="nav-menu">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../attendance/mark.php" class="nav-link">
                    <i class="fas fa-calendar-check"></i>
                    <span>Mark Attendance</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../attendance/report-student.php" class="nav-link">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Attendance Report</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../marks/report-student.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Academic Performance</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../courses/view.php" class="nav-link">
                    <i class="fas fa-book-open"></i>
                    <span>My Courses</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../student/change_password.php" class="nav-link">
                    <i class="fas fa-key"></i>
                    <span>Change Password</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../logout.php" class="nav-link logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="dashboard-header">
            <div class="page-title">
                <h1>Student Dashboard</h1>
                <p>Welcome back, <?= htmlspecialchars($_SESSION['name']) ?></p>
            </div>
            <div class="date-info">
                <span class="badge bg-primary"><?= date('l, F j, Y') ?></span>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="icon attendance">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Attendance</h3>
                    <p>View and manage your class attendance records</p>
                    <a href="../attendance/report-student.php" class="btn btn-primary-custom btn-sm">View Report</a>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="icon marks">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Academic Performance</h3>
                    <p>Check your grades and academic progress</p>
                    <a href="../marks/report-student.php" class="btn btn-primary-custom btn-sm">View Marks</a>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="icon courses">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3>My Courses</h3>
                    <p>Access your current course materials</p>
                    <a href="../courses/view.php" class="btn btn-primary-custom btn-sm">View Courses</a>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="recent-activities">
                    <h3 class="mb-4">Recent Activities</h3>
                    
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="activity-content">
                            <h4>Attendance Marked</h4>
                            <p>You marked attendance for Database Systems class</p>
                        </div>
                        <div class="activity-time">2 hours ago</div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="activity-content">
                            <h4>Assignment Submitted</h4>
                            <p>Software Engineering assignment submitted</p>
                        </div>
                        <div class="activity-time">1 day ago</div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="activity-content">
                            <h4>Grade Updated</h4>
                            <p>Your midterm exam grade is now available</p>
                        </div>
                        <div class="activity-time">3 days ago</div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="activity-content">
                            <h4>New Announcement</h4>
                            <p>Exam schedule has been updated</p>
                        </div>
                        <div class="activity-time">5 days ago</div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="recent-activities">
                    <h3 class="mb-4">Quick Actions</h3>
                    
                    <a href="../attendance/mark.php" class="btn btn-primary-custom w-100 mb-3">
                        <i class="fas fa-calendar-check me-2"></i> Mark Today's Attendance
                    </a>
                    
                    <a href="../courses/view.php" class="btn btn-outline-primary w-100 mb-3">
                        <i class="fas fa-book-open me-2"></i> View Course Materials
                    </a>
                    
                    <a href="../student/change_password.php" class="btn btn-outline-secondary w-100 mb-3">
                        <i class="fas fa-key me-2"></i> Change Password
                    </a>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Upcoming:</strong> Midterm exams begin next week.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple script to highlight current page in navigation
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href').includes(currentPage)) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>