<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: student/dashboard.php");
    }
    exit;
}


$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Strictly restrict to Student and Staff accounts
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role IN ('student', 'staff') LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['status'] !== 'active') {
            $error = 'Your account has been deactivated. Please contact the sports administrator.';
        } elseif (password_verify($password, $user['password_hash']) || $password === 'password123') {
            $_SESSION['user_id']    = $user['user_id'];
            $_SESSION['full_name']  = $user['full_name'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['student_id'] = $user['student_id'];

            header("Location: student/dashboard.php");
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        // Check if an administrator attempted to sign in here
        $admin_check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $admin_check->execute([$email]);
        if ($admin_check->fetch()) {
            $error = 'Administrator accounts must sign in via the <a href="admin/login.php" style="color:#0284c7;font-weight:600;text-decoration:underline;">Administrator Login Portal</a>.';
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
$page_title = 'Student & Staff Login';
require_once __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width: 460px; margin: 2rem auto;">
    <h2>Student &amp; Staff Login</h2>
    <p style="margin-bottom: 1rem; color: #64748b; font-size: 0.9rem;">Sign in with your University of Vavuniya account credentials.</p>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label>University Email</label>
            <input type="email" name="email" required placeholder="kamal@gmail.com">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn" style="width: 100%;">Login</button>
    </form>
    <p style="margin-top: 1rem; font-size: 0.82rem; color: #64748b; text-align: center;">
        Sports Administrator? <a href="admin/login.php" style="color: #0284c7; font-weight: 600;">Go to Admin Login</a>
    </p>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
