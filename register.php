<?php
// Voter self sign-up. Turn it off with $ALLOW_VOTER_SIGNUP in includes/config.php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
start_session('VOTERSESSID');

if (!$ALLOW_VOTER_SIGNUP) {
    flash('info', 'Voter accounts are created by the election admin. Ask them for your voter ID and password.');
    redirect('index.php');
}
if (!empty($_SESSION['voter_id'])) {
    redirect('ballot.php');
}

$old = ['firstname' => '', 'lastname' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm'] ?? '';
    $old = ['firstname' => $firstname, 'lastname' => $lastname];

    if ($firstname === '' || $lastname === '') {
        flash('error', 'Enter your first and last name.');
    } elseif (strlen($firstname) > 50 || strlen($lastname) > 50) {
        flash('error', 'Names can be up to 50 characters.');
    } elseif (strlen($password) < 6) {
        flash('error', 'Choose a password with at least 6 characters.');
    } elseif ($password !== $confirm) {
        flash('error', 'The two passwords don\'t match. Type them again.');
    } else {
        try {
            [$id, $vid] = create_voter_with_generated_id(
                $pdo, $firstname, $lastname, password_hash($password, PASSWORD_DEFAULT)
            );
            session_regenerate_id(true);
            $_SESSION['voter_id'] = $id;
            flash('success', "Account created. Your voter ID is $vid — write it down, you'll need it to sign in next time.");
            redirect('ballot.php');
        } catch (PDOException $e) {
            flash('error', 'Your account couldn\'t be created. Please try again.');
        }
    }
}

$page_title = 'Create a voter account — Online Voting System';
include __DIR__ . '/includes/head.php';
?>
<body>
<main class="login-wrap">
  <form class="slip" method="post" autocomplete="off">
    <h1>Create a voter account</h1>
    <p class="muted">You'll get a voter ID when you finish. Use it with your password to sign in.</p>

    <?= show_flash() ?>
    <?= csrf_field() ?>

    <div class="field">
      <label for="firstname">First name</label>
      <input type="text" id="firstname" name="firstname" maxlength="50" required autofocus value="<?= e($old['firstname']) ?>">
    </div>
    <div class="field">
      <label for="lastname">Last name</label>
      <input type="text" id="lastname" name="lastname" maxlength="50" required value="<?= e($old['lastname']) ?>">
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" minlength="6" required>
      <p class="hint">At least 6 characters.</p>
    </div>
    <div class="field">
      <label for="confirm">Confirm password</label>
      <input type="password" id="confirm" name="confirm" minlength="6" required>
    </div>
    <button class="btn btn-block" type="submit">Create account</button>

    <p class="switch muted">Already have a voter ID? <a href="index.php">Sign in</a></p>
  </form>
</main>
</body>
</html>
