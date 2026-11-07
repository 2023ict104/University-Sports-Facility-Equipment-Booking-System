<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireStudentOrStaff();

$user_id = $_SESSION['user_id'];

// Summary counts
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count
    FROM equipment_requests
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$summary = $stmt->fetch();

$equip_stmt = $pdo->query("
    SELECT e.*, c.category_name
    FROM equipment e
    JOIN equipment_categories c ON e.category_id = c.category_id
    WHERE e.active = 1 AND c.active = 1
    ORDER BY e.equipment_name ASC
    LIMIT 6
");
$available_items = $equip_stmt->fetchAll();

$page_title = 'Student & Staff Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2>Welcome, <?= e($_SESSION['full_name']) ?>!</h2>
    <p>Role: <strong><?= e(ucfirst($_SESSION['role'])) ?></strong>
    <?php if (!empty($_SESSION['student_id'])): ?>
        &bull; Registration No: <strong><?= e($_SESSION['student_id']) ?></strong>
    <?php endif; ?>
    </p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="label">Total Requests</div>
        <div class="value"><?= (int)($summary['total_requests'] ?? 0) ?></div>
    </div>
    <div class="stat-card warning">
        <div class="label">Pending Approval</div>
        <div class="value"><?= (int)($summary['pending_count'] ?? 0) ?></div>
    </div>
    <div class="stat-card success">
        <div class="label">Approved / Active</div>
        <div class="value"><?= (int)($summary['approved_count'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Completed Returns</div>
        <div class="value"><?= (int)($summary['completed_count'] ?? 0) ?></div>
    </div>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.85rem;">
        <h3>Available Sports Equipment</h3>
        <a href="request_equipment.php" class="btn btn-sm">Request Equipment</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Equipment</th>
                <th>Category</th>
                <th>Condition</th>
                <th>Available / Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($available_items as $item): ?>
            <tr>
                <td><strong><?= e($item['equipment_name']) ?></strong></td>
                <td><?= e($item['category_name']) ?></td>
                <td><?= e($item['condition_status']) ?></td>
                <td><?= (int)$item['available_quantity'] ?> / <?= (int)$item['total_quantity'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
