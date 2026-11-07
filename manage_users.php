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
            // All items returned (or requests were pending/rejected/completed). Cleanly cascade delete request/issue/return history.
            try {
                $pdo->beginTransaction();

                // Delete return records linked to user's issues
                $del_returns = $pdo->prepare("
                    DELETE rt FROM equipment_returns rt
                    JOIN equipment_issues i ON rt.issue_id = i.issue_id
                    JOIN equipment_requests r ON i.request_id = r.request_id
                    WHERE r.user_id = ?
                ");
                $del_returns->execute([$uid]);

                // Delete issue records linked to user's requests
                $del_issues = $pdo->prepare("
                    DELETE i FROM equipment_issues i
                    JOIN equipment_requests r ON i.request_id = r.request_id
                    WHERE r.user_id = ?
                ");
                $del_issues->execute([$uid]);

                // Delete requests submitted by user
                $del_requests = $pdo->prepare("DELETE FROM equipment_requests WHERE user_id = ?");
                $del_requests->execute([$uid]);

                // Clear any reviewed_by references if user ever reviewed as admin
                $upd_reviewed = $pdo->prepare("UPDATE equipment_requests SET reviewed_by = NULL WHERE reviewed_by = ?");
                $upd_reviewed->execute([$uid]);

                // Finally delete the user account
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

$users = $pdo->query("SELECT * FROM users ORDER BY role, full_name")->fetchAll();
$page_title = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <div>
            <h2>Manage Student &amp; Staff Accounts</h2>
            <p>Users who have returned all equipment can be safely deleted or deactivated.</p>
        </div>
        <div>
            <a href="add_student.php" class="btn btn-sm">+ Add Student</a>
            <a href="add_staff.php" class="btn btn-sm btn-success">+ Add Staff</a>
        </div>
    </div>
    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?= (int)$u['user_id'] ?></td>
                    <td><?= e($u['student_id'] ?: '—') ?></td>
                    <td><strong><?= e($u['full_name']) ?></strong></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e(ucfirst($u['role'])) ?></td>
                    <td><span class="badge badge-<?= e($u['status']) ?>"><?= e(ucfirst($u['status'])) ?></span></td>
                    <td class="action-cell">
                        <div class="table-actions">
                            <?php if ($u['role'] !== 'admin'): ?>
                                <a class="btn btn-sm btn-secondary" href="edit_user.php?id=<?= (int)$u['user_id'] ?>">
                                    Edit
                                </a>
                                <a class="btn btn-sm btn-secondary" href="?toggle_id=<?= (int)$u['user_id'] ?>">
                                    <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                </a>
                                <a class="btn btn-sm btn-danger" href="?delete_id=<?= (int)$u['user_id'] ?>" onclick="return confirm('Are you sure you want to permanently delete user <?= e(addslashes($u['full_name'])) ?>? All their completed borrowing records will be deleted.');">
                                    Delete
                                </a>
                            <?php else: ?>
                                <small style="color:#64748b; font-weight:600;">Protected Admin</small>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
