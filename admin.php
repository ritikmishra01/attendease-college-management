<?php
include('../includes/auth.php');
include('../includes/db.php');
redirectIfNotLoggedIn();
redirectIfNotRole('admin');

// Fetch all teachers and students
$teachers = $pdo->query("SELECT * FROM users WHERE role = 'teacher' ORDER BY name")->fetchAll();
$students = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY division, roll")->fetchAll();

// Get unique divisions and classes for filter
$divisions = $pdo->query("SELECT DISTINCT division FROM users WHERE role = 'student' AND division IS NOT NULL ORDER BY division")->fetchAll(PDO::FETCH_COLUMN);
$classes = $pdo->query("SELECT DISTINCT class FROM users WHERE role = 'student' AND class IS NOT NULL ORDER BY class")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | AttendEase</title>
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
        
        /* Navigation - Updated for mobile */
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
            gap: 0.75rem;
            list-style: none;
            transition: var(--transition);
        }
        
        .nav-menu a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            font-weight: 500;
            font-size: 0.9rem;
            box-shadow: var(--card-shadow);
        }
        
        .nav-menu a:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
        
        /* Tables */
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.9rem;
        }
        
        th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            padding: 0.75rem 1rem;
            text-align: left;
            position: sticky;
            top: 0;
        }
        
        th:first-child {
            border-top-left-radius: 8px;
        }
        
        th:last-child {
            border-top-right-radius: 8px;
        }
        
        td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--gray-200);
            background-color: var(--light);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover td {
            background-color: var(--gray-50);
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
        
        .badge-teacher {
            background-color: rgba(42, 82, 152, 0.1);
            color: var(--primary);
        }
        
        .badge-student {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
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
        
        .btn-edit {
            background-color: var(--info);
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
        }
        
        .btn-delete {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #dc2626;
            transform: translateY(-1px);
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
        
        /* Responsive adjustments - Updated for mobile menu */
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
                padding: 0.5rem 0;
            }
            
            .nav-menu a {
                width: 100%;
                justify-content: space-between;
            }
            
            .section {
                padding: 1.25rem;
            }
            
            th, td {
                padding: 0.6rem;
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
            
            .actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .filter-input {
                width: 100%;
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
                    <i class="fas fa-user-cog" style="color: white; font-size: 0.9rem;"></i>
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
                <li><a href="add_teacher.php"><i class="fas fa-user-plus"></i> Add Teacher</a></li>
                <li><a href="add_student.php"><i class="fas fa-user-graduate"></i> Add Student</a></li>
                <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <div class="section">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-chalkboard-teacher"></i> Teachers</h2>
                <div class="filter-group">
                    <label for="teacher-search" class="filter-label">Search:</label>
                    <input type="text" id="teacher-search" class="filter-input" placeholder="Filter teachers...">
                </div>
            </div>
            <div class="table-responsive">
                <table id="teachers-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teachers)): ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <i class="fas fa-user-slash"></i>
                                    <h3>No Teachers Found</h3>
                                    <p>Add your first teacher using the "Add Teacher" button above</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><?= htmlspecialchars($teacher['id']) ?></td>
                                    <td><?= htmlspecialchars($teacher['name']) ?></td>
                                    <td>
                                        <span class="badge badge-teacher">
                                            <i class="fas fa-user-tie"></i> <?= htmlspecialchars($teacher['username']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($teacher['email'] ?? 'N/A') ?></td>
                                    <td class="actions">
                                        <a href="edit_teacher.php?username=<?= urlencode($teacher['username']) ?>" class="btn btn-edit btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="delete_teacher.php?username=<?= urlencode($teacher['username']) ?>" 
                                           class="btn btn-delete btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this teacher?')">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-user-graduate"></i> Students</h2>
                <div class="filter-group">
                    <label for="student-search" class="filter-label">Search:</label>
                    <input type="text" id="student-search" class="filter-input" placeholder="Filter students...">
                    <label for="division-filter" class="filter-label">Division:</label>
                    <select id="division-filter" class="filter-input">
                        <option value="">All Divisions</option>
                        <?php foreach ($divisions as $division): ?>
                            <option value="<?= htmlspecialchars($division) ?>"><?= htmlspecialchars($division) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="class-filter" class="filter-label">Class:</label>
                    <select id="class-filter" class="filter-input">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($class) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table id="students-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Class</th>
                            <th>Division</th>
                            <th>Roll No</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fas fa-user-graduate"></i>
                                    <h3>No Students Found</h3>
                                    <p>Add your first student using the "Add Student" button above</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr data-division="<?= htmlspecialchars($student['division']) ?>" data-class="<?= htmlspecialchars($student['class']) ?>">
                                    <td><?= htmlspecialchars($student['id']) ?></td>
                                    <td><?= htmlspecialchars($student['name']) ?></td>
                                    <td>
                                        <span class="badge badge-student">
                                            <i class="fas fa-user-graduate"></i> <?= htmlspecialchars($student['username']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($student['class']) ?></td>
                                    <td><?= htmlspecialchars($student['division']) ?></td>
                                    <td><?= htmlspecialchars($student['roll']) ?></td>
                                    <td class="actions">
                                        <a href="edit_student.php?username=<?= urlencode($student['username']) ?>" class="btn btn-edit btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="delete_student.php?username=<?= urlencode($student['username']) ?>" 
                                           class="btn btn-delete btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this student?')">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
            
            // Teacher search functionality
            const teacherSearch = document.getElementById('teacher-search');
            const teacherRows = document.querySelectorAll('#teachers-table tbody tr');
            
            teacherSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                teacherRows.forEach(row => {
                    const name = row.cells[1].textContent.toLowerCase();
                    const username = row.cells[2].textContent.toLowerCase();
                    const email = row.cells[3].textContent.toLowerCase();
                    
                    if (name.includes(searchTerm) || username.includes(searchTerm) || email.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Student search and filter functionality
            const studentSearch = document.getElementById('student-search');
            const divisionFilter = document.getElementById('division-filter');
            const classFilter = document.getElementById('class-filter');
            const studentRows = document.querySelectorAll('#students-table tbody tr');
            
            function filterStudents() {
                const searchTerm = studentSearch.value.toLowerCase();
                const division = divisionFilter.value;
                const classVal = classFilter.value;
                
                studentRows.forEach(row => {
                    const name = row.cells[1].textContent.toLowerCase();
                    const username = row.cells[2].textContent.toLowerCase();
                    const rowDivision = row.getAttribute('data-division');
                    const rowClass = row.getAttribute('data-class');
                    
                    const matchesSearch = name.includes(searchTerm) || username.includes(searchTerm);
                    const matchesDivision = division === '' || rowDivision === division;
                    const matchesClass = classVal === '' || rowClass === classVal;
                    
                    if (matchesSearch && matchesDivision && matchesClass) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            studentSearch.addEventListener('input', filterStudents);
            divisionFilter.addEventListener('change', filterStudents);
            classFilter.addEventListener('change', filterStudents);
            
            // Highlight current nav item
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-menu a');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href').includes(currentPage)) {
                    link.style.backgroundColor = 'var(--primary-dark)';
                }
            });
            
            // Add fade-in animation to tables
            const tables = document.querySelectorAll('table');
            tables.forEach(table => {
                table.style.opacity = '0';
                table.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    table.style.opacity = '1';
                }, 100);
            });
        });
    </script>
</body>
</html>