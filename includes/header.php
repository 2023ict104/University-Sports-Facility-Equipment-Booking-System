<?php
require_once __DIR__ . '/auth.php';
$current_role = $_SESSION['role'] ?? '';
$full_name    = $_SESSION['full_name'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' | ' : '' ?>UoV Sports Equipment Booking System</title>
    <link rel="stylesheet" href="<?= appUrl('/assets/css/style.css?v=' . time()) ?>">
</head>
<body>
<header class="topbar">
    <div class="brand">
        <span class="brand-badge">UOV</span>
        <div>
            <h1>University Sports Equipment Booking System</h1>
        </div>
    </div>
    <?php if (isLoggedIn()): ?>
    <nav class="nav-links" style="display:flex; align-items:center; justify-content:space-between; width:100%; gap:1rem;">
        <!-- Left Side: Page Navigation Links -->
        <div class="nav-pages" style="display:flex; flex-wrap:wrap; align-items:center; gap:0.45rem;">
            <?php if ($current_role === 'admin'): ?>
                <a href="<?= appUrl('/admin/dashboard.php') ?>">Dashboard</a>
                <a href="<?= appUrl('/admin/manage_users.php') ?>">Users</a>
                <a href="<?= appUrl('/admin/manage_categories.php') ?>">Categories</a>
                <a href="<?= appUrl('/admin/manage_equipment.php') ?>">Equipment</a>
                <a href="<?= appUrl('/admin/manage_requests.php') ?>">Requests</a>
                <a href="<?= appUrl('/admin/issue_equipment.php') ?>">Issue</a>
                <a href="<?= appUrl('/admin/record_returns.php') ?>">Returns</a>
                <a href="<?= appUrl('/admin/borrowing_records.php') ?>">Records</a>
            <?php else: ?>
                <a href="<?= appUrl('/student/dashboard.php') ?>">Dashboard</a>
                <a href="<?= appUrl('/student/equipment_list.php') ?>">Equipment List</a>
                <a href="<?= appUrl('/student/request_equipment.php') ?>">Request Equipment</a>
                <a href="<?= appUrl('/student/my_requests.php') ?>">My Requests</a>
                <a href="<?= appUrl('/student/my_borrowed.php') ?>">My Borrowed</a>
            <?php endif; ?>
        </div>

        <!-- Right Side: User Name, Change Password, and Logout -->
        <div class="nav-user" style="margin-left:auto; display:inline-flex; align-items:center; gap:0.65rem; flex-shrink:0;">
            <span class="user-pill"><?= e($full_name) ?> (<?= e(ucfirst($current_role)) ?>)</span>
            <a href="<?= appUrl('/change_password.php') ?>" class="btn-sm btn-change-pwd" title="Change your account password">&#128273; Change Password</a>
            <a href="<?= appUrl('/logout.php') ?>" class="btn-logout">Logout</a>
        </div>
    </nav>
    <?php endif; ?>
</header>
<main class="container">
