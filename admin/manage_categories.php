<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['category_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO equipment_categories (category_name, description, active) VALUES (?, ?, 1)");
        $stmt->execute([$name, $desc]);
        $message = "Category '{$name}' added.";
    }
}

if (isset($_GET['toggle_id'])) {
    $cid = (int)$_GET['toggle_id'];
    $pdo->prepare("UPDATE equipment_categories SET active = IF(active=1,0,1) WHERE category_id = ?")->execute([$cid]);
    $message = 'Category status updated.';
}

// Handle Category Deletion
if (isset($_GET['delete_id'])) {
    $cid = (int)$_GET['delete_id'];
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM equipment WHERE category_id = ?");
    $stmt_check->execute([$cid]);
    $eq_count = (int)$stmt_check->fetchColumn();

    if ($eq_count > 0) {
        $error = "Cannot delete this category because it contains {$eq_count} equipment items. Please reassign or delete the equipment items first, or deactivate the category.";
    } else {
        $del = $pdo->prepare("DELETE FROM equipment_categories WHERE category_id = ?");
        $del->execute([$cid]);
        $message = "Category #{$cid} was successfully deleted.";
    }
}

$categories = $pdo->query("SELECT * FROM equipment_categories ORDER BY category_name")->fetchAll();
$page_title = 'Manage Equipment Categories';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2>Add Equipment Category</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form-grid">
        <div class="form-group">
            <label>Category Name</label>
            <input type="text" name="category_name" required placeholder="e.g. Hockey">
        </div>
        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" placeholder="Sticks, balls, shin guards">
        </div>
        <div class="form-group" style="display:flex; align-items:flex-end;">
            <button type="submit" class="btn">Add Category</button>
        </div>
    </form>
</div>

<div class="card">
    <h3>Existing Categories</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Description</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $c): ?>
            <tr>
                <td><?= (int)$c['category_id'] ?></td>
                <td><strong><?= e($c['category_name']) ?></strong></td>
                <td><?= e($c['description']) ?></td>
                <td><span class="badge badge-<?= $c['active'] ? 'active' : 'inactive' ?>"><?= $c['active'] ? 'Active' : 'Inactive' ?></span></td>
                <td>
                    <a class="btn btn-sm btn-secondary" href="edit_category.php?id=<?= (int)$c['category_id'] ?>">Edit</a>
                    <a class="btn btn-sm" href="?toggle_id=<?= (int)$c['category_id'] ?>"><?= $c['active'] ? 'Deactivate' : 'Activate' ?></a>
                    <a class="btn btn-sm btn-danger" href="?delete_id=<?= (int)$c['category_id'] ?>" onclick="return confirm('Are you sure you want to permanently delete category <?= e(addslashes($c['category_name'])) ?>?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
