<?php
$role = $_SESSION['role'] ?? '';
$base = BASE_URL;
?>

<?php if ($role === 'student'): ?>
    <a href="<?= $base ?>/student/dashboard.php" class="nav-link">Dashboard</a>
    <a href="<?= $base ?>/student/new_request.php" class="nav-link">New Request</a>
    <a href="<?= $base ?>/student/clearance.php" class="nav-link">My Clearance</a>
    <a href="<?= $base ?>/student/timeline.php" class="nav-link">Timeline</a>
    <a href="<?= $base ?>/student/notifications.php" class="nav-link">Notifications</a>
    <a href="<?= $base ?>/student/certificates.php" class="nav-link">Certificates</a>

<?php elseif (in_array($role, ['library','dormitory','bursary','cafeteria','sports'])): ?>
    <a href="<?= $base ?>/departments/dashboard.php" class="nav-link">Dashboard</a>
    <a href="<?= $base ?>/departments/pending.php" class="nav-link">Pending Requests</a>
    <a href="<?= $base ?>/departments/history.php" class="nav-link">History</a>

<?php elseif ($role === 'admin'): ?>
    <a href="<?= $base ?>/admin/dashboard.php" class="nav-link">Dashboard</a>
    <a href="<?= $base ?>/admin/final_approval.php" class="nav-link">Final Approval</a>
    <a href="<?= $base ?>/admin/users.php" class="nav-link">Users</a>
    <a href="<?= $base ?>/admin/students.php" class="nav-link">Students</a>
    <a href="<?= $base ?>/admin/analytics.php" class="nav-link">Analytics</a>
    <a href="<?= $base ?>/admin/audit_logs.php" class="nav-link">Audit Logs</a>
<?php endif; ?>

<a href="<?= $base ?>/public/verify.php" class="nav-link" target="_blank">Verify Certificate</a>
