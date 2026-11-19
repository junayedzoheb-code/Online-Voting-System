<?php
// Manage admin accounts. Only a signed-in admin can create another admin.
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
                flash('error', 'Usernames are 3–50 characters: letters, numbers, dots, dashes or underscores.');
            } elseif (strlen($password) < 8) {
                flash('error', 'Admin passwords need at least 8 characters.');
            } else {
                $pdo->prepare('INSERT INTO admin (username, password) VALUES (?, ?)')
                    ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
                flash('success', "Admin \"$username\" added. Give them their password in person.");
            }

        } elseif ($action === 'password') {
            $stmt = $pdo->prepare('SELECT password FROM admin WHERE id = ?');
            $stmt->execute([$admin['id']]);
            $current_hash = $stmt->fetchColumn();
            $new = $_POST['new_password'] ?? '';

            if (!password_verify($_POST['current_password'] ?? '', $current_hash)) {
                flash('error', 'Your current password is wrong, so nothing was changed.');
            } elseif (strlen($new) < 8) {
                flash('error', 'Admin passwords need at least 8 characters.');
            } elseif ($new !== ($_POST['confirm'] ?? '')) {
                flash('error', 'The two new passwords don\'t match. Type them again.');
            } else {
                $pdo->prepare('UPDATE admin SET password = ? WHERE id = ?')
                    ->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
                flash('success', 'Your password is changed.');
            }

        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id === (int) $admin['id']) {
                flash('error', 'You can\'t delete your own account while signed in to it.');
            } else {
                $pdo->prepare('DELETE FROM admin WHERE id = ?')->execute([$id]);
                flash('success', 'Admin deleted.');
            }
        }
    } catch (PDOException $e) {
        flash('error', is_duplicate($e) ? 'That username is already taken. Choose another.' : 'That change couldn\'t be saved.');
    }
    redirect('admins.php');
}

$admins = $pdo->query('SELECT id, username FROM admin ORDER BY username')->fetchAll();

admin_header('Admins', 'admins');
?>
<div class="page-head"><h1>Admins</h1></div>

<form class="panel" method="post" autocomplete="off">
  <h2>Add an admin</h2>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="add">
  <div class="form-row">
    <div class="field">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
    </div>
    <div class="form-actions">
      <button class="btn" type="submit">Add admin</button>
    </div>
  </div>
  <p class="hint">Admins can see results and change everything in the election, so only add people you trust.</p>
</form>

<div class="table-wrap spaced">
<table>
  <thead><tr><th>Username</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($admins as $a): ?>
    <tr>
      <td><?= e($a['username']) ?><?= $a['id'] == $admin['id'] ? ' <span class="badge badge-no">You</span>' : '' ?></td>
      <td class="actions">
        <?php if ($a['id'] != $admin['id']): ?>
        <form method="post" data-confirm="Delete admin <?= e($a['username']) ?>? They won't be able to sign in any more.">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
          <button class="btn btn-danger btn-small" type="submit">Delete</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<form class="panel" method="post" autocomplete="off">
  <h2>Change your password</h2>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="password">
  <div class="form-row">
    <div class="field">
      <label for="current_password">Current password</label>
      <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
    </div>
    <div class="field">
      <label for="new_password">New password</label>
      <input type="password" id="new_password" name="new_password" minlength="8" required autocomplete="new-password">
    </div>
    <div class="field">
      <label for="confirm">Confirm new password</label>
      <input type="password" id="confirm" name="confirm" minlength="8" required autocomplete="new-password">
    </div>
    <div class="form-actions">
      <button class="btn" type="submit">Change password</button>
    </div>
  </div>
</form>
<?php admin_footer();
