<?php
// Add, edit and delete candidates
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/layout.php';

$positions = $pdo->query('SELECT id, description FROM positions ORDER BY id')->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action      = $_POST['action'] ?? '';
    $id          = (int) ($_POST['id'] ?? 0);
    $firstname   = trim($_POST['firstname'] ?? '');
    $lastname    = trim($_POST['lastname'] ?? '');
    $position_id = (int) ($_POST['position_id'] ?? 0);

    try {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM candidates WHERE id = ?')->execute([$id]);
            flash('success', 'Candidate deleted, along with their votes.');
        } elseif ($firstname === '' || $lastname === '' || !isset($positions[$position_id])) {
            flash('error', 'Enter a first name, last name and position.');
            redirect($action === 'update' ? "candidates.php?edit=$id" : 'candidates.php');
        } elseif ($action === 'add') {
            $pdo->prepare('INSERT INTO candidates (firstname, lastname, position_id) VALUES (?, ?, ?)')
                ->execute([$firstname, $lastname, $position_id]);
            flash('success', "$firstname $lastname added.");
        } elseif ($action === 'update') {
            $pdo->beginTransaction();
            // Votes were cast for the old position, so they can't follow the
            // candidate to a new one.
            $pdo->prepare('DELETE FROM votes WHERE candidate_id = ? AND position_id <> ?')
                ->execute([$id, $position_id]);
            $pdo->prepare('UPDATE candidates SET firstname = ?, lastname = ?, position_id = ? WHERE id = ?')
                ->execute([$firstname, $lastname, $position_id, $id]);
            $pdo->commit();
            flash('success', 'Candidate saved.');
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'That change couldn\'t be saved.');
    }
    redirect('candidates.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM candidates WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

$candidates = $pdo->query(
    'SELECT c.*, p.description AS position, COUNT(v.id) AS n_votes
       FROM candidates c
       JOIN positions p ON p.id = c.position_id
       LEFT JOIN votes v ON v.candidate_id = c.id
      GROUP BY c.id ORDER BY p.id, c.lastname, c.firstname'
)->fetchAll();

admin_header('Candidates', 'candidates');
?>
<div class="page-head"><h1>Candidates</h1></div>

<?php if (!$positions): ?>
  <div class="panel empty">Candidates run for a position. <a href="positions.php">Add a position</a> first.</div>
<?php else: ?>
<form class="panel" method="post">
  <h2><?= $editing ? 'Edit candidate' : 'Add a candidate' ?></h2>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
  <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
  <div class="form-row">
    <div class="field">
      <label for="firstname">First name</label>
      <input type="text" id="firstname" name="firstname" maxlength="50" required value="<?= e($editing['firstname'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="lastname">Last name</label>
      <input type="text" id="lastname" name="lastname" maxlength="50" required value="<?= e($editing['lastname'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="position_id">Position</label>
      <select id="position_id" name="position_id" required>
        <?php foreach ($positions as $pid => $pname): ?>
          <option value="<?= $pid ?>"<?= ($editing['position_id'] ?? null) == $pid ? ' selected' : '' ?>><?= e($pname) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-actions">
      <button class="btn" type="submit"><?= $editing ? 'Save candidate' : 'Add candidate' ?></button>
      <?php if ($editing): ?><a class="btn btn-ghost" href="candidates.php">Cancel</a><?php endif; ?>
    </div>
  </div>
  <?php if ($editing): ?>
    <p class="hint">Moving a candidate to a different position deletes the votes they already received.</p>
  <?php endif; ?>
</form>
<?php endif; ?>

<div class="table-wrap">
<table>
  <thead><tr><th>Name</th><th>Position</th><th>Votes</th><th></th></tr></thead>
  <tbody>
  <?php if (!$candidates): ?>
    <tr><td colspan="4" class="empty">No candidates yet.</td></tr>
  <?php endif; ?>
  <?php foreach ($candidates as $c): ?>
    <tr>
      <td><?= e($c['firstname'] . ' ' . $c['lastname']) ?></td>
      <td><?= e($c['position']) ?></td>
      <td><?= (int) $c['n_votes'] ?></td>
      <td class="actions">
        <a class="btn btn-ghost btn-small" href="candidates.php?edit=<?= (int) $c['id'] ?>">Edit</a>
        <form method="post" data-confirm="Delete <?= e($c['firstname'] . ' ' . $c['lastname']) ?>? Their votes will be deleted too.">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
          <button class="btn btn-danger btn-small" type="submit">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php admin_footer();
