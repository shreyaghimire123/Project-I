<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$total = $pdo->query("SELECT COUNT(*) FROM clearance_requests")->fetchColumn();
$completed = $pdo->query("SELECT COUNT(*) FROM clearance_requests WHERE status = 'completed'")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM clearance_requests WHERE status IN ('in_progress','action_required')")->fetchColumn();
$ready = $pdo->query("SELECT COUNT(*) FROM clearance_requests WHERE status = 'ready_for_final'")->fetchColumn();

// Department-wise
$dept_stats = $pdo->query("
    SELECT d.department_name,
           SUM(CASE WHEN da.status = 'approved' THEN 1 ELSE 0 END) AS approved,
           SUM(CASE WHEN da.status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
           SUM(CASE WHEN da.status = 'pending' THEN 1 ELSE 0 END) AS pending
    FROM departments d
    LEFT JOIN department_approvals da ON d.id = da.department_id
    GROUP BY d.id
")->fetchAll();

// By type
$type_stats = $pdo->query("
    SELECT clearance_type, COUNT(*) AS cnt
    FROM clearance_requests
    GROUP BY clearance_type
")->fetchAll();

$page_title = 'Analytics';
include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card"><div class="number"><?= $total ?></div><div class="label">Total Requests</div></div>
    <div class="stat-card"><div class="number"><?= $completed ?></div><div class="label">Completed</div></div>
    <div class="stat-card"><div class="number"><?= $pending ?></div><div class="label">Pending / Action</div></div>
    <div class="stat-card"><div class="number"><?= $ready ?></div><div class="label">Ready for Final</div></div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Department-wise Approvals</span></div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Approved</th>
                    <th>Rejected</th>
                    <th>Pending</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dept_stats as $d): ?>
                <tr>
                    <td><?= e($d['department_name']) ?></td>
                    <td><?= $d['approved'] ?></td>
                    <td><?= $d['rejected'] ?></td>
                    <td><?= $d['pending'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Requests by Type</span></div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Type</th><th>Count</th></tr>
            </thead>
            <tbody>
                <?php foreach ($type_stats as $t): ?>
                <tr>
                    <td><?= e($t['clearance_type']) ?></td>
                    <td><?= $t['cnt'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
