<?php
// First-run setup: creates the very first administrator. It works ONLY while the database has no administrator,
// and switches itself off for good as soon as one exists.
require 'includes/config.php';
$has = (int)one("SELECT COUNT(*) c FROM users WHERE role='admin'")['c'];
if ($has) { flash('Setup is already complete. Please sign in.'); redirect('login.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $n = trim($_POST['name'] ?? ''); $em = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? '';
    if (!$n || !filter_var($em, FILTER_VALIDATE_EMAIL)) flash('Enter your name and a valid email.', 'error');
    elseif (strlen($pw) < 10) flash('Use a password of at least 10 characters.', 'error');
    elseif ($pw !== ($_POST['password2'] ?? '')) flash('Passwords do not match.', 'error');
    else {
        q("INSERT INTO users(name,email,password,role,terms_at) VALUES(?,?,?, 'admin', NOW())", [$n, $em, password_hash($pw, PASSWORD_DEFAULT)]);
        session_regenerate_id(true); $_SESSION['uid'] = (int)q("SELECT LAST_INSERT_ID() i")->fetch()['i'];
        flash('Administrator created. Next: open the profile menu → System health to review the go-live checks.'); redirect('dashboard.php');
    }
}
$layout = 'auth'; $title = 'Set up LearnHub'; include 'includes/header.php';
$side = ['tag' => '⚙️ First-time setup', 'h' => 'Welcome to <span>Telesom LearnHub</span>', 'p' => 'Create the administrator account that will manage the platform. This page disappears once the account exists.', 'pts' => ['Use a strong, unique password', 'You can add instructors and organizations afterwards', 'Then review System health before going live']];
?>
<div class="split"><?php include 'includes/auth_side.php'; ?>
  <main class="split-r"><div class="form-wrap">
    <a class="m-brand brand" href="index.php"><?= logo() ?><span>Telesom <b>LearnHub</b></span></a>
    <h2>Create the administrator</h2><p class="lead">This is a one-time step.</p>
    <?= flash_html() ?>
    <form method="post" autocomplete="off"><?= csrf_field() ?>
      <label>Full name</label><div class="field"><span>👤</span><input name="name" required value="<?= e($_POST['name'] ?? '') ?>"></div>
      <label>Email address</label><div class="field"><span>✉️</span><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
      <label>Password <small class="muted">(10+ characters)</small></label><div class="field"><span>🔒</span><input type="password" id="pw" name="password" required minlength="10"><button type="button" class="eye" title="Show / hide">👁</button></div>
      <div class="meter"><i id="pwbar"></i></div><small class="hint" id="pwtxt"></small>
      <label>Confirm password</label><div class="field"><span>🔒</span><input type="password" name="password2" required minlength="10"></div>
      <button class="btn btn-block btn-lg" style="margin-top:22px">Create administrator →</button>
    </form>
  </div></main></div>
<?php include 'includes/footer.php'; ?>
