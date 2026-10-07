<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect(BASE_URL . '/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['last_activity'] = time();

            log_audit($pdo, $user['id'], 'login', 'User logged in');

            if ($user['first_login']) {
                redirect(BASE_URL . '/auth/set_password.php');
            }

            // Redirect based on role
            if ($user['role'] === 'student') {
                redirect(BASE_URL . '/student/dashboard.php');
            } elseif (in_array($user['role'], ['library','dormitory','bursary','cafeteria','sports'])) {
                redirect(BASE_URL . '/departments/dashboard.php');
            } else {
                redirect(BASE_URL . '/admin/dashboard.php');
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Academic Clearance</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <h2>Login</h2>
        <p class="subtitle">Academic Clearance System</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        <p class="text-center mt-2">
            Student? <a href="<?= BASE_URL ?>/auth/register.php">Register here</a>
        </p>
        <p class="text-center mt-1">
            <a href="<?= BASE_URL ?>/index.php">← Back to Home</a>
        </p>

        <div class="mt-2" style="font-size:.8rem;color:#666;border-top:1px solid #eee;padding-top:1rem;">
            <strong>Demo Accounts</strong> (password: <code>password</code>)<br>
            Student: shreya@student.edu<br>
            Library: library@college.edu<br>
            Bursary: bursary@college.edu<br>
            Admin: admin@college.edu
        </div>
    </div>
</div>
</body>
</html>
