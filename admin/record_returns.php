<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issue_id            = (int)($_POST['issue_id'] ?? 0);
    $quantity_returned   = (int)($_POST['quantity_returned'] ?? 0);
    $condition_on_return = trim($_POST['condition_on_return'] ?? 'Good');
    $notes               = trim($_POST['notes'] ?? '');

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            SELECT i.*, r.equipment_id, r.request_id,
                   COALESCE((SELECT SUM(quantity_returned) FROM equipment_returns rt WHERE rt.issue_id = i.issue_id), 0) AS already_returned
            FROM equipment_issues i
            JOIN equipment_requests r ON i.request_id = r.request_id
            WHERE i.issue_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$issue_id]);
        $issue = $stmt->fetch();

        if (!$issue) {
            throw new Exception('Issue record not found.');
        }
        $remaining = (int)$issue['quantity_issued'] - (int)$issue['already_returned'];
        if ($quantity_returned <= 0 || $quantity_returned > $remaining) {
            throw new Exception("Return quantity must be between 1 and {$remaining}.");
        }

        $ins = $pdo->prepare("
            INSERT INTO equipment_returns (issue_id, received_by, return_date, quantity_returned, condition_on_return, notes)
            VALUES (?, ?, NOW(), ?, ?, ?)
        ");
        $ins->execute([$issue_id, $_SESSION['user_id'], $quantity_returned, $condition_on_return, $notes]);

        $pdo->prepare("
            UPDATE equipment
            SET available_quantity = available_quantity + ?, condition_status = ?
            WHERE equipment_id = ?
        ")->execute([$quantity_returned, $condition_on_return, $issue['equipment_id']]);

        if (((int)$issue['already_returned'] + $quantity_returned) >= (int)$issue['quantity_issued']) {
            $pdo->prepare("UPDATE equipment_requests SET status = 'completed' WHERE request_id = ?")
                ->execute([$issue['request_id']]);
        }

        $pdo->commit();
        $message = "Return recorded for Issue #{$issue_id}. Stock and request status updated.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

$active_issues = $pdo->query("
    SELECT i.*, u.full_name, e.equipment_name,
           COALESCE((SELECT SUM(quantity_returned) FROM equipment_returns rt WHERE rt.issue_id = i.issue_id), 0) AS already_returned
    FROM equipment_issues i
    JOIN equipment_requests r ON i.request_id = r.request_id
    JOIN users u ON r.user_id = u.user_id
    JOIN equipment e ON r.equipment_id = e.equipment_id
    HAVING (i.quantity_issued - already_returned) > 0
    ORDER BY i.due_date ASC
")->fetchAll();

$page_title = 'Record Equipment Returns';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2>Record Returned Equipment</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <table>
        <thead>
            <tr>
                <th>Issue ID</th>
                <th>Borrower</th>
                <th>Equipment</th>
                <th>Issued / Returned</th>
                <th>Due Date</th>
                <th>Return Entry</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($active_issues as $i):
                $rem = (int)$i['quantity_issued'] - (int)$i['already_returned'];
            ?>
            <tr>
                <td>#<?= (int)$i['issue_id'] ?></td>
                <td><?= e($i['full_name']) ?></td>
                <td><strong><?= e($i['equipment_name']) ?></strong></td>
                <td><?= (int)$i['quantity_issued'] ?> / <?= (int)$i['already_returned'] ?> (Rem: <?= $rem ?>)</td>
                <td><?= e($i['due_date']) ?></td>
                <td>
                    <form method="post" style="display:flex; gap:0.4rem; align-items:center;">
                        <input type="hidden" name="issue_id" value="<?= (int)$i['issue_id'] ?>">
                        <input type="number" name="quantity_returned" value="<?= $rem ?>" min="1" max="<?= $rem ?>" style="width:65px;" required>
                        <select name="condition_on_return">
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Damaged">Damaged</option>
                        </select>
                        <input type="text" name="notes" placeholder="Return notes..." style="width:140px;">
                        <button type="submit" class="btn btn-sm">Record Return</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
