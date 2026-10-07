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

$stmt = $pdo->prepare("
    SELECT da.*, cr.clearance_type, cr.submitted_at,
           s.student_number, u.name AS student_name, s.program
    FROM department_approvals da
    JOIN clearance_requests cr ON da.clearance_request_id = cr.id
    JOIN students s ON cr.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE da.department_id = ? AND da.status != 'pending'
    ORDER BY da.processed_at DESC
    LIMIT 100
");
$stmt->execute([$dept_id]);
$list = $stmt->fetchAll();

$page_title = $dept_name . ' - History';
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Processing History</span>
        <input type="text" id="tableSearch" class="form-control" placeholder="Search..." style="width:200px;">
    </div>
    <div class="table-responsive">
        <table id="dataTable">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Number</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <th>Processed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $r): ?>
                <tr>
                    <td><?= e($r['student_name']) ?></td>
                    <td><?= e($r['student_number']) ?></td>
                    <td><?= e($r['clearance_type']) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td><?= e($r['remarks'] ?: '—') ?></td>
                    <td><?= $r['processed_at'] ? date('d M Y', strtotime($r['processed_at'])) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
