<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function redirectIfNotLoggedIn() {
    if (!isset($_SESSION['username'])) {
        header('Location: ../login.php');
        exit;
    }
}

function redirectIfNotRole($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header('Location: ../login.php');
        exit;
    }
}
?>
