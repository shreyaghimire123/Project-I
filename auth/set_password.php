<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_login();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, first_login = 0 WHERE id = ?");
        $stmt->execute([$hash, $_SESSION['user_id']]);
        log_audit($pdo, $_SESSION['user_id'], 'set_password', 'First-time password set');
        flash('success', 'Password set successfully.');
        redirect(BASE_URL . '/index.php');
    }
}

$page_title = 'Set Password';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <h2>Set Your Password</h2>
        <p class="subtitle">Please create a new password for your account</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Save Password</button>
        </form>
    </div>
</div>
</body>
</html>
