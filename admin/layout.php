<?php
// Page frame for admin pages: admin_header('Title', 'home') ... admin_footer();
function admin_header(string $title, string $active): void
{
    global $admin;
    $page_title   = "$title — Admin";
    $asset_prefix = '../';
    include __DIR__ . '/../includes/head.php';
    $links = [
        'home'       => ['home.php',       'Dashboard'],
        'positions'  => ['positions.php',  'Positions'],
        'candidates' => ['candidates.php', 'Candidates'],
        'voters'     => ['voters.php',     'Voters'],
        'admins'     => ['admins.php',     'Admins'],
    ];
    ?>
<body>
<div class="admin-shell">
  <aside class="sidebar">
    <div class="brand">Voting admin<small>Signed in as <?= e($admin['username']) ?></small></div>
    <nav>
      <?php foreach ($links as $key => [$href, $label]): ?>
        <a href="<?= $href ?>"<?= $key === $active ? ' class="active" aria-current="page"' : '' ?>><?= $label ?></a>
      <?php endforeach; ?>
      <a class="logout" href="logout.php">Sign out</a>
    </nav>
  </aside>
  <main class="main">
    <?= show_flash() ?>
<?php
}

function admin_footer(): void
{
    ?>
  </main>
</div>
<script>
// Ask before any delete
document.querySelectorAll('form[data-confirm]').forEach(function (f) {
  f.addEventListener('submit', function (ev) {
    if (!confirm(f.dataset.confirm)) ev.preventDefault();
  });
});
</script>
</body>
</html>
<?php
}
