<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: student/dashboard.php");
    }
    exit;
}
header("Location: login.php");
exit;
