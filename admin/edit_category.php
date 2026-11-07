<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$message = '';
$error   = '';
$cid     = (int)($_GET['id'] ?? $_POST['category_id'] ?? 0);

if (!$cid) {
    header("Location: /sports_equipment_system/admin/manage_categories.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM equipment_categories WHERE category_id = ?");
$stmt->execute([$cid]);
$category = $stmt->fetch();

if (!$category) {
    die("Category not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $active        = isset($_POST['active']) ? 1 : 0;

    if (empty($category_name)) {
        $error = 'Category name cannot be empty.';
    } else {
        $upd = $pdo->prepare("UPDATE equipment_categories SET category_name = ?, description = ?, active = ? WHERE category_id = ?");
        $upd->execute([$category_name, $description, $active, $cid]);
        $message = "Category '{$category_name}' updated successfully!";
        // Refresh record
        $stmt->execute([$cid]);
        $category = $stmt->fetch();
    }
}

$page_title = 'Edit Equipment Category';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width: 580px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h2>Edit Category #<?= (int)$category['category_id'] ?></h2>
        <a href="/sports_equipment_system/admin/manage_categories.php" class="btn btn-sm btn-secondary">&larr; Back to Categories</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
        <input type="hidden" name="category_id" value="<?= (int)$category['category_id'] ?>">

        <div class="form-group">
            <label>Category Name</label>
            <input type="text" name="category_name" value="<?= e($category['category_name']) ?>" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"><?= e($category['description']) ?></textarea>
        </div>

        <div class="form-group" style="display:flex; align-items:center; gap:0.5rem;">
            <input type="checkbox" name="active" id="activeChk" value="1" <?= $category['active'] ? 'checked' : '' ?> style="width:auto;">
            <label for="activeChk" style="margin:0; font-weight:600; cursor:pointer;">Active Category (visible for new equipment and requests)</label>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.6rem; margin-top:1rem;">
            <a href="/sports_equipment_system/admin/manage_categories.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
