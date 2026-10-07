<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['library','dormitory','bursary','cafeteria','sports']);

$role = $_SESSION['role'];
$dept_name = ucfirst($role);
if ($role === 'library') $dept_name = 'Library';
elseif ($role === 'dormitory') $dept_name = 'Dormitory';
elseif ($role === 'bursary') $dept_name = 'Bursary';
elseif ($role === 'cafeteria') $dept_name = 'Cafeteria';
elseif ($role === 'sports') $dept_name = 'Sports';

$dept_id = get_department_id($pdo, $dept_name);

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM department_approvals WHERE department_id = ? AND status = 'pending'");
$stmt->execute([$dept_id]);
$pending = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM department_approvals WHERE department_id = ? AND status = 'approved'");
$stmt->execute([$dept_id]);
$approved = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM department_approvals WHERE department_id = ? AND status = 'rejected'");
$stmt->execute([$dept_id]);
$rejected = $stmt->fetchColumn();

// Recent pending
$stmt = $pdo->prepare("
    SELECT da.id AS approval_id, da.status, cr.id AS request_id, cr.clearance_type, cr.submitted_at,
           s.student_number, u.name AS student_name, s.program, s.batch
    FROM department_approvals da
    JOIN clearance_requests cr ON da.clearance_request_id = cr.id
    JOIN students s ON cr.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE da.department_id = ? AND da.status = 'pending'
    ORDER BY cr.submitted_at ASC
    LIMIT 10
");
$stmt->execute([$dept_id]);
$pending_list = $stmt->fetchAll();

$page_title = $dept_name . ' Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="number"><?= $pending ?></div>
        <div class="label">Pending</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $approved ?></div>
        <div class="label">Approved</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $rejected ?></div>
        <div class="label">Rejected</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Pending Clearance Requests</span>
        <a href="<?= BASE_URL ?>/departments/pending.php" class="btn btn-sm btn-primary">View All</a>
    </div>

    <?php if (empty($pending_list)): ?>
        <div class="empty-state">No pending requests.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Number</th>
                        <th>Program</th>
                        <th>Type</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_list as $r): ?>
                    <tr>
                        <td><?= e($r['student_name']) ?></td>
                        <td><?= e($r['student_number']) ?></td>
                        <td><?= e($r['program']) ?> (<?= e($r['batch']) ?>)</td>
                        <td><?= e($r['clearance_type']) ?></td>
                        <td><?= date('d M Y', strtotime($r['submitted_at'])) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/departments/review.php?id=<?= $r['approval_id'] ?>" class="btn btn-sm btn-primary">Review</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
