<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('student');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

// Get latest or specific request
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($request_id) {
    $stmt = $pdo->prepare("SELECT * FROM clearance_requests WHERE id = ? AND student_id = ?");
    $stmt->execute([$request_id, $student['id']]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM clearance_requests WHERE student_id = ? ORDER BY submitted_at DESC LIMIT 1");
    $stmt->execute([$student['id']]);
}
$request = $stmt->fetch();

if (!$request) {
    flash('info', 'No clearance request found.');
    redirect(BASE_URL . '/student/new_request.php');
}

// Handle re-application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reapply'])) {
    if (verify_csrf($_POST['csrf_token'] ?? '') && $request['status'] === 'action_required') {
        // Reset rejected departments to pending
        $stmt = $pdo->prepare("
            UPDATE department_approvals 
            SET status = 'pending', remarks = NULL, processed_by = NULL, processed_at = NULL 
            WHERE clearance_request_id = ? AND status = 'rejected'
        ");
        $stmt->execute([$request['id']]);

        update_request_status($pdo, $request['id']);
        log_audit($pdo, $user_id, 'reapply', "Re-applied for request #{$request['id']}");
        add_notification($pdo, $user_id, "You have re-applied for clearance request #{$request['id']}.");
        flash('success', 'Request re-submitted. Rejected departments will review again.');
        redirect(BASE_URL . '/student/clearance.php?id=' . $request['id']);
    }
}

// Department approvals
$stmt = $pdo->prepare("
    SELECT da.*, d.department_name, u.name AS processed_by_name
    FROM department_approvals da
    JOIN departments d ON da.department_id = d.id
    LEFT JOIN users u ON da.processed_by = u.id
    WHERE da.clearance_request_id = ?
    ORDER BY d.id
");
$stmt->execute([$request['id']]);
$approvals = $stmt->fetchAll();

// Certificate if exists
$cert = null;
if ($request['status'] === 'completed') {
    $stmt = $pdo->prepare("SELECT * FROM certificates WHERE clearance_request_id = ?");
    $stmt->execute([$request['id']]);
    $cert = $stmt->fetch();
}

$page_title = 'My Clearance';
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Clearance Request #<?= $request['id'] ?></span>
        <?= status_badge($request['status']) ?>
    </div>
    <p><strong>Type:</strong> <?= e($request['clearance_type']) ?></p>
    <p><strong>Submitted:</strong> <?= date('d M Y, h:i A', strtotime($request['submitted_at'])) ?></p>
    <?php if ($request['completed_at']): ?>
        <p><strong>Completed:</strong> <?= date('d M Y, h:i A', strtotime($request['completed_at'])) ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Department-wise Status</span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <th>Processed By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($approvals as $a): ?>
                <tr>
                    <td><?= e($a['department_name']) ?></td>
                    <td><?= status_badge($a['status']) ?></td>
                    <td><?= e($a['remarks'] ?: '—') ?></td>
                    <td><?= e($a['processed_by_name'] ?: '—') ?></td>
                    <td><?= $a['processed_at'] ? date('d M Y', strtotime($a['processed_at'])) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($request['status'] === 'action_required'): ?>
<div class="card">
    <div class="alert alert-warning">
        One or more departments have rejected your request. Please resolve the issues and re-apply.
    </div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <button type="submit" name="reapply" class="btn btn-primary" data-confirm="Are you sure you have resolved all issues and want to re-apply?">Re-Apply Now</button>
    </form>
</div>
<?php endif; ?>

<?php if ($cert): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Digital Certificate</span>
        <?= status_badge($cert['status']) ?>
    </div>
    <p><strong>Certificate Number:</strong> <?= e($cert['certificate_number']) ?></p>
    <p><strong>Issue Date:</strong> <?= date('d M Y', strtotime($cert['issue_date'])) ?></p>
    <a href="<?= BASE_URL ?>/certificate/view.php?id=<?= $cert['id'] ?>" class="btn btn-success btn-sm" target="_blank">View / Print Certificate</a>
</div>
<?php endif; ?>

<a href="<?= BASE_URL ?>/student/dashboard.php" class="btn btn-outline">← Back to Dashboard</a>

<?php include __DIR__ . '/../includes/footer.php'; ?>
