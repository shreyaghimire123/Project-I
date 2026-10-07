<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

// Stats
$total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_requests = $pdo->query("SELECT COUNT(*) FROM clearance_requests")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM clearance_requests WHERE status = 'in_progress'")->fetchColumn();
$action_req = $pdo->query("SELECT COUNT(*) FROM clearance_requests WHERE status = 'action_required'")->fetchColumn();
$ready = $pdo->query("SELECT COUNT(*) FROM clearance_requests WHERE status = 'ready_for_final'")->fetchColumn();
$completed = $pdo->query("SELECT COUNT(*) FROM clearance_requests WHERE status = 'completed'")->fetchColumn();

// Recent activity
$logs = $pdo->query("
    SELECT al.*, u.name 
    FROM audit_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC LIMIT 15
")->fetchAll();

$page_title = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card"><div class="number"><?= $total_students ?></div><div class="label">Students</div></div>
    <div class="stat-card"><div class="number"><?= $total_users ?></div><div class="label">Users</div></div>
    <div class="stat-card"><div class="number"><?= $total_requests ?></div><div class="label">Total Requests</div></div>
    <div class="stat-card"><div class="number"><?= $pending ?></div><div class="label">In Progress</div></div>
    <div class="stat-card"><div class="number"><?= $action_req ?></div><div class="label">Action Required</div></div>
    <div class="stat-card"><div class="number"><?= $ready ?></div><div class="label">Ready for Final</div></div>
    <div class="stat-card"><div class="number"><?= $completed ?></div><div class="label">Completed</div></div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Recent Activity</span>
        <a href="<?= BASE_URL ?>/admin/audit_logs.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?= e($l['name'] ?? 'System') ?></td>
                    <td><?= e($l['action']) ?></td>
                    <td><?= e($l['description']) ?></td>
                    <td><?= date('d M Y H:i', strtotime($l['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex gap-1 mt-2">
    <a href="<?= BASE_URL ?>/admin/final_approval.php" class="btn btn-primary">Final Approvals (<?= $ready ?>)</a>
    <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline">Manage Users</a>
    <a href="<?= BASE_URL ?>/admin/analytics.php" class="btn btn-outline">Analytics</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
