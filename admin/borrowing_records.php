<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$records = $pdo->query("
    SELECT i.*, r.request_id, r.status AS request_status, u.full_name, u.student_id, u.role, e.equipment_name,
           COALESCE((SELECT SUM(quantity_returned) FROM equipment_returns rt WHERE rt.issue_id = i.issue_id), 0) AS returned_qty
    FROM equipment_issues i
    JOIN equipment_requests r ON i.request_id = r.request_id
    JOIN users u ON r.user_id = u.user_id
    JOIN equipment e ON r.equipment_id = e.equipment_id
    ORDER BY i.issue_date DESC
")->fetchAll();

$page_title = 'Borrowing & Overdue Records';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2>Complete Borrowing, Return &amp; Overdue Records</h2>
    <table>
        <thead>
            <tr>
                <th>Issue ID</th>
                <th>Borrower</th>
                <th>Equipment</th>
                <th>Issued Qty</th>
                <th>Returned Qty</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $rec):
                $outstanding = (int)$rec['quantity_issued'] - (int)$rec['returned_qty'];
                $is_overdue  = ($outstanding > 0 && $rec['due_date'] < date('Y-m-d'));
            ?>
            <tr>
                <td>#<?= (int)$rec['issue_id'] ?></td>
                <td><?= e($rec['full_name']) ?> (<?= e($rec['student_id'] ?: ucfirst($rec['role'])) ?>)</td>
                <td><strong><?= e($rec['equipment_name']) ?></strong></td>
                <td><?= (int)$rec['quantity_issued'] ?></td>
                <td><?= (int)$rec['returned_qty'] ?></td>
                <td><?= e($rec['issue_date']) ?></td>
                <td><?= e($rec['due_date']) ?></td>
                <td>
                    <?php if ($outstanding <= 0): ?>
                        <span class="badge badge-completed">Returned</span>
                    <?php elseif ($is_overdue): ?>
                        <span class="badge badge-overdue">Overdue (<?= $outstanding ?> out)</span>
                    <?php else: ?>
                        <span class="badge badge-approved">Issued (<?= $outstanding ?> out)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
