<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$message = '';
$error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (!$student_id || !$full_name || !$email || !$password) {
        $error = 'All fields are required to create a student account.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (student_id, full_name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'student', 'active')");
            $stmt->execute([$student_id, $full_name, $email, $hash]);
            $message = "Student account for {$full_name} ({$student_id}) created successfully.";
        } catch (PDOException $e) {
            $error = 'Duplicate Student ID or Email address.';
        }
    }
}
$page_title = 'Add Student Account';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width: 580px;">
    <h2>Add Student Account</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label>University Student Registration No (e.g. 2023/ICT/001)</label>
            <input type="text" name="student_id" required>
        </div>
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required>
        </div>
        <div class="form-group">
            <label>University Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Initial Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Create Student Account</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
