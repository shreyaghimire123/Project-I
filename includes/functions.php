<?php
/**
 * Common helper functions
 */

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user() {
    return [
        'id'         => $_SESSION['user_id'] ?? null,
        'name'       => $_SESSION['name'] ?? null,
        'email'      => $_SESSION['email'] ?? null,
        'role'       => $_SESSION['role'] ?? null,
        'department' => $_SESSION['department'] ?? null
    ];
}

function require_login() {
    if (!is_logged_in()) {
        flash('error', 'Please login to continue.');
        redirect(BASE_URL . '/auth/login.php');
    }
}

function require_role($roles) {
    require_login();
    $roles = (array)$roles;
    if (!in_array($_SESSION['role'], $roles)) {
        flash('error', 'You do not have permission to access this page.');
        redirect(BASE_URL . '/index.php');
    }
}

function log_audit($pdo, $user_id, $action, $description = '') {
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $action, $description]);
}

function add_notification($pdo, $user_id, $message) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$user_id, $message]);
}

function generate_certificate_number($pdo) {
    $year = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM certificates WHERE YEAR(issue_date) = $year");
    $count = (int)$stmt->fetchColumn() + 1;
    return sprintf("NMC-CLR-%s-%05d", $year, $count);
}

function generate_verification_code() {
    return strtoupper(bin2hex(random_bytes(8)));
}

function update_request_status($pdo, $request_id) {
    // Count department statuses
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending
        FROM department_approvals 
        WHERE clearance_request_id = ?
    ");
    $stmt->execute([$request_id]);
    $counts = $stmt->fetch();

    $new_status = 'in_progress';
    if ($counts['rejected'] > 0) {
        $new_status = 'action_required';
    } elseif ($counts['approved'] == 5) {
        $new_status = 'ready_for_final';
    }

    $stmt = $pdo->prepare("UPDATE clearance_requests SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $request_id]);
}

function get_department_id($pdo, $name) {
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE department_name = ?");
    $stmt->execute([$name]);
    return $stmt->fetchColumn();
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function status_badge($status) {
    $map = [
        'pending'            => 'badge-yellow',
        'approved'           => 'badge-green',
        'rejected'           => 'badge-red',
        'in_progress'        => 'badge-blue',
        'action_required'    => 'badge-yellow',
        'ready_for_final'    => 'badge-blue',
        'completed'          => 'badge-green',
        'active'             => 'badge-green',
        'inactive'           => 'badge-red',
        'valid'              => 'badge-green',
        'revoked'            => 'badge-red'
    ];
    $class = $map[$status] ?? 'badge-gray';
    $label = ucwords(str_replace('_', ' ', $status));
    return "<span class=\"badge $class\">$label</span>";
}
?>
