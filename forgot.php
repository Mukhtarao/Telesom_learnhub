<?php
require 'includes/config.php';
if (me()) redirect('dashboard.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && rate_count('reset', 60) >= 5) flash('Too many reset requests from this device. Please try again in an hour.', 'error');
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rate_hit('reset');
    $r = one("SELECT * FROM users WHERE email=? AND status='active'", [strtolower(trim($_POST['email'] ?? ''))]);
    if ($r) {
        $tok = bin2hex(random_bytes(32));
        q("DELETE FROM password_resets WHERE user_id=?", [$r['id']]);
        q("INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(), INTERVAL 30 MINUTE))", [$r['id'], hash('sha256', $tok)]);
        $url = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\').'/reset.php?token='.$tok;
        send_mail($r['email'], 'Reset your Telesom LearnHub password', mail_html($r['name'], 'Reset your password', "Use the button below to choose a new password. The link is valid for 30 minutes. If you did not ask for this, you can ignore this email.", $url), "Hello {$r['name']},\nOpen this link to choose a new password (valid 30 minutes):\n$url");
    }
    flash('If that email is registered, a reset link has been sent.');
}
$layout = 'auth'; $title = 'Forgot password'; include 'includes/header.php';
$side = ['tag' => t('🔑 Account recovery'), 'h' => t('Forgot your password? <span>No problem.</span>'), 'p' => t('Enter your email and we will help you choose a new password in a minute.'), 'pts' => [t('Reset links expire after 30 minutes'), t('Your old password stops working once you reset it'), t('Need help? Contact your administrator')]];
?>
<div class="split"><?php include 'includes/auth_side.php'; ?>
  <main class="split-r"><div class="form-wrap">
    <a class="m-brand brand" href="index.php"><?= logo() ?><span>Telesom <b>LearnHub</b></span></a>
    <a class="back" href="login.php"><?= t('← Back to sign in') ?></a>
    <h2><?= t('Reset your password 🔑') ?></h2><p class="lead"><?= t('Enter the email you registered with.') ?></p>
    <?= flash_html() ?>
    <form method="post"><?= csrf_field() ?><label><?= t('Email address') ?></label><div class="field"><span>✉️</span><input type="email" name="email" placeholder="you@example.com" required autofocus></div>
      <button class="btn btn-block btn-lg" style="margin-top:22px"><?= t('Send reset link →') ?></button></form>
    <p class="switch"><?= t('Remembered it?') ?> <a href="login.php"><b><?= t('Sign in') ?></b></a></p>
  </div></main></div>
<?php include 'includes/footer.php'; ?>
