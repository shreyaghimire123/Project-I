<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    $role = $_SESSION['role'];
    if ($role === 'student') {
        redirect(BASE_URL . '/student/dashboard.php');
    } elseif (in_array($role, ['library','dormitory','bursary','cafeteria','sports'])) {
        redirect(BASE_URL . '/departments/dashboard.php');
    } elseif ($role === 'admin') {
        redirect(BASE_URL . '/admin/dashboard.php');
    }
}

$page_title = 'Welcome';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Clearance System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card" style="max-width:500px;text-align:center;">
        <h2>Academic Clearance System</h2>
        <p class="subtitle">“One Request. Multiple Departments. One Digital Certificate.”</p>
        <p class="mb-2 text-muted">Digitize your academic clearance process. Submit once, get approved by all departments, and receive a verifiable digital certificate.</p>
        <div class="d-flex gap-1 justify-between">
            <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary btn-block">Login</a>
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-outline btn-block">Student Register</a>
        </div>
        <p class="mt-2"><a href="<?= BASE_URL ?>/public/verify.php">Verify a Certificate →</a></p>
    </div>
</div>
</body>
</html>
