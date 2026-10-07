<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT c.*, cr.clearance_type, cr.completed_at,
           s.student_number, s.program, s.batch,
           u.name AS student_name
    FROM certificates c
    JOIN clearance_requests cr ON c.clearance_request_id = cr.id
    JOIN students s ON cr.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE c.id = ? AND c.status = 'valid'
");
$stmt->execute([$id]);
$cert = $stmt->fetch();

if (!$cert) {
    die('Certificate not found or invalid.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Certificate - <?= e($cert['certificate_number']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { background: #f0f0f0; padding: 2rem; }
        .print-actions { text-align: center; margin-bottom: 1.5rem; }
        @media print {
            body { background: #fff; padding: 0; }
            .print-actions { display: none; }
        }
    </style>
</head>
<body>
<div class="print-actions no-print">
    <button onclick="window.print()" class="btn btn-primary">Print / Save as PDF</button>
    <a href="javascript:history.back()" class="btn btn-outline">Back</a>
</div>

<div class="certificate">
    <div class="certificate-header">
        <h1>NATIONAL MODEL COLLEGE</h1>
        <p>Academic Affairs Office</p>
        <div class="cert-seal">ACS</div>
        <h2 style="color:var(--navy);margin-top:1rem;">ACADEMIC CLEARANCE CERTIFICATE</h2>
    </div>

    <div class="certificate-body">
        <p>This is to certify that</p>
        <div class="student-name"><?= e($cert['student_name']) ?></div>
        <p>
            Student Number: <strong><?= e($cert['student_number']) ?></strong><br>
            Program: <strong><?= e($cert['program']) ?></strong> &nbsp;|&nbsp;
            Batch: <strong><?= e($cert['batch']) ?></strong>
        </p>
        <p style="margin-top:1.5rem;">
            has successfully completed all departmental clearance requirements<br>
            for <strong><?= e($cert['clearance_type']) ?></strong>
            and is hereby granted this Academic Clearance Certificate.
        </p>
    </div>

    <div class="certificate-footer">
        <div>
            <p><strong>Certificate No:</strong><br><?= e($cert['certificate_number']) ?></p>
            <p style="margin-top:.5rem;"><strong>Issue Date:</strong><br><?= date('d F Y', strtotime($cert['issue_date'])) ?></p>
        </div>
        <div style="text-align:right;">
            <p>_________________________</p>
            <p><strong>Registrar / Authorized Officer</strong></p>
            <p style="font-size:.8rem;margin-top:.5rem;">
                Verify at: <?= e($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/academic-clearance/public/verify.php
            </p>
        </div>
    </div>
</div>
</body>
</html>
