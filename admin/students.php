<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$students = $pdo->query("
    SELECT s.*, u.name, u.email, u.status AS user_status
    FROM students s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.created_at DESC
")->fetchAll();

$page_title = 'Students';
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Registered Students</span>
        <input type="text" id="tableSearch" class="form-control" placeholder="Search..." style="width:200px;">
    </div>
    <div class="table-responsive">
        <table id="dataTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Student No.</th>
                    <th>Email</th>
                    <th>Program</th>
                    <th>Batch</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td><?= e($s['name']) ?></td>
                    <td><?= e($s['student_number']) ?></td>
                    <td><?= e($s['email']) ?></td>
                    <td><?= e($s['program']) ?></td>
                    <td><?= e($s['batch']) ?></td>
                    <td><?= status_badge($s['user_status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
