<?php
require 'includes/config.php';
if (me()) redirect('dashboard.php');
$link = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = one("SELECT * FROM users WHERE email=? AND status='active'", [strtolower(trim($_POST['email'] ?? ''))]);
    if ($r) {
        $tok = bin2hex(random_bytes(32));
        q("DELETE FROM password_resets WHERE user_id=?", [$r['id']]);
        q("INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(), INTERVAL 30 MINUTE))", [$r['id'], hash('sha256', $tok)]);
        $url = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\').'/reset.php?token='.$tok;
        if (RESET_MODE === 'mail') @mail($r['email'], 'Reset your Telesom LearnHub password', "Hello {$r['name']},\n\nOpen this link to choose a new password (valid for 30 minutes):\n$url\n\nIf you did not ask for this, ignore this email.", 'From: no-reply@telesom.com');
        else $link = $url;
    }
    if (!$link) flash('If that email is registered, a reset link has been sent.');
}
$layout = 'auth'; $title = 'Forgot password'; include 'includes/header.php';
$side = ['tag' => '🔑 Account recovery', 'h' => 'Forgot your password? <span>No problem.</span>', 'p' => 'Enter your email and we will help you choose a new password in a minute.', 'pts' => ['Reset links expire after 30 minutes', 'Your old password stops working once you reset it', 'Need help? Contact your administrator']];
?>
<div class="split"><?php include 'includes/auth_side.php'; ?>
  <main class="split-r"><div class="form-wrap">
    <a class="m-brand brand" href="index.php"><?= logo() ?><span>Telesom <b>LearnHub</b></span></a>
    <a class="back" href="login.php">← Back to sign in</a>
    <h2>Reset your password 🔑</h2><p class="lead">Enter the email you registered with.</p>
    <?= flash_html() ?>
    <?php if ($link): ?><div class="flash" style="display:block"><b>Demo mode:</b> email sending is off, so here is your reset link (valid 30 minutes):<br><a href="<?= e($link) ?>" style="word-break:break-all"><?= e($link) ?></a></div><?php endif; ?>
    <form method="post"><?= csrf_field() ?><label>Email address</label><div class="field"><span>✉️</span><input type="email" name="email" placeholder="you@example.com" required autofocus></div>
      <button class="btn btn-block btn-lg" style="margin-top:22px">Send reset link →</button></form>
    <p class="switch">Remembered it? <a href="login.php"><b>Sign in</b></a></p>
  </div></main></div>
<?php include 'includes/footer.php'; ?>
