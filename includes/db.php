<?php
// ------------------------------------------------------------
// Database connection (shared by the voter and admin sides).
// Edit these four values to match your MySQL setup.
// XAMPP default: user "root" with an empty password.
// ------------------------------------------------------------
$DB_HOST = 'sql308.infinityfree.com';   
$DB_NAME = 'if0_42993984_votesystem';
$DB_USER = 'if0_42993984';
$DB_PASS = 'suss6969';     

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Could not connect to the database. Check the settings in includes/db.php.');
}
