<?php
// Handles the admin login form
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
start_session('ADMINSESSID');
csrf_check();

$stmt = $pdo->prepare('SELECT id, password FROM admin WHERE username = ?');
$stmt->execute([trim($_POST['username'] ?? '')]);
$row = $stmt->fetch();

if (!$row || !password_verify($_POST['password'] ?? '', $row['password'])) {
    flash('error', 'That username and password don\'t match. Check them and try again.');
    redirect('index.php');
}

session_regenerate_id(true);
$_SESSION['admin_id'] = $row['id'];
redirect('home.php');
