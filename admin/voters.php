<?php
// Add, edit and delete voters
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action    = $_POST['action'] ?? '';
    $id        = (int) ($_POST['id'] ?? 0);
    $voters_id = strtoupper(trim($_POST['voters_id'] ?? ''));
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $password  = $_POST['password'] ?? '';

    try {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM voters WHERE id = ?')->execute([$id]);
            flash('success', 'Voter deleted, along with any votes they cast.');
        } elseif ($firstname === '' || $lastname === '') {
            flash('error', 'Enter the voter\'s first and last name.');
            redirect($action === 'update' ? "voters.php?edit=$id" : 'voters.php');
        } elseif ($password !== '' && strlen($password) < 6) {
            flash('error', 'Passwords need at least 6 characters.');
            redirect($action === 'update' ? "voters.php?edit=$id" : 'voters.php');
        } elseif ($action === 'add') {
            $generated_id = $voters_id === '';
            $plain = $password !== '' ? $password : new_password();
            $hash  = password_hash($plain, PASSWORD_DEFAULT);
            if ($generated_id) {
                [, $vid] = create_voter_with_generated_id($pdo, $firstname, $lastname, $hash);
            } else {
                $vid = $voters_id;
                $pdo->prepare('INSERT INTO voters (voters_id, password, firstname, lastname) VALUES (?, ?, ?, ?)')
                    ->execute([$vid, $hash, $firstname, $lastname]);
            }
            flash('success', "$firstname $lastname added. Voter ID: $vid   Password: $plain   — give these to the voter now; the password won't be shown again.");
        } elseif ($action === 'update') {
            if ($voters_id === '') {
                flash('error', 'Voter ID can\'t be empty.');
                redirect("voters.php?edit=$id");
            }
            $pdo->prepare('UPDATE voters SET voters_id = ?, firstname = ?, lastname = ? WHERE id = ?')
                ->execute([$voters_id, $firstname, $lastname, $id]);
            if ($password !== '') {
                $pdo->prepare('UPDATE voters SET password = ? WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            }
            flash('success', 'Voter saved' . ($password !== '' ? ' with the new password.' : '.'));
        }
    } catch (PDOException $e) {
        flash('error', is_duplicate($e) ? "Voter ID $voters_id is already in use. Choose a different one." : 'That change couldn\'t be saved.');
    }
    redirect('voters.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT id, voters_id, firstname, lastname FROM voters WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

$voters = $pdo->query(
    'SELECT v.id, v.voters_id, v.firstname, v.lastname,
            EXISTS(SELECT 1 FROM votes x WHERE x.voters_id = v.id) AS has_voted
       FROM voters v ORDER BY v.lastname, v.firstname'
)->fetchAll();

admin_header('Voters', 'voters');
?>
<div class="page-head"><h1>Voters</h1></div>

<form class="panel" method="post" autocomplete="off">
  <h2><?= $editing ? 'Edit voter' : 'Add a voter' ?></h2>
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
      <label for="voters_id">Voter ID</label>
      <input type="text" id="voters_id" name="voters_id" maxlength="30"
             <?= $editing ? 'required' : 'placeholder="Leave blank to generate"' ?>
             value="<?= e($editing['voters_id'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="password"><?= $editing ? 'New password' : 'Password' ?></label>
      <input type="text" id="password" name="password" minlength="6"
             placeholder="<?= $editing ? 'Leave blank to keep current' : 'Leave blank to generate' ?>">
    </div>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit"><?= $editing ? 'Save voter' : 'Add voter' ?></button>
    <?php if ($editing): ?><a class="btn btn-ghost" href="voters.php">Cancel</a><?php endif; ?>
  </div>
</form>

<div class="table-wrap">
<table>
  <thead><tr><th>Name</th><th>Voter ID</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php if (!$voters): ?>
    <tr><td colspan="4" class="empty">No voters yet. Add the first one above.</td></tr>
  <?php endif; ?>
  <?php foreach ($voters as $v): ?>
    <tr>
      <td><?= e($v['firstname'] . ' ' . $v['lastname']) ?></td>
      <td><?= e($v['voters_id']) ?></td>
      <td><?= $v['has_voted'] ? '<span class="badge badge-yes">Voted</span>' : '<span class="badge badge-no">Not voted</span>' ?></td>
      <td class="actions">
        <a class="btn btn-ghost btn-small" href="voters.php?edit=<?= (int) $v['id'] ?>">Edit</a>
        <form method="post" data-confirm="Delete <?= e($v['firstname'] . ' ' . $v['lastname']) ?>? Any votes they cast will be deleted too.">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
          <button class="btn btn-danger btn-small" type="submit">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php admin_footer();
