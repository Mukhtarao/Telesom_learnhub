<?php
require 'includes/config.php';
if (me()) redirect('dashboard.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = one("SELECT * FROM users WHERE email=?", [strtolower(trim($_POST['email'] ?? ''))]);
    if ($r && password_verify($_POST['password'] ?? '', $r['password'])) {
        if ($r['status'] !== 'active') flash('This account is suspended. Contact the administrator.', 'error');
        else { session_regenerate_id(true); $_SESSION['uid'] = $r['id']; flash('Welcome back, '.$r['name'].'!'); redirect('dashboard.php'); }
    } else flash('Invalid email or password.', 'error');
}
$layout = 'auth'; $title = 'Sign in'; include 'includes/header.php';
$side = ['tag' => '👋 Welcome back', 'h' => 'Your next lesson is <span>waiting for you</span>', 'p' => 'Sign in to pick up where you stopped, track your progress and earn certificates with Telesom LearnHub.', 'pts' => ['Resume courses exactly where you left off', 'Submit assignments and see your grades', 'Message instructors and join discussions']];
?>
<div class="split">
  <?php include 'includes/auth_side.php'; ?>
  <main class="split-r"><div class="form-wrap">
    <a class="m-brand brand" href="index.php"><?= logo() ?><span>Telesom <b>LearnHub</b></span></a>
    <a class="back" href="index.php">← Back to home</a>
    <h2>Welcome back 👋</h2><p class="lead">Sign in to continue your learning journey.</p>
    <?= flash_html() ?>
    <form method="post"><?= csrf_field() ?>
      <label>Email address</label>
      <div class="field"><span>✉️</span><input type="email" id="email" name="email" placeholder="you@example.com" required autofocus></div>
      <label style="display:flex;justify-content:space-between">Password <a href="forgot.php" style="font-weight:600">Forgot password?</a></label>
      <div class="field"><span>🔒</span><input type="password" id="password" name="password" placeholder="Enter your password" required><button type="button" class="eye" title="Show / hide">👁</button></div>
      <button class="btn btn-block btn-lg" style="margin-top:22px">Sign in →</button>
    </form>
    <div class="divider"><span>Quick demo access</span></div>
    <div class="demo-chips">
      <button type="button" data-demo="admin@telesom.com">🛡 Admin</button><button type="button" data-demo="instructor@telesom.com">👨‍🏫 Instructor</button>
      <button type="button" data-demo="learner@telesom.com">🎓 Learner</button><button type="button" data-demo="org@telesom.com">🏢 Organization</button>
    </div>
    <p class="hint">Tap a role to fill the demo login (password: <b>password</b>), then press Sign in.</p>
    <p class="switch">New to Telesom LearnHub? <a href="register.php"><b>Create a free account</b></a></p>
  </div></main>
</div>
<?php include 'includes/footer.php'; ?>
