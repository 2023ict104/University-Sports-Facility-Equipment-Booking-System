<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$message = '';
$error   = '';
$uid     = (int)($_GET['id'] ?? $_POST['user_id'] ?? 0);

if (!$uid) {
    header("Location: /sports_equipment_system/admin/manage_users.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $role       = $_POST['role'] ?? $user['role'];
    $status     = $_POST['status'] ?? $user['status'];
    $student_id = trim($_POST['student_id'] ?? '');
    $password   = $_POST['new_password'] ?? '';

    if ($role === 'student' && empty($student_id)) {
        $error = 'Student ID is required for student accounts.';
    } elseif (empty($full_name) || empty($email)) {
        $error = 'Full name and email address are required.';
    } else {
        // Check email uniqueness
        $chk = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $chk->execute([$email, $uid]);
        if ($chk->fetch()) {
            $error = 'Email address is already in use by another account.';
        } else {
            if ($role !== 'student') {
                $student_id = null;
            }
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, status = ?, student_id = ?, password_hash = ? WHERE user_id = ?");
                $upd->execute([$full_name, $email, $role, $status, $student_id, $hash, $uid]);
            } else {
                $upd = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, status = ?, student_id = ? WHERE user_id = ?");
                $upd->execute([$full_name, $email, $role, $status, $student_id, $uid]);
            }
            $message = 'User account updated successfully!';
            // Refresh record
            $stmt->execute([$uid]);
            $user = $stmt->fetch();
        }
    }
}

$page_title = 'Edit User Account';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width: 640px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h2>Edit User Account #<?= (int)$user['user_id'] ?></h2>
        <a href="/sports_equipment_system/admin/manage_users.php" class="btn btn-sm btn-secondary">&larr; Back to Users</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
        <input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>">

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required>
        </div>

        <div class="form-group">
            <label>University Email</label>
            <input type="email" name="email" value="<?= e($user['email']) ?>" required>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label>System Role</label>
                <select name="role" <?= $user['role'] === 'admin' && (int)$user['user_id'] === (int)$_SESSION['user_id'] ? 'disabled' : '' ?>>
                    <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                    <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                </select>
            </div>
            <div class="form-group">
                <label>Account Status</label>
                <select name="status" <?= (int)$user['user_id'] === (int)$_SESSION['user_id'] ? 'disabled' : '' ?>>
                    <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Student Registration ID (e.g. 2023/ICT/001)</label>
            <input type="text" name="student_id" value="<?= e($user['student_id'] ?? '') ?>" placeholder="Leave blank for Staff and Administrators">
        </div>

        <div class="form-group">
            <label>Reset Password (leave blank to keep current password)</label>
            <input type="password" name="new_password" placeholder="Enter new password if resetting">
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.6rem; margin-top:1rem;">
            <a href="/sports_equipment_system/admin/manage_users.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-success">Update User Account</button>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
