<?php

 //Authentication & Role-Based Access Helpers

 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getAppBasePath(): string {
    // Dynamically detects the base URL path regardless of what folder name is used in htdocs
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (preg_match('#^(/[^/]+)#', $script, $matches)) {
        // If placed in a subfolder like /sports_equipment_system or /sports
        $folder = $matches[1];
        if (!in_array($folder, ['/admin', '/student', '/includes', '/config', '/assets'], true)) {
            return $folder;
        }
    }
    return '';
}

function appUrl(string $path): string {
    $base = getAppBasePath();
    $cleanPath = '/' . ltrim($path, '/');
    return $base . $cleanPath;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header("Location: " . appUrl('/login.php'));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: " . appUrl('/student/dashboard.php'));
        exit;
    }
}

function requireStudentOrStaff(): void {
    requireLogin();
    if ($_SESSION['role'] === 'admin') {
        // Prevent administrators from accessing student portal pages
        header("Location: " . appUrl('/admin/dashboard.php'));
        exit;
    }
    if (!in_array($_SESSION['role'], ['student', 'staff'], true)) {
        header("Location: " . appUrl('/login.php'));
        exit;
    }
}

function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
