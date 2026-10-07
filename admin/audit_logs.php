<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$logs = $pdo->query("
    SELECT al.*, u.name, u.email
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 200
")->fetchAll();

$page_title = 'Audit Logs';
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <span class="card-title">System Audit Logs</span>
        <input type="text" id="tableSearch" class="form-control" placeholder="Search..." style="width:200px;">
    </div>
    <div class="table-responsive">
        <table id="dataTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?= e($l['name'] ?? 'System') ?><br><small class="text-muted"><?= e($l['email'] ?? '') ?></small></td>
                    <td><?= e($l['action']) ?></td>
                    <td><?= e($l['description']) ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($l['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
