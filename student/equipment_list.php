<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireStudentOrStaff();

$stmt = $pdo->query("
    SELECT e.*, c.category_name
    FROM equipment e
    JOIN equipment_categories c ON e.category_id = c.category_id
    WHERE e.active = 1 AND c.active = 1
    ORDER BY c.category_name, e.equipment_name
");
$items = $stmt->fetchAll();

$page_title = 'Equipment List';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2>Sports Equipment Directory</h2>
    <table>
        <thead>
            <tr>
                <th>Equipment Name</th>
                <th>Category</th>
                <th>Description</th>
                <th>Condition</th>
                <th>Available Quantity</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><strong><?= e($item['equipment_name']) ?></strong></td>
                <td><?= e($item['category_name']) ?></td>
                <td><?= e($item['description']) ?></td>
                <td><?= e($item['condition_status']) ?></td>
                <td><?= (int)$item['available_quantity'] ?> / <?= (int)$item['total_quantity'] ?></td>
                <td>
                    <?php if ((int)$item['available_quantity'] > 0): ?>
                        <a class="btn btn-sm" href="request_equipment.php?equipment_id=<?= (int)$item['equipment_id'] ?>">Borrow</a>
                    <?php else: ?>
                        <span class="badge badge-rejected">Out of Stock</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
