<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['library','dormitory','bursary','cafeteria','sports']);

$role = $_SESSION['role'];
$dept_map = [
    'library' => 'Library', 'dormitory' => 'Dormitory', 'bursary' => 'Bursary',
    'cafeteria' => 'Cafeteria', 'sports' => 'Sports'
];
$dept_name = $dept_map[$role];
$dept_id = get_department_id($pdo, $dept_name);
$user_id = $_SESSION['user_id'];

$approval_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT da.*, cr.id AS request_id, cr.clearance_type, cr.status AS request_status, cr.submitted_at,
           s.id AS student_id, s.student_number, s.program, s.batch,
           u.name AS student_name, u.email
    FROM department_approvals da
    JOIN clearance_requests cr ON da.clearance_request_id = cr.id
    JOIN students s ON cr.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE da.id = ? AND da.department_id = ?
");
$stmt->execute([$approval_id, $dept_id]);
$approval = $stmt->fetch();

if (!$approval) {
    flash('error', 'Request not found.');
    redirect(BASE_URL . '/departments/dashboard.php');
}

// Handle Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $approval['status'] === 'pending') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid request.');
        redirect(BASE_URL . '/departments/review.php?id=' . $approval_id);
    }

    $action = $_POST['action'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');

    if ($action === 'approve') {
        $stmt = $pdo->prepare("
            UPDATE department_approvals 
            SET status = 'approved', remarks = ?, processed_by = ?, processed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$remarks ?: 'Cleared', $user_id, $approval_id]);

        update_request_status($pdo, $approval['request_id']);
        log_audit($pdo, $user_id, 'approve', "$dept_name approved request #{$approval['request_id']}");

        $stu_user = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
        $stu_user->execute([$approval['student_id']]);
        $stu_uid = $stu_user->fetchColumn();
        if ($stu_uid) {
            add_notification($pdo, $stu_uid, "$dept_name has approved your clearance request #{$approval['request_id']}.");
        }

        flash('success', 'Request approved successfully.');
        redirect(BASE_URL . '/departments/dashboard.php');

    } elseif ($action === 'reject') {
        if (empty($remarks)) {
            flash('error', 'Rejection reason is required.');
            redirect(BASE_URL . '/departments/review.php?id=' . $approval_id);
        }

        $stmt = $pdo->prepare("
            UPDATE department_approvals 
            SET status = 'rejected', remarks = ?, processed_by = ?, processed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$remarks, $user_id, $approval_id]);

        update_request_status($pdo, $approval['request_id']);
        log_audit($pdo, $user_id, 'reject', "$dept_name rejected request #{$approval['request_id']}: $remarks");

        $stu_user = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
        $stu_user->execute([$approval['student_id']]);
        $stu_uid = $stu_user->fetchColumn();
        if ($stu_uid) {
            add_notification($pdo, $stu_uid, "$dept_name has rejected your clearance request. Reason: $remarks");
        }

        flash('success', 'Request rejected.');
        redirect(BASE_URL . '/departments/dashboard.php');
    }
}

// Checklist suggestions per department
$checklists = [
    'Library' => ['Books returned', 'No overdue books', 'No outstanding fine'],
    'Dormitory' => ['Room cleared', 'Keys returned', 'No property damage'],
    'Bursary' => ['Tuition fees cleared', 'No outstanding loans', 'No other financial dues'],
    'Cafeteria' => ['Meal card status OK', 'No outstanding balance', 'ID card status OK'],
    'Sports' => ['Equipment returned', 'No sports dues', 'No pending issues']
];
$checklist = $checklists[$dept_name] ?? [];

$page_title = 'Review Request';
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Student Information</span>
        <?= status_badge($approval['status']) ?>
    </div>
    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
        <div><strong>Name</strong><br><?= e($approval['student_name']) ?></div>
        <div><strong>Student No.</strong><br><?= e($approval['student_number']) ?></div>
        <div><strong>Program</strong><br><?= e($approval['program']) ?></div>
        <div><strong>Batch</strong><br><?= e($approval['batch']) ?></div>
        <div><strong>Email</strong><br><?= e($approval['email']) ?></div>
        <div><strong>Clearance Type</strong><br><?= e($approval['clearance_type']) ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><?= e($dept_name) ?> Checklist</span>
    </div>
    <ul style="padding-left:1.5rem;">
        <?php foreach ($checklist as $item): ?>
            <li><?= e($item) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php if ($approval['status'] === 'pending'): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Take Action</span>
    </div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-group">
            <label>Remarks (required for rejection)</label>
            <textarea name="remarks" class="form-control" placeholder="Optional for approve, required for reject"></textarea>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" name="action" value="approve" class="btn btn-success" data-confirm="Approve this clearance?">Approve</button>
            <button type="submit" name="action" value="reject" class="btn btn-danger" data-confirm="Reject this clearance?">Reject</button>
            <a href="<?= BASE_URL ?>/departments/dashboard.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="card">
    <p><strong>Status:</strong> <?= status_badge($approval['status']) ?></p>
    <p><strong>Remarks:</strong> <?= e($approval['remarks'] ?: '—') ?></p>
    <p><strong>Processed At:</strong> <?= $approval['processed_at'] ? date('d M Y, h:i A', strtotime($approval['processed_at'])) : '—' ?></p>
    <a href="<?= BASE_URL ?>/departments/dashboard.php" class="btn btn-outline">← Back</a>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
