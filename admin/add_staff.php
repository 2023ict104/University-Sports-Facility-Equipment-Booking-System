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
            <div style="position: relative; display: flex; align-items: center;">
                <input type="password" name="password" id="password" required style="width: 100%; padding-right: 40px;">
                <button type="button" id="togglePassword" style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #64748b; display: flex; align-items: center; justify-content: center; padding: 0;" title="Show/Hide Password">
                    
                    <svg id="eyeIcon" style="width: 20px; height: 20px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-success">Create Staff Account</button>
    </form>
</div>


<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const eyeIcon = document.querySelector('#eyeIcon');

    togglePassword.addEventListener('click', function () {
        // Toggle the type attribute
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);

        
        if (type === 'text') {
            eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
            `;
        } else {
            eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            `;
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
