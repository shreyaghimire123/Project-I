<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('student');

$user_id = $_SESSION['user_id'];

// Get student record
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

if (!$student) {
    flash('error', 'Student profile not found.');
    redirect(BASE_URL . '/auth/logout.php');
}

// Get latest request
$stmt = $pdo->prepare("
    SELECT cr.*, 
        (SELECT COUNT(*) FROM department_approvals da WHERE da.clearance_request_id = cr.id AND da.status = 'approved') AS approved_count,
        (SELECT COUNT(*) FROM department_approvals da WHERE da.clearance_request_id = cr.id AND da.status = 'rejected') AS rejected_count,
        (SELECT COUNT(*) FROM department_approvals da WHERE da.clearance_request_id = cr.id AND da.status = 'pending') AS pending_count
    FROM clearance_requests cr
    WHERE cr.student_id = ?
    ORDER BY cr.submitted_at DESC
    LIMIT 1
");
$stmt->execute([$student['id']]);
$request = $stmt->fetch();

// Counts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM clearance_requests WHERE student_id = ?");
$stmt->execute([$student['id']]);
$total_requests = $stmt->fetchColumn();

// Outstanding dues
$stmt = $pdo->prepare("
    SELECT d.*, dep.department_name 
    FROM dues d 
    JOIN departments dep ON d.department_id = dep.id 
    WHERE d.student_id = ? AND d.status = 'pending'
");
$stmt->execute([$student['id']]);
$dues = $stmt->fetchAll();

// Unread notifications
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread = $stmt->fetchColumn();

$page_title = 'Student Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="number"><?= $total_requests ?></div>
        <div class="label">Total Requests</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $request ? $request['pending_count'] : 0 ?></div>
        <div class="label">Pending Depts</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $request ? $request['approved_count'] : 0 ?></div>
        <div class="label">Approved Depts</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $request ? $request['rejected_count'] : 0 ?></div>
        <div class="label">Rejected Depts</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= count($dues) ?></div>
        <div class="label">Outstanding Dues</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $unread ?></div>
        <div class="label">Notifications</div>
    </div>
</div>

<?php if ($request): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Current Clearance Status</span>
        <?= status_badge($request['status']) ?>
    </div>
    <p><strong>Type:</strong> <?= e($request['clearance_type']) ?></p>
    <p><strong>Submitted:</strong> <?= date('d M Y, h:i A', strtotime($request['submitted_at'])) ?></p>

    <div class="mt-2">
        <strong>Department Approval Progress</strong>
        <p><?= $request['approved_count'] ?> / 5 Departments Approved</p>
        <?php $pct = ($request['approved_count'] / 5) * 100; ?>
        <div class="progress-bar">
            <div class="progress-fill" style="width:<?= $pct ?>%"></div>
        </div>
        <small><?= round($pct) ?>%</small>
    </div>

    <div class="mt-2">
        <a href="<?= BASE_URL ?>/student/clearance.php" class="btn btn-primary btn-sm">View Details</a>
        <a href="<?= BASE_URL ?>/student/timeline.php" class="btn btn-outline btn-sm">Timeline</a>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="empty-state">
        <p>You have not submitted any clearance request yet.</p>
        <a href="<?= BASE_URL ?>/student/new_request.php" class="btn btn-primary mt-1">Submit New Request</a>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($dues)): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Outstanding Dues</span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dues as $d): ?>
                <tr>
                    <td><?= e($d['department_name']) ?></td>
                    <td><?= e($d['description']) ?></td>
                    <td>Rs. <?= number_format($d['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
