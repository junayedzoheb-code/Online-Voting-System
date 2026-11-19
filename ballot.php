<?php
// Shows the ballot, or the voter's receipt if they already voted
require_once __DIR__ . '/includes/session.php';

// Has this voter already voted?
$stmt = $pdo->prepare(
    'SELECT p.description AS position, CONCAT(c.firstname, " ", c.lastname) AS candidate
       FROM votes v
       JOIN positions  p ON p.id = v.position_id
       JOIN candidates c ON c.id = v.candidate_id
      WHERE v.voters_id = ?
      ORDER BY p.id, c.lastname'
);
$stmt->execute([$voter['id']]);
$my_votes = $stmt->fetchAll();
$has_voted = count($my_votes) > 0;

// Positions with their candidates
$positions  = $pdo->query('SELECT id, description, max_vote FROM positions ORDER BY id')->fetchAll();
$candidates = $pdo->query('SELECT id, firstname, lastname, position_id FROM candidates ORDER BY lastname, firstname')->fetchAll();
$by_position = [];
foreach ($candidates as $c) {
    $by_position[$c['position_id']][] = $c;
}

$page_title = 'Your ballot — Online Voting System';
include __DIR__ . '/includes/head.php';
?>
<body>
<header class="topbar">
  <strong>Online Voting System</strong>
  <span>
    <span class="muted"><?= e($voter['firstname'] . ' ' . $voter['lastname']) ?></span>
    &nbsp;<a class="btn btn-ghost btn-small" href="logout.php">Sign out</a>
  </span>
</header>

<main class="container">
  <?= show_flash() ?>

<?php if ($has_voted): ?>
  <section class="ballot">
    <div class="ballot-head">
      <h1>Your vote is recorded</h1>
      <p class="muted">Thank you for voting. Each voter can submit one ballot, so this is final.</p>
    </div>
    <?php foreach ($my_votes as $v): ?>
      <div class="receipt-row"><span><?= e($v['position']) ?></span><span><?= e($v['candidate']) ?></span></div>
    <?php endforeach; ?>
    <div class="ballot-foot"><a class="btn btn-ghost" href="logout.php">Sign out</a></div>
  </section>

<?php elseif (!$positions): ?>
  <section class="ballot">
    <div class="ballot-head">
      <h1>The ballot isn't ready yet</h1>
      <p class="muted">No positions have been set up for this election. Check back once the admin has added them.</p>
    </div>
  </section>

<?php else: ?>
  <form class="ballot" action="submit_ballot.php" method="post" id="ballot-form">
    <?= csrf_field() ?>
    <div class="ballot-head">
      <h1>Official ballot</h1>
      <p class="muted">Mark your choice for each position. You can leave a position blank. Once you submit, you can't change your ballot.</p>
    </div>

    <?php foreach ($positions as $p):
        $max  = (int) $p['max_vote'];
        $type = $max === 1 ? 'radio' : 'checkbox';
    ?>
      <div class="race" role="group" aria-labelledby="pos-<?= (int) $p['id'] ?>" data-max="<?= $max ?>">
        <div class="race-head">
          <h2 id="pos-<?= (int) $p['id'] ?>"><?= e($p['description']) ?></h2>
          <span class="rule-text"><?= $max === 1 ? 'Choose 1' : 'Choose up to ' . $max ?></span>
        </div>

        <?php if (empty($by_position[$p['id']])): ?>
          <div class="choice is-disabled">No candidates for this position.</div>
        <?php else: foreach ($by_position[$p['id']] as $c): ?>
          <label class="choice">
            <input type="<?= $type ?>" name="votes[<?= (int) $p['id'] ?>][]" value="<?= (int) $c['id'] ?>">
            <span class="name"><?= e($c['firstname'] . ' ' . $c['lastname']) ?></span>
            <span class="mark" aria-hidden="true">✓</span>
          </label>
        <?php endforeach; endif; ?>

        <?php if ($type === 'radio' && !empty($by_position[$p['id']])): ?>
          <div class="race-clear">
            <button type="button" class="btn btn-ghost btn-small clear-choice">Clear choice</button>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <div class="ballot-foot">
      <span class="muted" id="summary"></span>
      <button class="btn" type="submit">Submit ballot</button>
    </div>
  </form>
<?php endif; ?>
</main>

<script>
// Limit checkboxes to max_vote per position and show live counts.
document.querySelectorAll('.race').forEach(function (race) {
  var max = parseInt(race.dataset.max, 10);
  var inputs = race.querySelectorAll('input');
  var ruleText = race.querySelector('.rule-text');
  var base = ruleText.textContent;

  function update() {
    var picked = race.querySelectorAll('input:checked').length;
    if (max > 1) {
      inputs.forEach(function (i) {
        i.disabled = !i.checked && picked >= max;
        i.closest('.choice').classList.toggle('is-disabled', i.disabled);
      });
      ruleText.textContent = picked ? base + ' — ' + picked + ' chosen' : base;
    }
    ruleText.classList.toggle('full', picked === max);
  }
  inputs.forEach(function (i) { i.addEventListener('change', update); });

  var clear = race.querySelector('.clear-choice');
  if (clear) clear.addEventListener('click', function () {
    inputs.forEach(function (i) { i.checked = false; });
    update();
  });
});

var form = document.getElementById('ballot-form');
if (form) form.addEventListener('submit', function (ev) {
  var picked = form.querySelectorAll('input[type=radio]:checked, input[type=checkbox]:checked').length;
  if (picked === 0) {
    ev.preventDefault();
    alert('Your ballot is empty. Mark at least one candidate before submitting.');
    return;
  }
  if (!confirm('Submit your ballot? You won\'t be able to change it afterwards.')) ev.preventDefault();
});
</script>
</body>
</html>
