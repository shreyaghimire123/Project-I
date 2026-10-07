<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('student');

$user_id = $_SESSION['user_id'];

// Mark all as read
if (isset($_GET['mark_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
    flash('success', 'All notifications marked as read.');
    redirect(BASE_URL . '/student/notifications.php');
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

$page_title = 'Notifications';
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Notifications</span>
        <a href="?mark_read=1" class="btn btn-sm btn-outline">Mark all as read</a>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="empty-state">No notifications yet.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $n): ?>
                    <tr style="<?= !$n['is_read'] ? 'font-weight:600;' : '' ?>">
                        <td><?= e($n['message']) ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></td>
                        <td><?= $n['is_read'] ? 'Read' : '<span class="badge badge-blue">New</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
