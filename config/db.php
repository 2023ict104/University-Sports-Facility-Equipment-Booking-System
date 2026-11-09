<?php
// Database Configuration & PDO Connection

$db_host = 'localhost';
$db_name = 'sports_equipment_db';
$db_user = 'root';
$db_pass = ''; // Default XAMPP MySQL password is empty

try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()) .
        "<br>Please make sure MySQL is running in XAMPP and <code>database/sports_equipment_db.sql</code> has been imported.");
}
