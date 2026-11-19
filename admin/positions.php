<?php
// Add, edit and delete positions
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action      = $_POST['action'] ?? '';
    $id          = (int) ($_POST['id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $max_vote    = (int) ($_POST['max_vote'] ?? 1);

    try {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM positions WHERE id = ?')->execute([$id]);
            flash('success', 'Position deleted, along with its candidates and their votes.');
        } elseif ($description === '' || $max_vote < 1) {
            flash('error', 'Enter a position name and a vote limit of 1 or more.');
            redirect($action === 'update' ? "positions.php?edit=$id" : 'positions.php');
        } elseif ($action === 'add') {
            $pdo->prepare('INSERT INTO positions (description, max_vote) VALUES (?, ?)')
                ->execute([$description, $max_vote]);
            flash('success', "Position \"$description\" added.");
        } elseif ($action === 'update') {
            $pdo->prepare('UPDATE positions SET description = ?, max_vote = ? WHERE id = ?')
                ->execute([$description, $max_vote, $id]);
            flash('success', 'Position saved.');
        }
    } catch (PDOException $e) {
        flash('error', is_duplicate($e) ? "A position called \"$description\" already exists." : 'That change couldn\'t be saved.');
    }
    redirect('positions.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM positions WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

$positions = $pdo->query(
    'SELECT p.*, COUNT(c.id) AS n_candidates
       FROM positions p LEFT JOIN candidates c ON c.position_id = p.id
      GROUP BY p.id ORDER BY p.id'
)->fetchAll();

admin_header('Positions', 'positions');
?>
<div class="page-head"><h1>Positions</h1></div>

<form class="panel" method="post">
  <h2><?= $editing ? 'Edit position' : 'Add a position' ?></h2>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
  <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
  <div class="form-row">
    <div class="field">
      <label for="description">Position name</label>
      <input type="text" id="description" name="description" maxlength="100" required
             placeholder="e.g. President" value="<?= e($editing['description'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="max_vote">Votes allowed per voter</label>
      <input type="number" id="max_vote" name="max_vote" min="1" required value="<?= (int) ($editing['max_vote'] ?? 1) ?>">
    </div>
    <div class="form-actions">
      <button class="btn" type="submit"><?= $editing ? 'Save position' : 'Add position' ?></button>
      <?php if ($editing): ?><a class="btn btn-ghost" href="positions.php">Cancel</a><?php endif; ?>
    </div>
  </div>
</form>

<div class="table-wrap">
<table>
  <thead><tr><th>Position</th><th>Votes allowed</th><th>Candidates</th><th></th></tr></thead>
  <tbody>
  <?php if (!$positions): ?>
    <tr><td colspan="4" class="empty">No positions yet. Add the first one above.</td></tr>
  <?php endif; ?>
  <?php foreach ($positions as $p): ?>
    <tr>
      <td><?= e($p['description']) ?></td>
      <td><?= (int) $p['max_vote'] ?></td>
      <td><?= (int) $p['n_candidates'] ?></td>
      <td class="actions">
        <a class="btn btn-ghost btn-small" href="positions.php?edit=<?= (int) $p['id'] ?>">Edit</a>
        <form method="post" data-confirm="Delete &quot;<?= e($p['description']) ?>&quot;? Its candidates and all votes for it will be deleted too.">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <button class="btn btn-danger btn-small" type="submit">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php admin_footer();
