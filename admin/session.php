<?php
// Include at the top of every admin page. Uses its own session cookie,
// so being signed in as a voter never gives access to /admin.
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

start_session('ADMINSESSID');

if (empty($_SESSION['admin_id'])) {
    redirect('index.php');
}

$stmt = $pdo->prepare('SELECT id, username FROM admin WHERE id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

if (!$admin) {
    session_destroy();
    redirect('index.php');
}
