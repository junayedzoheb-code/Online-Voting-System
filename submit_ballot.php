<?php
// Validates and saves a voter's ballot
require_once __DIR__ . '/includes/session.php';
csrf_check();

$submitted = $_POST['votes'] ?? [];
if (!is_array($submitted)) {
    $submitted = [];
}

// Look up the real positions and candidates so we trust nothing from the form
$max_votes = $pdo->query('SELECT id, max_vote FROM positions')->fetchAll(PDO::FETCH_KEY_PAIR);
$cand_pos  = $pdo->query('SELECT id, position_id FROM candidates')->fetchAll(PDO::FETCH_KEY_PAIR);

$to_insert = [];
foreach ($submitted as $position_id => $candidate_ids) {
    $position_id = (int) $position_id;
    if (!isset($max_votes[$position_id]) || !is_array($candidate_ids)) {
        flash('error', 'Your ballot contained a position that doesn\'t exist. Please try again.');
        redirect('ballot.php');
    }
    $candidate_ids = array_unique(array_map('intval', $candidate_ids));

    if (count($candidate_ids) > $max_votes[$position_id]) {
        flash('error', 'You chose too many candidates for one position. Please try again.');
        redirect('ballot.php');
    }
    foreach ($candidate_ids as $cid) {
        if (($cand_pos[$cid] ?? null) !== $position_id) {
            flash('error', 'Your ballot contained a candidate who isn\'t running for that position. Please try again.');
            redirect('ballot.php');
        }
        $to_insert[] = [$voter['id'], $cid, $position_id];
    }
}

if (!$to_insert) {
    flash('error', 'Your ballot is empty. Mark at least one candidate before submitting.');
    redirect('ballot.php');
}

try {
    $pdo->beginTransaction();

    // Lock this voter's row so two quick submissions can't both get through
    $pdo->prepare('SELECT id FROM voters WHERE id = ? FOR UPDATE')->execute([$voter['id']]);

    $check = $pdo->prepare('SELECT COUNT(*) FROM votes WHERE voters_id = ?');
    $check->execute([$voter['id']]);
    if ($check->fetchColumn() > 0) {
        $pdo->rollBack();
        flash('info', 'You have already voted. Your first ballot is the one that counts.');
        redirect('ballot.php');
    }

    $insert = $pdo->prepare('INSERT INTO votes (voters_id, candidate_id, position_id) VALUES (?, ?, ?)');
    foreach ($to_insert as $row) {
        $insert->execute($row);
    }

    $pdo->commit();
    flash('success', 'Ballot submitted.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Your ballot couldn\'t be saved, and nothing was recorded. Please try again.');
}

redirect('ballot.php');
