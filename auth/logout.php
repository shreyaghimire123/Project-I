<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    log_audit($pdo, $_SESSION['user_id'], 'logout', 'User logged out');
}

session_unset();
session_destroy();
session_start();
flash('success', 'You have been logged out successfully.');
redirect(BASE_URL . '/auth/login.php');
?>
