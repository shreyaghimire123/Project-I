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

$status_filter = $_GET['status'] ?? 'pending';

$stmt = $pdo->prepare("
    SELECT da.id AS approval_id, da.status, da.remarks, da.processed_at,
           cr.id AS request_id, cr.clearance_type, cr.submitted_at, cr.status AS request_status,
           s.student_number, u.name AS student_name, s.program, s.batch, u.email
    FROM department_approvals da
    JOIN clearance_requests cr ON da.clearance_request_id = cr.id
    JOIN students s ON cr.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE da.department_id = ?
    " . ($status_filter !== 'all' ? "AND da.status = ?" : "") . "
    ORDER BY cr.submitted_at DESC
");
if ($status_filter !== 'all') {
    $stmt->execute([$dept_id, $status_filter]);
} else {
    $stmt->execute([$dept_id]);
}
$list = $stmt->fetchAll();

$page_title = $dept_name . ' - Requests';
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Clearance Requests</span>
        <div class="d-flex gap-1">
            <input type="text" id="tableSearch" class="form-control" placeholder="Search..." style="width:200px;">
            <select id="statusFilter" class="form-control" style="width:150px;">
                <option value="">All Status</option>
                <option value="pending" <?= $status_filter==='pending'?'selected':'' ?>>Pending</option>
                <option value="approved" <?= $status_filter==='approved'?'selected':'' ?>>Approved</option>
                <option value="rejected" <?= $status_filter==='rejected'?'selected':'' ?>>Rejected</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table id="dataTable">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Number</th>
                    <th>Program</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $r): ?>
                <tr data-status="<?= e($r['status']) ?>">
                    <td><?= e($r['student_name']) ?></td>
                    <td><?= e($r['student_number']) ?></td>
                    <td><?= e($r['program']) ?></td>
                    <td><?= e($r['clearance_type']) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td><?= date('d M Y', strtotime($r['submitted_at'])) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/departments/review.php?id=<?= $r['approval_id'] ?>" class="btn btn-sm btn-primary">
                            <?= $r['status'] === 'pending' ? 'Review' : 'View' ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
