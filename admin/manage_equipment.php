<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id      = (int)($_POST['category_id'] ?? 0);
    $equipment_name   = trim($_POST['equipment_name'] ?? '');
    $description      = trim($_POST['description'] ?? '');
    $total_quantity   = (int)($_POST['total_quantity'] ?? 0);
    $condition_status = trim($_POST['condition_status'] ?? 'Good');

    if ($category_id && $equipment_name && $total_quantity >= 0) {
        $stmt = $pdo->prepare("
            INSERT INTO equipment (category_id, equipment_name, description, total_quantity, available_quantity, condition_status, active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$category_id, $equipment_name, $description, $total_quantity, $total_quantity, $condition_status]);
        $message = "Equipment '{$equipment_name}' added.";
    }
}

if (isset($_GET['toggle_id'])) {
    $eid = (int)$_GET['toggle_id'];
    $pdo->prepare("UPDATE equipment SET active = IF(active=1,0,1) WHERE equipment_id = ?")->execute([$eid]);
    $message = 'Equipment active status updated.';
}

// Handle Equipment Deletion
if (isset($_GET['delete_id'])) {
    $eid = (int)$_GET['delete_id'];
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM equipment_requests WHERE equipment_id = ?");
    $stmt_check->execute([$eid]);
    $req_count = (int)$stmt_check->fetchColumn();

    if ($req_count > 0) {
        $error = "Cannot delete this equipment item because it is referenced in {$req_count} borrowing request(s) or issue records. You can deactivate the item to hide it from new requests.";
    } else {
        $del = $pdo->prepare("DELETE FROM equipment WHERE equipment_id = ?");
        $del->execute([$eid]);
        $message = "Equipment #{$eid} was successfully deleted.";
    }
}

$categories = $pdo->query("SELECT * FROM equipment_categories WHERE active = 1 ORDER BY category_name")->fetchAll();
$items = $pdo->query("
    SELECT e.*, c.category_name
    FROM equipment e
    JOIN equipment_categories c ON e.category_id = c.category_id
    ORDER BY e.equipment_name
")->fetchAll();

$page_title = 'Manage Equipment';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2>Add New Sports Equipment</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form-grid">
        <div class="form-group">
            <label>Category</label>
            <select name="category_id" required>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['category_id'] ?>"><?= e($c['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Equipment Name</label>
            <input type="text" name="equipment_name" required>
        </div>
        <div class="form-group">
            <label>Total Quantity</label>
            <input type="number" name="total_quantity" min="1" value="5" required>
        </div>
        <div class="form-group">
            <label>Condition Status</label>
            <select name="condition_status">
                <option value="New">New</option>
                <option value="Good" selected>Good</option>
                <option value="Fair">Fair</option>
                <option value="Needs Repair">Needs Repair</option>
            </select>
        </div>
        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description">
        </div>
        <div class="form-group" style="display:flex; align-items:flex-end;">
            <button type="submit" class="btn">Save Equipment</button>
        </div>
    </form>
</div>

<div class="card">
    <h3>Equipment Inventory</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Available / Total</th>
                <th>Condition</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= (int)$item['equipment_id'] ?></td>
                <td><strong><?= e($item['equipment_name']) ?></strong></td>
                <td><?= e($item['category_name']) ?></td>
                <td><?= (int)$item['available_quantity'] ?> / <?= (int)$item['total_quantity'] ?></td>
                <td><?= e($item['condition_status']) ?></td>
                <td><span class="badge badge-<?= $item['active'] ? 'active' : 'inactive' ?>"><?= $item['active'] ? 'Active' : 'Inactive' ?></span></td>
                <td>
                    <a class="btn btn-sm btn-secondary" href="edit_equipment.php?id=<?= (int)$item['equipment_id'] ?>">Edit</a>
                    <a class="btn btn-sm" href="?toggle_id=<?= (int)$item['equipment_id'] ?>"><?= $item['active'] ? 'Deactivate' : 'Activate' ?></a>
                    <a class="btn btn-sm btn-danger" href="?delete_id=<?= (int)$item['equipment_id'] ?>" onclick="return confirm('Are you sure you want to permanently delete <?= e(addslashes($item['equipment_name'])) ?>?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
