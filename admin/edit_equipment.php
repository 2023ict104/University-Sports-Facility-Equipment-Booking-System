<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$message = '';
$error   = '';
$eid     = (int)($_GET['id'] ?? $_POST['equipment_id'] ?? 0);

if (!$eid) {
    header("Location: /sports_equipment_system/admin/manage_equipment.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM equipment WHERE equipment_id = ?");
$stmt->execute([$eid]);
$item = $stmt->fetch();

if (!$item) {
    die("Equipment not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id        = (int)($_POST['category_id'] ?? 0);
    $equipment_name     = trim($_POST['equipment_name'] ?? '');
    $description        = trim($_POST['description'] ?? '');
    $total_quantity     = (int)($_POST['total_quantity'] ?? 0);
    $available_quantity = (int)($_POST['available_quantity'] ?? 0);
    $condition_status   = trim($_POST['condition_status'] ?? 'Good');
    $active             = isset($_POST['active']) ? 1 : 0;

    if (empty($equipment_name)) {
        $error = 'Equipment name is required.';
    } elseif ($total_quantity < 0 || $available_quantity < 0) {
        $error = 'Quantities cannot be negative.';
    } elseif ($available_quantity > $total_quantity) {
        $error = 'Available quantity cannot exceed total quantity.';
    } else {
        $upd = $pdo->prepare("
            UPDATE equipment
            SET category_id = ?, equipment_name = ?, description = ?, total_quantity = ?, available_quantity = ?, condition_status = ?, active = ?
            WHERE equipment_id = ?
        ");
        $upd->execute([$category_id, $equipment_name, $description, $total_quantity, $available_quantity, $condition_status, $active, $eid]);
        $message = "Equipment '{$equipment_name}' updated successfully!";
        // Refresh record
        $stmt->execute([$eid]);
        $item = $stmt->fetch();
    }
}

$categories = $pdo->query("SELECT * FROM equipment_categories WHERE active = 1 ORDER BY category_name")->fetchAll();
$page_title = 'Edit Sports Equipment';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width: 680px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h2>Edit Equipment #<?= (int)$item['equipment_id'] ?></h2>
        <a href="/sports_equipment_system/admin/manage_equipment.php" class="btn btn-sm btn-secondary">&larr; Back to Equipment</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
        <input type="hidden" name="equipment_id" value="<?= (int)$item['equipment_id'] ?>">

        <div class="form-grid">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" required>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['category_id'] ?>" <?= $c['category_id'] == $item['category_id'] ? 'selected' : '' ?>>
                            <?= e($c['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Equipment Name</label>
                <input type="text" name="equipment_name" value="<?= e($item['equipment_name']) ?>" required>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label>Total Quantity Owned</label>
                <input type="number" name="total_quantity" min="0" value="<?= (int)$item['total_quantity'] ?>" required>
            </div>

            <div class="form-group">
                <label>Available Quantity for Borrowing</label>
                <input type="number" name="available_quantity" min="0" value="<?= (int)$item['available_quantity'] ?>" required>
            </div>

            <div class="form-group">
                <label>Condition Status</label>
                <select name="condition_status">
                    <option value="New" <?= $item['condition_status'] === 'New' ? 'selected' : '' ?>>New</option>
                    <option value="Good" <?= $item['condition_status'] === 'Good' ? 'selected' : '' ?>>Good</option>
                    <option value="Fair" <?= $item['condition_status'] === 'Fair' ? 'selected' : '' ?>>Fair</option>
                    <option value="Needs Repair" <?= $item['condition_status'] === 'Needs Repair' ? 'selected' : '' ?>>Needs Repair</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"><?= e($item['description']) ?></textarea>
        </div>

        <div class="form-group" style="display:flex; align-items:center; gap:0.5rem;">
            <input type="checkbox" name="active" id="eqActiveChk" value="1" <?= $item['active'] ? 'checked' : '' ?> style="width:auto;">
            <label for="eqActiveChk" style="margin:0; font-weight:600; cursor:pointer;">Active Equipment (visible to students &amp; staff for borrowing)</label>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.6rem; margin-top:1rem;">
            <a href="/sports_equipment_system/admin/manage_equipment.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
