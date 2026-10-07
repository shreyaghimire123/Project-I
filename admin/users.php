<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$error = '';
$success = '';

// Add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $department = $_POST['department'] ?? null;
        $password = $_POST['password'] ?? 'password123';

        $allowed_roles = ['library','dormitory','bursary','cafeteria','sports','admin'];
        if (!$name || !$email || !in_array($role, $allowed_roles)) {
            $error = 'Please fill all required fields correctly.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $dept = in_array($role, ['library','dormitory','bursary','cafeteria','sports']) ? ucfirst($role) : null;
                if ($role === 'library') $dept = 'Library';
                if ($role === 'dormitory') $dept = 'Dormitory';
                if ($role === 'bursary') $dept = 'Bursary';
                if ($role === 'cafeteria') $dept = 'Cafeteria';
                if ($role === 'sports') $dept = 'Sports';

                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, department, status, first_login) VALUES (?, ?, ?, ?, ?, 'active', 1)");
                $stmt->execute([$name, $email, $hash, $role, $dept]);
                log_audit($pdo, $_SESSION['user_id'], 'add_user', "Added user $email as $role");
                $success = 'User added successfully. Default password: ' . $password;
            }
        }
    }
}

// Toggle status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $uid = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$uid]);
    $u = $stmt->fetch();
    if ($u) {
        $new = $u['status'] === 'active' ? 'inactive' : 'active';
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new, $uid]);
        log_audit($pdo, $_SESSION['user_id'], 'toggle_user', "User #$uid set to $new");
        flash('success', "User status updated to $new.");
        redirect(BASE_URL . '/admin/users.php');
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY role, name")->fetchAll();

$page_title = 'User Management';
include __DIR__ . '/../includes/header.php';
?>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Add New Staff / Admin</span>
    </div>
    <form method="POST" class="d-flex gap-1" style="flex-wrap:wrap;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="text" name="name" class="form-control" placeholder="Full Name" required style="max-width:180px;">
        <input type="email" name="email" class="form-control" placeholder="Email" required style="max-width:200px;">
        <select name="role" class="form-control" required style="max-width:150px;">
            <option value="">Role</option>
            <option value="library">Library</option>
            <option value="dormitory">Dormitory</option>
            <option value="bursary">Bursary</option>
            <option value="cafeteria">Cafeteria</option>
            <option value="sports">Sports</option>
            <option value="admin">Admin</option>
        </select>
        <input type="text" name="password" class="form-control" placeholder="Password (default: password123)" style="max-width:180px;">
        <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">All Users</span>
        <input type="text" id="tableSearch" class="form-control" placeholder="Search..." style="width:200px;">
    </div>
    <div class="table-responsive">
        <table id="dataTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e(ucfirst($u['role'])) ?></td>
                    <td><?= e($u['department'] ?? '—') ?></td>
                    <td><?= status_badge($u['status']) ?></td>
                    <td>
                        <?php if ($u['role'] !== 'admin' || $u['id'] != $_SESSION['user_id']): ?>
                        <a href="?toggle=<?= $u['id'] ?>" class="btn btn-sm btn-outline" data-confirm="Toggle status for this user?">
                            <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
