<?php
// Admin dashboard: turnout and live results
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/layout.php';

$total_voters = (int) $pdo->query('SELECT COUNT(*) FROM voters')->fetchColumn();
$voted        = (int) $pdo->query('SELECT COUNT(DISTINCT voters_id) FROM votes')->fetchColumn();
$n_positions  = (int) $pdo->query('SELECT COUNT(*) FROM positions')->fetchColumn();
$n_candidates = (int) $pdo->query('SELECT COUNT(*) FROM candidates')->fetchColumn();
$turnout      = $total_voters ? round($voted / $total_voters * 100) : 0;

$rows = $pdo->query(
    'SELECT p.id AS position_id, p.description AS position,
            CONCAT(c.firstname, " ", c.lastname) AS candidate,
            COUNT(v.id) AS total_votes
       FROM candidates c
       JOIN positions p ON p.id = c.position_id
       LEFT JOIN votes v ON v.candidate_id = c.id
      GROUP BY p.id, p.description, c.id, c.firstname, c.lastname
      ORDER BY p.id, total_votes DESC, candidate'
)->fetchAll();
$results = [];
foreach ($rows as $r) {
    $results[$r['position_id']]['name'] = $r['position'];
    $results[$r['position_id']]['candidates'][] = $r;
}

admin_header('Dashboard', 'home');
?>
<div class="page-head"><h1>Dashboard</h1></div>

<div class="stats">
  <div class="stat"><div class="value"><?= $voted ?> / <?= $total_voters ?></div><div class="label">Voters who have voted (<?= $turnout ?>%)</div></div>
  <div class="stat"><div class="value"><?= $n_positions ?></div><div class="label">Positions</div></div>
  <div class="stat"><div class="value"><?= $n_candidates ?></div><div class="label">Candidates</div></div>
</div>

<h2>Results</h2>
<?php if (!$results): ?>
  <div class="panel empty">No candidates yet. <a href="positions.php">Add a position</a>, then add candidates to it.</div>
<?php endif; ?>

<?php foreach ($results as $pos): 
    $top = max(array_column($pos['candidates'], 'total_votes'));
    $sum = array_sum(array_column($pos['candidates'], 'total_votes'));
?>
  <section class="panel">
    <h3><?= e($pos['name']) ?></h3>
    <p class="muted"><?= $sum ?> vote<?= $sum == 1 ? '' : 's' ?> cast</p>
    <?php foreach ($pos['candidates'] as $c):
        $pct = $sum ? round($c['total_votes'] / $sum * 100) : 0;
        $leading = $top > 0 && $c['total_votes'] == $top;
    ?>
      <div class="result">
        <div class="result-top">
          <span class="<?= $leading ? 'leader' : '' ?>"><?= e($c['candidate']) ?><?= $leading ? ' (leading)' : '' ?></span>
          <span><?= (int) $c['total_votes'] ?> (<?= $pct ?>%)</span>
        </div>
        <div class="bar"><span style="width: <?= $pct ?>%"></span></div>
      </div>
    <?php endforeach; ?>
  </section>
<?php endforeach; ?>
<?php admin_footer();
