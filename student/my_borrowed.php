<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireStudentOrStaff();

$stmt = $pdo->prepare("
    SELECT i.*, r.status AS request_status, e.equipment_name,
           COALESCE((SELECT SUM(quantity_returned) FROM equipment_returns rt WHERE rt.issue_id = i.issue_id), 0) AS total_returned
    FROM equipment_issues i
    JOIN equipment_requests r ON i.request_id = r.request_id
    JOIN equipment e ON r.equipment_id = e.equipment_id
    WHERE r.user_id = ?
    ORDER BY i.issue_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$borrowed = $stmt->fetchAll();

$page_title = 'My Borrowed Equipment';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2>My Borrowed Equipment &amp; Due Dates</h2>
    <table>
        <thead>
            <tr>
                <th>Issue ID</th>
                <th>Equipment</th>
                <th>Issued Qty</th>
                <th>Returned Qty</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($borrowed as $b):
                $outstanding = (int)$b['quantity_issued'] - (int)$b['total_returned'];
                $is_overdue  = ($outstanding > 0 && $b['due_date'] < date('Y-m-d'));
            ?>
            <tr>
                <td>#<?= (int)$b['issue_id'] ?></td>
                <td><strong><?= e($b['equipment_name']) ?></strong></td>
                <td><?= (int)$b['quantity_issued'] ?></td>
                <td><?= (int)$b['total_returned'] ?></td>
                <td><?= e($b['issue_date']) ?></td>
                <td><?= e($b['due_date']) ?></td>
                <td>
                    <?php if ($outstanding <= 0): ?>
                        <span class="badge badge-completed">Returned</span>
                    <?php elseif ($is_overdue): ?>
                        <span class="badge badge-overdue">Overdue (<?= $outstanding ?> due)</span>
                    <?php else: ?>
                        <span class="badge badge-approved">Issued (<?= $outstanding ?> held)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
