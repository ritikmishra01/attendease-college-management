<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

redirectIfNotLoggedIn();
redirectIfNotRole('teacher');

$username = $_SESSION['username'];

// Handle material deletion
if (isset($_POST['delete_material'])) {
    $material_id = $_POST['material_id'];
    
    // First get the filename to delete from server
    $stmt = $pdo->prepare("SELECT filename FROM course_materials WHERE id = ?");
    $stmt->execute([$material_id]);
    $filename = $stmt->fetchColumn();
    
    if ($filename) {
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM course_materials WHERE id = ?");
        $stmt->execute([$material_id]);
        
        // Delete the file
        $filepath = "../uploads/" . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        $_SESSION['message'] = "Material deleted successfully!";
        $_SESSION['message_type'] = "success";
    }
}

// Fetch all course materials with Division information
$stmt = $pdo->prepare("SELECT * FROM course_materials ORDER BY uploaded_at DESC");
$stmt->execute();
$materials = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Course Materials</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">Manage Course Materials</h2>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>
    
    <div class="mb-3">
        <a href="upload_material.php" class="btn btn-primary">Upload New Material</a>
        <a href="../dashboard/teacher.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if (empty($materials)): ?>
        <p class="text-muted">No course materials available.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Division</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materials as $material): ?>
                    <tr>
                        <td><?= htmlspecialchars($material['title'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($material['subject'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($material['Division'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($material['uploaded_by'] ?? 'N/A') ?></td>
                        <td><?= isset($material['uploaded_at']) ? date("d M Y, h:i A", strtotime($material['uploaded_at'])) : 'N/A' ?></td>
                        <td>
                            <?php if (!empty($material['filename'])): ?>
                                <a href="../uploads/<?= htmlspecialchars($material['filename']) ?>" 
                                   class="btn btn-sm btn-success" target="_blank">Download</a>
                            <?php else: ?>
                                <span class="text-muted">No file</span>
                            <?php endif; ?>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="material_id" value="<?= $material['id'] ?? '' ?>">
                                <button type="submit" name="delete_material" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this material?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>