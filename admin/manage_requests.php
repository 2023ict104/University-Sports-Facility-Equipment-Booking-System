<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id  = (int)($_POST['request_id'] ?? 0);
    $new_status  = $_POST['status'] ?? '';
    $review_note = trim($_POST['review_note'] ?? '');

    if ($request_id && in_array($new_status, ['approved', 'rejected'], true)) {
        $stmt = $pdo->prepare("
            UPDATE equipment_requests
            SET status = ?, reviewed_by = ?, review_note = ?
            WHERE request_id = ? AND status = 'pending'
        ");
        $stmt->execute([$new_status, $_SESSION['user_id'], $review_note, $request_id]);
        $message = "Request #{$request_id} marked as {$new_status}.";
    }
}

$requests = $pdo->query("
    SELECT r.*, u.full_name, u.student_id, u.role, e.equipment_name, e.available_quantity
    FROM equipment_requests r
    JOIN users u ON r.user_id = u.user_id
    JOIN equipment e ON r.equipment_id = e.equipment_id
    ORDER BY FIELD(r.status, 'pending', 'approved', 'completed', 'rejected'), r.request_date DESC
")->fetchAll();

$page_title = 'Manage Equipment Requests';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2>Approve or Reject Equipment Requests</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Requester</th>
                <th>Equipment</th>
                <th>Req. Qty (Avail)</th>
                <th>Expected Return</th>
                <th>Status</th>
                <th>Review Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $r): ?>
            <tr>
                <td>#<?= (int)$r['request_id'] ?></td>
                <td><?= e($r['full_name']) ?><br><small><?= e($r['student_id'] ?: ucfirst($r['role'])) ?></small></td>
                <td><strong><?= e($r['equipment_name']) ?></strong></td>
                <td><?= (int)$r['quantity_requested'] ?> (Avail: <?= (int)$r['available_quantity'] ?>)</td>
                <td><?= e($r['expected_return_date']) ?></td>
                <td><span class="badge badge-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                <td>
                    <?php if ($r['status'] === 'pending'): ?>
                    <form method="post" style="display:flex; gap:0.4rem; align-items:center;">
                        <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
                        <input type="text" name="review_note" placeholder="Review note..." style="padding:0.3rem; font-size:0.8rem;">
                        <button type="submit" name="status" value="approved" class="btn btn-sm btn-success">Approve</button>
                        <button type="submit" name="status" value="rejected" class="btn btn-sm btn-danger">Reject</button>
                    </form>
                    <?php else: ?>
                        <small><?= e($r['review_note'] ?: 'Reviewed') ?></small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
