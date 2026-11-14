<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireStudentOrStaff();

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipment_id         = (int)($_POST['equipment_id'] ?? 0);
    $quantity_requested   = (int)($_POST['quantity_requested'] ?? 0);
    $expected_return_date = $_POST['expected_return_date'] ?? '';

    $stmt = $pdo->prepare("SELECT available_quantity FROM equipment WHERE equipment_id = ? AND active = 1");
    $stmt->execute([$equipment_id]);
    $eq = $stmt->fetch();

    if (!$eq) {
        $error = 'Selected equipment does not exist or is inactive.';
    } elseif ($quantity_requested <= 0 || $quantity_requested > (int)$eq['available_quantity']) {
        $error = 'Requested quantity exceeds available stock (' . (int)$eq['available_quantity'] . ').';
    } elseif (empty($expected_return_date) || $expected_return_date < date('Y-m-d')) {
        $error = 'Please choose a valid expected return date.';
    } else {
        $ins = $pdo->prepare("
            INSERT INTO equipment_requests (user_id, equipment_id, quantity_requested, request_date, expected_return_date, status)
            VALUES (?, ?, ?, NOW(), ?, 'pending')
        ");
        $ins->execute([$_SESSION['user_id'], $equipment_id, $quantity_requested, $expected_return_date]);
        $message = 'Equipment borrowing request submitted successfully!';
    }
}

$items = $pdo->query("
    SELECT e.*, c.category_name
    FROM equipment e
    JOIN equipment_categories c ON e.category_id = c.category_id
    WHERE e.active = 1 AND e.available_quantity > 0
    ORDER BY e.equipment_name
")->fetchAll();

$selected_id = (int)($_GET['equipment_id'] ?? 0);
$page_title = 'Request Equipment';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width: 640px;">
    <h2>Submit Equipment Borrowing Request</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" data-validate="request">
        
<div class="form-group">
    <label>Select Sports Equipment</label>

    <input type="text"
           id="equipmentSearch"
           placeholder="Type to search equipment..."
           autocomplete="off"
           style="margin-bottom: 8px;">

    <select name="equipment_id" id="equipmentSelect" required>
        <option value="">-- Choose Equipment --</option>

        <?php foreach ($items as $item): ?>
            <option value="<?= (int)$item['equipment_id'] ?>"
                    data-available="<?= (int)$item['available_quantity'] ?>"
                    <?= $selected_id === (int)$item['equipment_id'] ? 'selected' : '' ?>>
                <?= e($item['equipment_name']) ?>
                (<?= e($item['category_name']) ?> — Avail: <?= (int)$item['available_quantity'] ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>

        <div class="form-group">
            <label>Quantity Requested</label>
            <input type="number" name="quantity_requested" min="1" value="1" required>
        </div>
        <div class="form-group">
            <label>Expected Return Date</label>
            <input type="date" name="expected_return_date" min="<?= date('Y-m-d') ?>" required>
        </div>
        <button type="submit" class="btn">Submit Request</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
