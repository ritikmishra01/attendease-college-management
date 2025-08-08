<?php
// Include your DB connection file (adjust path if needed)
require 'includes/db.php';

// Admin user details
$name = 'Admin User';
$username = 'admin';
$password = 'admin123';  // Change to your desired password
$role = 'admin';

// Hash the password securely using bcrypt (default)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Prepare insert statement
    $stmt = $pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");

    // Execute with values
    $stmt->execute([$name, $username, $hashed_password, $role]);

    echo "Admin user created successfully!";
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo "Username already exists. Please try a different username.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
