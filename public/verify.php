<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$result = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cert_no = trim($_POST['certificate_number'] ?? '');
    if (empty($cert_no)) {
        $error = 'Please enter a certificate number.';
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, cr.clearance_type,
                   s.student_number, s.program, s.batch,
                   u.name AS student_name
            FROM certificates c
            JOIN clearance_requests cr ON c.clearance_request_id = cr.id
            JOIN students s ON cr.student_id = s.id
            JOIN users u ON s.user_id = u.id
            WHERE c.certificate_number = ?
        ");
        $stmt->execute([$cert_no]);
        $result = $stmt->fetch();
        if (!$result) {
            $error = 'INVALID CERTIFICATE — No record found for this number.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Certificate | Academic Clearance System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card" style="max-width:480px;">
        <h2>Certificate Verification</h2>
        <p class="subtitle">Enter the certificate number to verify authenticity</p>

        <form method="POST">
            <div class="form-group">
                <label>Certificate Number</label>
                <input type="text" name="certificate_number" class="form-control" 
                       placeholder="e.g. NMC-CLR-2026-00030" 
                       value="<?= e($_POST['certificate_number'] ?? '') ?>" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Verify Certificate</button>
        </form>

        <?php if ($error): ?>
            <div class="alert alert-error mt-2"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($result): ?>
            <div class="alert alert-success mt-2" style="flex-direction:column;align-items:flex-start;">
                <strong style="font-size:1.1rem;">✓ VALID CERTIFICATE</strong>
                <hr style="width:100%;margin:.75rem 0;border:none;border-top:1px solid #166534;">
                <p><strong>Student Name:</strong> <?= e($result['student_name']) ?></p>
                <p><strong>Student Number:</strong> <?= e($result['student_number']) ?></p>
                <p><strong>Program:</strong> <?= e($result['program']) ?></p>
                <p><strong>Batch:</strong> <?= e($result['batch']) ?></p>
                <p><strong>Clearance Type:</strong> <?= e($result['clearance_type']) ?></p>
                <p><strong>Certificate Number:</strong> <?= e($result['certificate_number']) ?></p>
                <p><strong>Issue Date:</strong> <?= date('d F Y', strtotime($result['issue_date'])) ?></p>
                <p><strong>Status:</strong> <?= status_badge($result['status']) ?></p>
            </div>
        <?php endif; ?>

        <p class="text-center mt-2">
            <a href="<?= BASE_URL ?>/index.php">← Back to Home</a>
        </p>
    </div>
</div>
</body>
</html>
