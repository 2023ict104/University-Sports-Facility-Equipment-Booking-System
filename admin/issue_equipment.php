<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id      = (int)($_POST['request_id'] ?? 0);
    $quantity_issued = (int)($_POST['quantity_issued'] ?? 0);
    $due_date        = $_POST['due_date'] ?? '';

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, e.available_quantity, e.equipment_name
            FROM equipment_requests r
            JOIN equipment e ON r.equipment_id = e.equipment_id
            WHERE r.request_id = ? AND r.status = 'approved'
            FOR UPDATE
        ");
        $stmt->execute([$request_id]);
        $req = $stmt->fetch();

        if (!$req) {
            throw new Exception('Approved request not found or already issued.');
        }
        if ($quantity_issued <= 0 || $quantity_issued > (int)$req['available_quantity']) {
            throw new Exception('Cannot issue beyond available stock (' . (int)$req['available_quantity'] . ').');
        }

        $ins = $pdo->prepare("
            INSERT INTO equipment_issues (request_id, issued_by, issue_date, due_date, quantity_issued)
            VALUES (?, ?, NOW(), ?, ?)
        ");
        $ins->execute([$request_id, $_SESSION['user_id'], $due_date, $quantity_issued]);

        $upd = $pdo->prepare("
            UPDATE equipment
            SET available_quantity = available_quantity - ?
            WHERE equipment_id = ?
        ");
        $upd->execute([$quantity_issued, $req['equipment_id']]);

        $pdo->commit();
        $message = "Equipment issued for Request #{$request_id} and available stock updated.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

$approved_unissued = $pdo->query("
    SELECT r.*, u.full_name, e.equipment_name, e.available_quantity
    FROM equipment_requests r
    JOIN users u ON r.user_id = u.user_id
    JOIN equipment e ON r.equipment_id = e.equipment_id
    LEFT JOIN equipment_issues i ON r.request_id = i.request_id
    WHERE r.status = 'approved' AND i.issue_id IS NULL
    ORDER BY r.request_date ASC
")->fetchAll();

$page_title = 'Issue Equipment';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2>Issue Approved Equipment</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <table>
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Borrower</th>
                <th>Equipment</th>
                <th>Requested Qty</th>
                <th>Available Stock</th>
                <th>Issue Form</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($approved_unissued as $r): ?>
            <tr>
                <td>#<?= (int)$r['request_id'] ?></td>
                <td><?= e($r['full_name']) ?></td>
                <td><strong><?= e($r['equipment_name']) ?></strong></td>
                <td><?= (int)$r['quantity_requested'] ?></td>
                <td><?= (int)$r['available_quantity'] ?></td>
                <td>
                    <form method="post" style="display:flex; gap:0.4rem; align-items:center;">
                        <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
                        <input type="number" name="quantity_issued" value="<?= (int)$r['quantity_requested'] ?>" min="1" max="<?= (int)$r['available_quantity'] ?>" style="width:70px;" required>
                        <input type="date" name="due_date" value="<?= e($r['expected_return_date']) ?>" required>
                        <button type="submit" class="btn btn-sm btn-success">Hand Over</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
