<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$message = '';
$error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (!$full_name || !$email || !$password) {
        $error = 'All fields are required to create a staff account.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (student_id, full_name, email, password_hash, role, status) VALUES (NULL, ?, ?, ?, 'staff', 'active')");
            $stmt->execute([$full_name, $email, $hash]);
            $message = "Staff account for {$full_name} created successfully.";
        } catch (PDOException $e) {
            $error = 'Email address is already registered.';
        }
    }
}
$page_title = 'Add Staff Account';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width: 580px;">
    <h2>Add University Staff Account</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label>Staff Full Name &amp; Designation</label>
            <input type="text" name="full_name" required>
        </div>
        <div class="form-group">
            <label>Official Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Initial Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-success">Create Staff Account</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
