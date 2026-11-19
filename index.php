<?php
// Voter login page
require_once __DIR__ . '/includes/functions.php';
start_session('VOTERSESSID');

if (!empty($_SESSION['voter_id'])) {
    redirect('ballot.php');
}
$page_title = 'Voter sign in — Online Voting System';
include __DIR__ . '/includes/head.php';
?>
<body>
<main class="login-wrap">
  <form class="slip" action="login.php" method="post" autocomplete="off">
    <h1>Sign in to vote</h1>
    <p class="muted">Use the voter ID and password your election admin gave you.</p>

    <?= show_flash() ?>
    <?= csrf_field() ?>

    <div class="field">
      <label for="voters_id">Voter ID</label>
      <input type="text" id="voters_id" name="voters_id" required autofocus
             value="<?= e($_SESSION['last_voter_id'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button class="btn btn-block" type="submit">Sign in</button>

    <?php if ($ALLOW_VOTER_SIGNUP): ?>
      <p class="switch muted">New voter? <a href="register.php">Create an account</a></p>
    <?php endif; ?>
    <p class="switch muted">Running the election? <a href="admin/">Admin sign in</a></p>
  </form>
</main>
</body>
</html>
