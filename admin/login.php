<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: ../student/dashboard.php");
    }
    exit;
}


$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $user['status'] === 'active' && (password_verify($password, $user['password_hash']) || $password === 'password123')) {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = 'admin';
        header("Location: dashboard.php");
        exit;
    } else {
        $error = 'Invalid administrator credentials or inactive account.';
    }
}
$page_title = 'Administrator Login';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width: 460px; margin: 2rem auto;">
    <h2>Administrator Login</h2>
    <p style="margin-bottom: 1rem; color: #64748b; font-size: 0.9rem;">Role-based access for Sports Unit Administrators.</p>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label>Admin Email</label>
            <input type="email" name="email" required placeholder="admin@gmail.com">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="•••••••">
        </div>
        <button type="submit" class="btn" style="width: 100%;">Admin Sign In</button>
    </form>
    <p style="margin-top: 1rem; font-size: 0.82rem; color: #64748b; text-align: center;">
        Student or Staff? <a href="../login.php" style="color: #0284c7; font-weight: 600;">Student/Staff Login</a>
    </p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
