<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$total_equip = (int)$pdo->query("SELECT COALESCE(SUM(total_quantity),0) FROM equipment WHERE active=1")->fetchColumn();
$avail_equip = (int)$pdo->query("SELECT COALESCE(SUM(available_quantity),0) FROM equipment WHERE active=1")->fetchColumn();
$pending_req = (int)$pdo->query("SELECT COUNT(*) FROM equipment_requests WHERE status='pending'")->fetchColumn();
$overdue_cnt = (int)$pdo->query("
    SELECT COUNT(*) FROM equipment_issues i
    WHERE i.due_date < CURDATE()
      AND i.quantity_issued > COALESCE((SELECT SUM(quantity_returned) FROM equipment_returns rt WHERE rt.issue_id = i.issue_id), 0)
")->fetchColumn();

$recent_requests = $pdo->query("
    SELECT r.*, u.full_name, u.role, e.equipment_name
    FROM equipment_requests r
    JOIN users u ON r.user_id = u.user_id
    JOIN equipment e ON r.equipment_id = e.equipment_id
    ORDER BY r.request_date DESC
    LIMIT 8
")->fetchAll();

$page_title = 'Administrator Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2>Administrator Dashboard</h2>
    <p>Overview of sports equipment inventory, pending approvals, issued items, and overdue returns.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="label">Available / Total Stock</div>
        <div class="value"><?= $avail_equip ?> / <?= $total_equip ?></div>
    </div>
    <div class="stat-card warning">
        <div class="label">Pending Requests</div>
        <div class="value"><?= $pending_req ?></div>
    </div>
    <div class="stat-card success">
        <div class="label">Currently Issued Units</div>
        <div class="value"><?= $total_equip - $avail_equip ?></div>
    </div>
    <div class="stat-card danger">
        <div class="label">Overdue Issues</div>
        <div class="value"><?= $overdue_cnt ?></div>
    </div>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.85rem;">
        <h3>Recent Equipment Requests</h3>
        <a href="manage_requests.php" class="btn btn-sm">Manage All Requests</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Requester</th>
                <th>Equipment</th>
                <th>Qty</th>
                <th>Expected Return</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_requests as $r): ?>
            <tr>
                <td>#<?= (int)$r['request_id'] ?></td>
                <td><?= e($r['full_name']) ?> (<?= e($r['role']) ?>)</td>
                <td><?= e($r['equipment_name']) ?></td>
                <td><?= (int)$r['quantity_requested'] ?></td>
                <td><?= e($r['expected_return_date']) ?></td>
                <td><span class="badge badge-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
