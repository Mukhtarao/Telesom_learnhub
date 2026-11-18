<?php
require 'includes/config.php';
if (me()) redirect('dashboard.php');
$tok = $_POST['token'] ?? $_GET['token'] ?? '';
$row = $tok ? one("SELECT * FROM password_resets WHERE token_hash=? AND expires_at>NOW()", [hash('sha256', $tok)]) : null;
if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (strlen($_POST['password'] ?? '') < 6) flash('Password must be at least 6 characters.', 'error');
    elseif ($_POST['password'] !== ($_POST['password2'] ?? '')) flash('Passwords do not match.', 'error');
    else {
        q("UPDATE users SET password=? WHERE id=?", [password_hash($_POST['password'], PASSWORD_DEFAULT), $row['user_id']]);
        q("DELETE FROM password_resets WHERE user_id=?", [$row['user_id']]);
        flash('Password updated. You can sign in now.'); redirect('login.php');
    }
}
$layout = 'auth'; $title = 'New password'; include 'includes/header.php';
$side = ['tag' => t('🔑 Account recovery'), 'h' => t('Forgot your password? <span>No problem.</span>'), 'p' => t('Enter your email and we will help you choose a new password in a minute.'), 'pts' => [t('Reset links expire after 30 minutes'), t('Your old password stops working once you reset it'), t('Need help? Contact your administrator')]];
?>
<div class="split"><?php include 'includes/auth_side.php'; ?>
  <main class="split-r"><div class="form-wrap">
    <a class="m-brand brand" href="index.php"><?= logo() ?><span>Telesom <b>LearnHub</b></span></a>
    <a class="back" href="login.php"><?= t('← Back to sign in') ?></a>
    <?= flash_html() ?>
    <?php if (!$row): ?><h2>Link expired ⏳</h2><p class="lead">This reset link is invalid or has expired.</p><a class="btn" href="forgot.php">Request a new link</a>
    <?php else: ?><h2>Choose a new password 🔒</h2><p class="lead">Use at least 6 characters.</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($tok) ?>">
      <label><?= t('New password') ?></label><div class="field"><span>🔒</span><input type="password" name="password" required minlength="6" autofocus><button type="button" class="eye">👁</button></div>
      <label><?= t('Confirm new password') ?></label><div class="field"><span>🔒</span><input type="password" name="password2" required></div>
      <button class="btn btn-block btn-lg" style="margin-top:22px">Update password →</button></form><?php endif; ?>
  </div></main></div>
<?php include 'includes/footer.php'; ?>
