<?php
// create_teacher.php
$host = 'localhost';
$db   = 'college_attendance_system';
$user = 'root';
$pass = 'Root@01';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $name = 'Mr. Sharma';
    $username = 'teacher01';
    $plainPassword = 'teacher123';
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    $role = 'teacher';

    $stmt = $pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $username, $hashedPassword, $role]);

    echo "✅ Teacher user created successfully!";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
