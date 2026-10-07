<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('student');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT c.*, cr.clearance_type 
    FROM certificates c
    JOIN clearance_requests cr ON c.clearance_request_id = cr.id
    WHERE cr.student_id = ?
    ORDER BY c.issue_date DESC
");
$stmt->execute([$student['id']]);
$certs = $stmt->fetchAll();

$page_title = 'My Certificates';
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Digital Certificates</span>
    </div>

    <?php if (empty($certs)): ?>
        <div class="empty-state">No certificates generated yet.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Certificate Number</th>
                        <th>Type</th>
                        <th>Issue Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certs as $c): ?>
                    <tr>
                        <td><?= e($c['certificate_number']) ?></td>
                        <td><?= e($c['clearance_type']) ?></td>
                        <td><?= date('d M Y', strtotime($c['issue_date'])) ?></td>
                        <td><?= status_badge($c['status']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/certificate/view.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary" target="_blank">View / Print</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
