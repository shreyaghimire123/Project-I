<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('student');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $type = $_POST['clearance_type'] ?? '';
        $allowed = ['Graduation', 'Transfer', 'Semester Completion', 'Other'];

        if (!in_array($type, $allowed)) {
            $error = 'Invalid clearance type.';
        } else {
            // Prevent duplicate active request of same type
            $stmt = $pdo->prepare("
                SELECT id FROM clearance_requests 
                WHERE student_id = ? AND clearance_type = ? 
                AND status IN ('in_progress','action_required','ready_for_final')
            ");
            $stmt->execute([$student['id'], $type]);
            if ($stmt->fetch()) {
                $error = 'You already have an active request of this type.';
            } else {
                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("INSERT INTO clearance_requests (student_id, clearance_type, status) VALUES (?, ?, 'in_progress')");
                    $stmt->execute([$student['id'], $type]);
                    $request_id = $pdo->lastInsertId();

                    // Create department approval records for all 5 departments
                    $depts = $pdo->query("SELECT id FROM departments WHERE status = 'active'")->fetchAll();
                    $stmt = $pdo->prepare("INSERT INTO department_approvals (clearance_request_id, department_id, status) VALUES (?, ?, 'pending')");
                    foreach ($depts as $d) {
                        $stmt->execute([$request_id, $d['id']]);
                    }

                    log_audit($pdo, $user_id, 'new_request', "Submitted $type clearance request #$request_id");
                    add_notification($pdo, $user_id, "Your $type clearance request has been submitted successfully.");

                    $pdo->commit();
                    flash('success', 'Clearance request submitted successfully!');
                    redirect(BASE_URL . '/student/clearance.php');
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Failed to submit request. Please try again.';
                }
            }
        }
    }
}

$page_title = 'New Clearance Request';
include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width:500px;">
    <div class="card-header">
        <span class="card-title">Submit Clearance Request</span>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-group">
            <label>Clearance Type</label>
            <select name="clearance_type" class="form-control" required>
                <option value="">-- Select Type --</option>
                <option value="Graduation">Graduation Clearance</option>
                <option value="Transfer">Transfer Clearance</option>
                <option value="Semester Completion">Semester Completion</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <p class="text-muted mb-2">After submission, all five departments (Library, Dormitory, Bursary, Cafeteria, Sports) will review your request independently.</p>
        <button type="submit" class="btn btn-primary">Submit Request</button>
        <a href="<?= BASE_URL ?>/student/dashboard.php" class="btn btn-outline">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
