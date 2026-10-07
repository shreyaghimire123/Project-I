<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Basic session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    flash('error', 'Session expired. Please login again.');
    redirect(BASE_URL . '/auth/login.php');
}
$_SESSION['last_activity'] = time();
?>
