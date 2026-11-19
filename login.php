<?php
// Handles the voter login form
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
start_session('VOTERSESSID');
csrf_check();

$voters_id = trim($_POST['voters_id'] ?? '');
$password  = $_POST['password'] ?? '';
$_SESSION['last_voter_id'] = $voters_id;

$stmt = $pdo->prepare('SELECT id, password FROM voters WHERE voters_id = ?');
$stmt->execute([$voters_id]);
$row = $stmt->fetch();

if (!$row || !password_verify($password, $row['password'])) {
    flash('error', 'That voter ID and password don\'t match. Check them and try again.');
    redirect('index.php');
}

session_regenerate_id(true);          // stop session fixation
unset($_SESSION['last_voter_id']);
$_SESSION['voter_id'] = $row['id'];
redirect('ballot.php');
