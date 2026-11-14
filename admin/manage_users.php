<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$message = '';
$error   = '';

// Handle Status Toggle
if (isset($_GET['toggle_id'])) {
    $uid = (int)$_GET['toggle_id'];
    $stmt = $pdo->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE user_id = ? AND role != 'admin'");
    $stmt->execute([$uid]);
    $message = 'User account status updated.';
}

// Handle User Deletion
if (isset($_GET['delete_id'])) {
    $uid = (int)$_GET['delete_id'];
    if ($uid === (int)$_SESSION['user_id']) {
        $error = 'You cannot delete your own logged-in administrator account.';
    } else {
        // 1. Check if user currently holds any unreturned equipment
        $stmt_unreturned = $pdo->prepare("
            SELECT i.issue_id, e.equipment_name,
                   (i.quantity_issued - COALESCE((SELECT SUM(quantity_returned) FROM equipment_returns rt WHERE rt.issue_id = i.issue_id), 0)) AS remaining_qty
            FROM equipment_issues i
            JOIN equipment_requests r ON i.request_id = r.request_id
            JOIN equipment e ON r.equipment_id = e.equipment_id
            WHERE r.user_id = ?
            HAVING remaining_qty > 0
        ");
        $stmt_unreturned->execute([$uid]);
        $unreturned_items = $stmt_unreturned->fetchAll();

        // 2. Check if user is an administrator who processed issues/returns
        $stmt_admin_actions = $pdo->prepare("
            SELECT 
              (SELECT COUNT(*) FROM equipment_issues WHERE issued_by = ?) AS issues_handled,
              (SELECT COUNT(*) FROM equipment_returns WHERE received_by = ?) AS returns_handled
        ");
        $stmt_admin_actions->execute([$uid, $uid]);
        $admin_actions = $stmt_admin_actions->fetch();
        $is_officer = ((int)$admin_actions['issues_handled'] > 0 || (int)$admin_actions['returns_handled'] > 0);

        if (!empty($unreturned_items)) {
            $names = array_map(function($item) {
                return $item['equipment_name'] . ' (' . $item['remaining_qty'] . ' unreturned)';
            }, $unreturned_items);
            $error = 'Cannot delete this user because they still hold equipment: ' . implode(', ', $names) . '. All items must be returned before deleting.';
        } elseif ($is_officer) {
            $error = 'Cannot delete this user because they are an officer who officially issued or received equipment records. You can deactivate their account instead.';
        } else {
            try {
                $pdo->beginTransaction();

                $del_returns = $pdo->prepare("
                    DELETE rt FROM equipment_returns rt
                    JOIN equipment_issues i ON rt.issue_id = i.issue_id
                    JOIN equipment_requests r ON i.request_id = r.request_id
                    WHERE r.user_id = ?
                ");
                $del_returns->execute([$uid]);

                $del_issues = $pdo->prepare("
                    DELETE i FROM equipment_issues i
                    JOIN equipment_requests r ON i.request_id = r.request_id
                    WHERE r.user_id = ?
                ");
                $del_issues->execute([$uid]);

                $del_requests = $pdo->prepare("DELETE FROM equipment_requests WHERE user_id = ?");
                $del_requests->execute([$uid]);

                $upd_reviewed = $pdo->prepare("UPDATE equipment_requests SET reviewed_by = NULL WHERE reviewed_by = ?");
                $upd_reviewed->execute([$uid]);

                $del_user = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role != 'admin'");
                $del_user->execute([$uid]);

                $pdo->commit();
                $message = "User account #{$uid} and their completed borrowing history were successfully deleted.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Database error during deletion: ' . $e->getMessage();
            }
        }
    }
}

// Search and Filter Handling
$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role_filter'] ?? '';

$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (full_name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter !== '') {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

$query .= " ORDER BY role, full_name";
$stmt_users = $pdo->prepare($query);
$stmt_users->execute($params);
$users = $stmt_users->fetchAll();

$page_title = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <h2>Manage Student &amp; Staff Accounts</h2>
            <p style="color:#64748b; font-size:0.9rem; margin-top:0.25rem;">Manage system users, update account roles, status, and credentials.</p>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <a href="add_student.php" class="btn btn-sm">+ Add Student</a>
            <a href="add_staff.php" class="btn btn-sm btn-success">+ Add Staff</a>
        </div>
    </div>

    <!-- Search & Filter Form Bar -->
    <form method="GET" style="display:flex; gap:0.75rem; margin-bottom:1.25rem; flex-wrap:wrap; align-items:center; background:#f8fafc; padding:0.75rem; border-radius:8px; border:1px solid #e2e8f0;">
        <div style="flex:1; min-width:220px; position:relative;">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by name, email, or student ID..." style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.9rem;">
        </div>
        <div>
            <select name="role_filter" style="padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.9rem; background:#fff;">
                <option value="">All Roles</option>
                <option value="student" <?= $role_filter === 'student' ? 'selected' : '' ?>>Student</option>
                <option value="staff" <?= $role_filter === 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Administrator</option>
            </select>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <button type="submit" class="btn btn-sm btn-secondary" style="padding:0.5rem 1rem;">Filter</button>
            <?php if ($search !== '' || $role_filter !== ''): ?>
                <a href="manage_users.php" class="btn btn-sm btn-secondary" style="padding:0.5rem 0.75rem; background:#e2e8f0; color:#334155; text-decoration:none; display:inline-flex; align-items:center;">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <div class="table-responsive">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc; text-align:left;">
                    <th style="padding:10px 12px;">ID</th>
                    <th style="padding:10px 12px;">Student ID</th>
                    <th style="padding:10px 12px;">Full Name</th>
                    <th style="padding:10px 12px;">Email</th>
                    <th style="padding:10px 12px;">Role</th>
                    <th style="padding:10px 12px;">Status</th>
                    <th class="text-right" style="padding:10px 12px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:2rem; color:#64748b;">No users found matching your criteria.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): 
                        // Generate initials for avatar
                        $words = explode(' ', trim($u['full_name']));
                        $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                    ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px;">#<?= (int)$u['user_id'] ?></td>
                        <td style="padding:12px;"><?= e($u['student_id'] ?: '—') ?></td>
                        <td style="padding:12px;">
                            <div style="display:flex; align-items:center; gap:0.65rem;">
                                <div style="width:32px; height:32px; border-radius:50%; background:#e0f2fe; color:#0369a1; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;">
                                    <?= e($initials) ?>
                                </div>
                                <strong><?= e($u['full_name']) ?></strong>
                            </div>
                        </td>
                        <td style="padding:12px; color:#475569;"><?= e($u['email']) ?></td>
                        <td style="padding:12px;"><?= e(ucfirst($u['role'])) ?></td>
                        <td style="padding:12px;">
                            <span class="badge badge-<?= e($u['status']) ?>" style="display:inline-flex; align-items:center; gap:4px;">
                                <span style="width:6px; height:6px; border-radius:50%; background:<?= $u['status'] === 'active' ? '#10b981' : '#ef4444' ?>;"></span>
                                <?= e(ucfirst($u['status'])) ?>
                            </span>
                        </td>
                        <td class="action-cell" style="padding:12px;">
                            <div class="table-actions" style="display:flex; justify-content:flex-end; gap:0.35rem;">
                                <?php if ($u['role'] !== 'admin'): ?>
                                    <a class="btn btn-sm btn-secondary" href="edit_user.php?id=<?= (int)$u['user_id'] ?>" title="Edit User">
                                        Edit
                                    </a>
                                    <a class="btn btn-sm btn-secondary" href="?toggle_id=<?= (int)$u['user_id'] ?>" title="Toggle Status">
                                        <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                    </a>
                                    <a class="btn btn-sm btn-danger" href="?delete_id=<?= (int)$u['user_id'] ?>" onclick="return confirm('Are you sure you want to permanently delete user <?= e(addslashes($u['full_name'])) ?>? All their completed borrowing records will be deleted.');" title="Delete User">
                                        Delete
                                    </a>
                                <?php else: ?>
                                    <small style="color:#64748b; font-weight:600;">Protected Admin</small>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
