<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

redirectIfNotLoggedIn();
redirectIfNotRole('teacher');

$message = '';
$message_type = ''; // 'success' or 'danger'

// Get all subjects and classes taught by this teacher for dropdowns
$teacher = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT DISTINCT subject, class FROM attendance_sessions WHERE teacher = ? ORDER BY subject, class");
$stmt->execute([$teacher]);
$teacher_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare unique lists for dropdowns
$subjects = [];
$classes = [];
foreach ($teacher_courses as $course) {
    if (!in_array($course['subject'], $subjects)) {
        $subjects[] = $course['subject'];
    }
    if (!in_array($course['class'], $classes)) {
        $classes[] = $course['class'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $subject = trim($_POST['subject']);
    $class = trim($_POST['class']);
    $division = trim($_POST['division']);
    $uploaded_by = $teacher;

    // Validate inputs
    if (empty($title) || empty($subject) || empty($class) || empty($division) || !isset($_FILES['material'])) {
        $message = "All fields are required.";
        $message_type = 'danger';
    } elseif (!preg_match('/^[A-Za-z0-9\s\-_,\.]+$/', $title)) {
        $message = "Title contains invalid characters. Only letters, numbers, spaces, hyphens, underscores, commas and periods are allowed.";
        $message_type = 'danger';
    } elseif (!in_array($division, ['A', 'B', 'C'])) {
        $message = "Invalid division selected.";
        $message_type = 'danger';
    } else {
        $file = $_FILES['material'];
        
        // File validation
        $allowedTypes = ['application/pdf', 'application/msword', 
                        'application/vnd.ms-powerpoint', 'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'text/plain', 'image/jpeg', 'image/png'];
        
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = "File upload error: " . $file['error'];
            $message_type = 'danger';
        } elseif ($file['size'] > $maxFileSize) {
            $message = "File is too large. Maximum size is 10MB.";
            $message_type = 'danger';
        } elseif (!in_array($file['type'], $allowedTypes)) {
            $message = "Invalid file type. Allowed types: PDF, Word, Excel, PowerPoint, JPEG, PNG, TXT";
            $message_type = 'danger';
        } else {
            // Sanitize filename
            $filename = preg_replace('/[^A-Za-z0-9\.\-_]/', '_', basename($file['name']));
            $targetDir = '../uploads/course_materials/';
            $newFileName = time() . '_' . $filename;
            $targetPath = $targetDir . $newFileName;

            // Create uploads folder if not exists
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $stmt = $pdo->prepare("INSERT INTO course_materials (title, filename, original_filename, subject, class, division, uploaded_by, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $title,
                    $newFileName,
                    $filename,
                    $subject,
                    $class,
                    $division,
                    $uploaded_by,
                    $file['type'],
                    $file['size']
                ]);
                $message = "Material uploaded successfully!";
                $message_type = 'success';
                
                // Clear form on success
                $_POST = [];
            } else {
                $message = "Failed to upload file. Please try again.";
                $message_type = 'danger';
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
    <title>Upload Course Material | AttendEase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #2a5298;
            --primary-light: #3b6bc5;
            --primary-dark: #1e3c72;
            --light: #ffffff;
            --dark: #1a202c;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .header-wrapper {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }
        
        .upload-container {
            max-width: 800px;
            margin: -50px auto 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            position: relative;
            z-index: 2;
        }
        
        .page-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .file-input-container {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .file-input-container:hover {
            border-color: var(--primary);
            background-color: #f8faff;
        }
        
        .file-input-container.dragover {
            border-color: var(--primary);
            background-color: #e6f0ff;
        }
        
        .file-info {
            display: none;
            margin-top: 1rem;
            padding: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .btn-upload {
            background-color: var(--primary);
            border: none;
            padding: 0.5rem 1.5rem;
        }
        
        .btn-upload:hover {
            background-color: var(--primary-dark);
        }
        
        .brand {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
        }
        
        .logo {
            width: 50px;
            height: 50px;
            margin-right: 15px;
        }
        
        @media (max-width: 768px) {
            .header-wrapper {
                clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%);
            }
            
            .upload-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header-wrapper">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="../index.php" class="brand">
                    <img src="../assets/images/logo.png" alt="AttendEase Logo" class="logo">
                    <h1 class="h4 mb-0">AttendEase</h1>
                </a>
                <div class="d-flex align-items-center">
                    <span class="me-3 d-none d-sm-inline">Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Teacher') ?></span>
                    <a href="../dashboard/teacher.php" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="upload-container">
            <div class="page-header">
                <h2><i class="bi bi-cloud-arrow-up"></i> Upload Course Material</h2>
                <p class="text-muted">Share learning materials with your students</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                    <i class="bi <?= $message_type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Material Title *</label>
                        <input type="text" name="title" id="title" class="form-control" 
                               value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>" 
                               required>
                        <div class="form-text">A descriptive name for this material</div>
                    </div>

                    <div class="col-md-6">
                        <label for="subject" class="form-label">Subject *</label>
                        <input type="text" name="subject" id="subject" class="form-control" 
                               value="<?= isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : '' ?>" 
                               list="subjectList" required>
                        <datalist id="subjectList">
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?= htmlspecialchars($sub) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="col-md-4">
                        <label for="class" class="form-label">Class *</label>
                        <select name="class" id="class" class="form-select" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?= htmlspecialchars($cls) ?>" 
                                    <?= isset($_POST['class']) && $_POST['class'] === $cls ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cls) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="division" class="form-label">Division *</label>
                        <select name="division" id="division" class="form-select" required>
                            <option value="">-- Select Division --</option>
                            <option value="A" <?= isset($_POST['division']) && $_POST['division'] === 'A' ? 'selected' : '' ?>>Division A</option>
                            <option value="B" <?= isset($_POST['division']) && $_POST['division'] === 'B' ? 'selected' : '' ?>>Division B</option>
                            <option value="C" <?= isset($_POST['division']) && $_POST['division'] === 'C' ? 'selected' : '' ?>>Division C</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Uploaded By</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['name'] ?? 'Teacher') ?>" readonly>
                    </div>

                    <div class="col-12">
                        <label class="form-label">File *</label>
                        <div class="file-input-container" id="dropArea">
                            <i class="bi bi-file-earmark-arrow-up" style="font-size: 2rem; color: var(--primary);"></i>
                            <h5 class="my-2">Drag & drop your file here</h5>
                            <p class="text-muted">or click to browse</p>
                            <input type="file" name="material" id="material" class="d-none" required>
                            <div class="file-info" id="fileInfo">
                                <i class="bi bi-file-text"></i> <span id="fileName"></span>
                                <span class="badge bg-secondary ms-2" id="fileSize"></span>
                            </div>
                        </div>
                        <div class="form-text">Supported formats: PDF, Word, Excel, PowerPoint, images (max 10MB)</div>
                    </div>

                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <a href="../dashboard/teacher.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary btn-upload">
                                <i class="bi bi-upload"></i> Upload Material
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Drag and drop functionality
        const dropArea = document.getElementById('dropArea');
        const fileInput = document.getElementById('material');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        
        dropArea.addEventListener('click', () => fileInput.click());
        
        fileInput.addEventListener('change', handleFiles);
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropArea.classList.add('dragover');
        }
        
        function unhighlight() {
            dropArea.classList.remove('dragover');
        }
        
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            handleFiles();
        }
        
        function handleFiles() {
            const files = fileInput.files;
            if (files.length > 0) {
                const file = files[0];
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.style.display = 'block';
            }
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Form validation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const subject = document.getElementById('subject').value.trim();
            const classSelect = document.getElementById('class').value;
            const division = document.getElementById('division').value;
            const file = document.getElementById('material').files[0];
            
            if (!title || !subject || !classSelect || !division || !file) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (!title.replace(/\s/g, '').length) {
                e.preventDefault();
                alert('Please enter a valid title.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>