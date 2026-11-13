<?php
// Application entry point — sends visitors to the right place.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: views/" . (($_SESSION['role'] ?? '') === 'admin' ? "admin.php" : "user.php"));
} else {
    header("Location: views/auth/login.php");
}
exit;
