<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('student');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM clearance_requests WHERE student_id = ? ORDER BY submitted_at DESC LIMIT 1");
$stmt->execute([$student['id']]);
$request = $stmt->fetch();

$events = [];

if ($request) {
    $events[] = [
        'title' => 'Clearance Submitted',
        'date'  => $request['submitted_at'],
        'type'  => 'info',
        'desc'  => $request['clearance_type'] . ' request submitted.'
    ];

    $stmt = $pdo->prepare("
        SELECT da.*, d.department_name 
        FROM department_approvals da
        JOIN departments d ON da.department_id = d.id
        WHERE da.clearance_request_id = ? AND da.status != 'pending'
        ORDER BY da.processed_at ASC
    ");
    $stmt->execute([$request['id']]);
    $approvals = $stmt->fetchAll();

    foreach ($approvals as $a) {
        $events[] = [
            'title' => $a['department_name'] . ' ' . ucfirst($a['status']),
            'date'  => $a['processed_at'],
            'type'  => $a['status'],
            'desc'  => $a['remarks'] ?: ''
        ];
    }

    if ($request['status'] === 'ready_for_final') {
        $events[] = [
            'title' => 'Ready for Final Approval',
            'date'  => date('Y-m-d H:i:s'),
            'type'  => 'info',
            'desc'  => 'All departments have approved. Waiting for Registrar.'
        ];
    }

    if ($request['status'] === 'completed') {
        $stmt = $pdo->prepare("SELECT * FROM certificates WHERE clearance_request_id = ?");
        $stmt->execute([$request['id']]);
        $cert = $stmt->fetch();
        $events[] = [
            'title' => 'Registrar Approved & Certificate Generated',
            'date'  => $request['completed_at'],
            'type'  => 'approved',
            'desc'  => $cert ? 'Certificate: ' . $cert['certificate_number'] : ''
        ];
    }
}

$page_title = 'Clearance Timeline';
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Clearance Timeline</span>
    </div>

    <?php if (empty($events)): ?>
        <div class="empty-state">No clearance activity yet.</div>
    <?php else: ?>
        <div class="timeline">
            <?php foreach ($events as $e): ?>
            <div class="timeline-item <?= e($e['type']) ?>">
                <div class="timeline-content">
                    <strong><?= e($e['title']) ?></strong>
                    <?php if ($e['desc']): ?>
                        <p class="text-muted"><?= e($e['desc']) ?></p>
                    <?php endif; ?>
                    <div class="timeline-date"><?= date('d M Y, h:i A', strtotime($e['date'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<a href="<?= BASE_URL ?>/student/dashboard.php" class="btn btn-outline">← Back</a>

<?php include __DIR__ . '/../includes/footer.php'; ?>
