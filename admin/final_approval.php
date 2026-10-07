<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$user_id = $_SESSION['user_id'];

// Handle final approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_approve'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid request.');
        redirect(BASE_URL . '/admin/final_approval.php');
    }

    $request_id = (int)$_POST['request_id'];

    $stmt = $pdo->prepare("SELECT * FROM clearance_requests WHERE id = ? AND status = 'ready_for_final'");
    $stmt->execute([$request_id]);
    $req = $stmt->fetch();

    if (!$req) {
        flash('error', 'Request not found or not ready.');
        redirect(BASE_URL . '/admin/final_approval.php');
    }

    try {
        $pdo->beginTransaction();

        // Mark completed
        $stmt = $pdo->prepare("UPDATE clearance_requests SET status = 'completed', completed_at = NOW() WHERE id = ?");
        $stmt->execute([$request_id]);

        // Generate certificate
        $cert_no = generate_certificate_number($pdo);
        $ver_code = generate_verification_code();
        $stmt = $pdo->prepare("
            INSERT INTO certificates (clearance_request_id, certificate_number, verification_code, issue_date, status)
            VALUES (?, ?, ?, CURDATE(), 'valid')
        ");
        $stmt->execute([$request_id, $cert_no, $ver_code]);

        // Notify student
        $stmt = $pdo->prepare("SELECT s.user_id FROM students s JOIN clearance_requests cr ON cr.student_id = s.id WHERE cr.id = ?");
        $stmt->execute([$request_id]);
        $stu_uid = $stmt->fetchColumn();
        if ($stu_uid) {
            add_notification($pdo, $stu_uid, "Your clearance has been fully approved! Certificate: $cert_no");
        }

        log_audit($pdo, $user_id, 'final_approval', "Final approval for request #$request_id. Certificate: $cert_no");

        $pdo->commit();
        flash('success', "Final approval given. Certificate $cert_no generated.");
        redirect(BASE_URL . '/admin/final_approval.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Failed to process final approval.');
        redirect(BASE_URL . '/admin/final_approval.php');
    }
}

// List ready requests
$stmt = $pdo->query("
    SELECT cr.*, s.student_number, s.program, s.batch, u.name AS student_name, u.email
    FROM clearance_requests cr
    JOIN students s ON cr.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE cr.status = 'ready_for_final'
    ORDER BY cr.submitted_at ASC
");
$ready_list = $stmt->fetchAll();

// Also show completed recently
$completed = $pdo->query("
    SELECT cr.*, s.student_number, u.name AS student_name, c.certificate_number
    FROM clearance_requests cr
    JOIN students s ON cr.student_id = s.id
    JOIN users u ON s.user_id = u.id
    LEFT JOIN certificates c ON c.clearance_request_id = cr.id
    WHERE cr.status = 'completed'
    ORDER BY cr.completed_at DESC
    LIMIT 10
")->fetchAll();

$page_title = 'Final Approval';
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Ready for Final Approval</span>
    </div>

    <?php if (empty($ready_list)): ?>
        <div class="empty-state">No requests waiting for final approval.</div>
    <?php else: ?>
        <?php foreach ($ready_list as $r): ?>
        <div class="card" style="border:1px solid var(--gray-200);">
            <div class="d-flex justify-between align-center">
                <div>
                    <strong><?= e($r['student_name']) ?></strong> (<?= e($r['student_number']) ?>)<br>
                    <small><?= e($r['program']) ?> | <?= e($r['batch']) ?> | <?= e($r['clearance_type']) ?></small><br>
                    <small class="text-muted">Submitted: <?= date('d M Y', strtotime($r['submitted_at'])) ?></small>
                </div>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                    <button type="submit" name="final_approve" class="btn btn-success" data-confirm="Confirm final approval and generate certificate?">
                        Final Approve
                    </button>
                </form>
            </div>

            <?php
            // Show department statuses
            $apps = $pdo->prepare("
                SELECT da.status, d.department_name, da.remarks
                FROM department_approvals da
                JOIN departments d ON da.department_id = d.id
                WHERE da.clearance_request_id = ?
            ");
            $apps->execute([$r['id']]);
            $dept_status = $apps->fetchAll();
            ?>
            <div class="mt-1" style="font-size:.85rem;">
                <?php foreach ($dept_status as $ds): ?>
                    <span class="badge badge-green" style="margin-right:4px;"><?= e($ds['department_name']) ?> ✓</span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Recently Completed</span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Number</th>
                    <th>Type</th>
                    <th>Certificate</th>
                    <th>Completed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($completed as $c): ?>
                <tr>
                    <td><?= e($c['student_name']) ?></td>
                    <td><?= e($c['student_number']) ?></td>
                    <td><?= e($c['clearance_type']) ?></td>
                    <td><?= e($c['certificate_number'] ?? '—') ?></td>
                    <td><?= $c['completed_at'] ? date('d M Y', strtotime($c['completed_at'])) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
