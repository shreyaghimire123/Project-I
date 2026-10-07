<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect(BASE_URL . '/index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $student_number = trim($_POST['student_number'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $batch = trim($_POST['batch'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$student_number || !$program || !$batch || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate email or student number
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ?");
            $stmt->execute([$student_number]);
            if ($stmt->fetch()) {
                $error = 'Student number already exists.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, first_login) VALUES (?, ?, ?, 'student', 'active', 0)");
                    $stmt->execute([$name, $email, $hash]);
                    $user_id = $pdo->lastInsertId();

                    $stmt = $pdo->prepare("INSERT INTO students (user_id, student_number, program, batch) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $student_number, $program, $batch]);

                    $pdo->commit();
                    log_audit($pdo, $user_id, 'register', 'Student registered');
                    $success = 'Registration successful! You can now login.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Registration failed. Please try again.';
                }
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
    <title>Student Registration | Academic Clearance</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <h2>Student Registration</h2>
        <p class="subtitle">Create your account</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Student Number</label>
                <input type="text" name="student_number" class="form-control" required value="<?= e($_POST['student_number'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Program</label>
                <input type="text" name="program" class="form-control" required placeholder="e.g. BCA, BBA" value="<?= e($_POST['program'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Batch</label>
                <input type="text" name="batch" class="form-control" required placeholder="e.g. 2024" value="<?= e($_POST['batch'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        <p class="text-center mt-2">
            Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Login</a>
        </p>
    </div>
</div>
</body>
</html>
