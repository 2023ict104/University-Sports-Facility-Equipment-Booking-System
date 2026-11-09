<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireStudentOrStaff();

$stmt = $pdo->prepare("
    SELECT r.*, e.equipment_name, u.full_name AS reviewer_name
    FROM equipment_requests r
    JOIN equipment e ON r.equipment_id = e.equipment_id
    LEFT JOIN users u ON r.reviewed_by = u.user_id
    WHERE r.user_id = ?
    ORDER BY r.request_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll();

$page_title = 'My Equipment Requests';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2>My Equipment Requests</h2>
    <table>
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Equipment</th>
                <th>Quantity</th>
                <th>Request Date</th>
                <th>Expected Return</th>
                <th>Status</th>
                <th>Review Note</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $r): ?>
            <tr>
                <td>#<?= (int)$r['request_id'] ?></td>
                <td><strong><?= e($r['equipment_name']) ?></strong></td>
                <td><?= (int)$r['quantity_requested'] ?></td>
                <td><?= e($r['request_date']) ?></td>
                <td><?= e($r['expected_return_date']) ?></td>
                <td><span class="badge badge-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                <td><?= e($r['review_note'] ?: '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
