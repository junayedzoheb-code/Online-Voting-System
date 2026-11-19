<?php
// Include at the top of every page a voter must be logged in to see.
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

start_session('VOTERSESSID');

if (empty($_SESSION['voter_id'])) {
    redirect('index.php');
}

$stmt = $pdo->prepare('SELECT id, voters_id, firstname, lastname FROM voters WHERE id = ?');
$stmt->execute([$_SESSION['voter_id']]);
$voter = $stmt->fetch();

if (!$voter) {               // account was deleted while logged in
    session_destroy();
    redirect('index.php');
}
