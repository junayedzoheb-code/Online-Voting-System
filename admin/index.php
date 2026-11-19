<?php
// Admin login page (separate from the voter login)
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
start_session('ADMINSESSID');

// Fresh install with no admins yet? Go to first-time setup.
if ((int) $pdo->query('SELECT COUNT(*) FROM admin')->fetchColumn() === 0) {
    redirect('setup.php');
}

if (!empty($_SESSION['admin_id'])) {
    redirect('home.php');
}
$page_title   = 'Admin sign in — Online Voting System';
$asset_prefix = '../';
include __DIR__ . '/../includes/head.php';
?>
<body>
<main class="login-wrap">
  <form class="slip admin" action="login.php" method="post" autocomplete="off">
    <h1>Admin sign in</h1>
    <p class="muted">Manage positions, candidates, voters and results.</p>

    <?= show_flash() ?>
    <?= csrf_field() ?>

    <div class="field">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required autofocus>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button class="btn btn-block" type="submit">Sign in</button>

    <p class="switch muted"><a href="../">Go to voter sign in</a></p>
  </form>
</main>
</body>
</html>
