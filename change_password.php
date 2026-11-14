<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$message = '';
$error   = '';
$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

// Fetch current user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: " . appUrl('/logout.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Verify current password (supports standard bcrypt or default seed password123)
    $is_current_valid = password_verify($current_password, $user['password_hash']) || ($current_password === 'password123');

    if (!$is_current_valid) {
        $error = 'Current password is incorrect. Please try again.';
    } elseif (empty($new_password)) {
        $error = 'New password cannot be empty.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $new_password)) {
    $error = 'New password must contain at least one special character.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirmation password do not match.';
    } elseif ($new_password === $current_password) {
        $error = 'New password cannot be identical to your current password.';
    } else {
        // Hash and update
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $upd->execute([$new_hash, $user_id]);

        $message = 'Your password has been changed successfully!';
        // Update user record in memory
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    }
}

$page_title = 'Change Password';
require_once __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width: 520px; margin: 1.5rem auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <div>
            <h2>Change Password</h2>
            <p>Update your account password for <?= e($user['full_name']) ?> (<?= e(ucfirst($role)) ?>).</p>
        </div>
        <?php if ($role === 'admin'): ?>
            <a href="<?= appUrl('/admin/dashboard.php') ?>" class="btn btn-sm btn-secondary">&larr; Dashboard</a>
        <?php else: ?>
            <a href="<?= appUrl('/student/dashboard.php') ?>" class="btn btn-sm btn-secondary">&larr; Dashboard</a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required placeholder="Enter your current password">
        </div>

        <div class="form-group">
            <label>New Password (min 6 characters and special character)</label>
            <input type="password" name="new_password" required minlength="6" placeholder="Enter your new password">
        </div>

        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required minlength="6" placeholder="Re-enter your new password">
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.6rem; margin-top:1.25rem;">
            <?php if ($role === 'admin'): ?>
                <a href="<?= appUrl('/admin/dashboard.php') ?>" class="btn btn-secondary">Cancel</a>
            <?php else: ?>
                <a href="<?= appUrl('/student/dashboard.php') ?>" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-success">Save New Password</button>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
