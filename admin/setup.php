<?php
// First-time setup: create the first admin account.
// Only works while the admin table is empty, then locks itself.
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
start_session('ADMINSESSID');

$admin_count = (int) $pdo->query('SELECT COUNT(*) FROM admin')->fetchColumn();
if ($admin_count > 0) {
    redirect('index.php');
}

$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        flash('error', 'Usernames are 3–50 characters: letters, numbers, dots, dashes or underscores.');
    } elseif (strlen($password) < 8) {
        flash('error', 'Admin passwords need at least 8 characters.');
    } elseif ($password !== ($_POST['confirm'] ?? '')) {
        flash('error', 'The two passwords don\'t match. Type them again.');
    } else {
        $pdo->prepare('INSERT INTO admin (username, password) VALUES (?, ?)')
            ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $pdo->lastInsertId();
        flash('success', "Admin account \"$username\" created. Add other admins from the Admins page.");
        redirect('home.php');
    }
}

$page_title   = 'Set up the first admin — Online Voting System';
$asset_prefix = '../';
include __DIR__ . '/../includes/head.php';
?>
<body>
<main class="login-wrap">
  <form class="slip admin" method="post" autocomplete="off">
    <h1>Create the first admin</h1>
    <p class="muted">There are no admin accounts yet. The account you create here can add more admins later. This page closes once it's done.</p>

    <?= show_flash() ?>
    <?= csrf_field() ?>

    <div class="field">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required autofocus value="<?= e($username) ?>">
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" minlength="8" required>
      <p class="hint">At least 8 characters.</p>
    </div>
    <div class="field">
      <label for="confirm">Confirm password</label>
      <input type="password" id="confirm" name="confirm" minlength="8" required>
    </div>
    <button class="btn btn-block" type="submit">Create admin account</button>
  </form>
</main>
</body>
</html>
