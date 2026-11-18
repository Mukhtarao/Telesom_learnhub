<?php
require 'includes/config.php';
if (me()) redirect('dashboard.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $n = trim($_POST['name'] ?? ''); $em = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? '';
    $role = in_array($_POST['role'] ?? '', ['learner', 'instructor', 'organization']) ? $_POST['role'] : 'learner';
    if (!$n || !filter_var($em, FILTER_VALIDATE_EMAIL)) flash('Enter your name and a valid email.', 'error');
    elseif (strlen($pw) < 6) flash('Password must be at least 6 characters.', 'error');
    elseif ($pw !== ($_POST['password2'] ?? '')) flash('Passwords do not match.', 'error');
    elseif (one("SELECT id FROM users WHERE email=?", [$em])) flash('That email is already registered.', 'error');
    elseif (empty($_POST['terms'])) flash('Please accept the Terms of use and the Privacy policy to continue.', 'error');
    elseif (rate_count('register', 60) >= 10) flash('Too many sign-ups from this device. Please try again later.', 'error');
    else {
        q("INSERT INTO users(name,email,password,role,country,phone) VALUES(?,?,?,?,?,?)", [$n, $em, password_hash($pw, PASSWORD_DEFAULT), $role, trim($_POST['country'] ?? ''), trim($_POST['phone'] ?? '')]);
        $_SESSION['uid'] = q("SELECT LAST_INSERT_ID() i")->fetch()['i'];
        q("UPDATE users SET terms_at=NOW() WHERE id=?", [$_SESSION['uid']]); rate_hit('register');
        notify($_SESSION['uid'], 'Welcome to Telesom LearnHub!', 'dashboard.php');
        if ($role === 'organization') { q("UPDATE users SET org_status='pending' WHERE id=?", [$_SESSION['uid']]); foreach (all("SELECT id FROM users WHERE role='admin'") as $ad) notify($ad['id'], '🏢 New organization awaiting approval: '.$n, 'admin.php?tab=orgs'); }
        flash($role === 'organization' ? 'Organization registered. An administrator will review and approve it — you will be notified.' : 'Account created. Welcome!'); redirect('dashboard.php');
    }
}
$layout = 'auth'; $title = 'Create account'; include 'includes/header.php';
$side = ['tag' => t('🚀 Join Telesom LearnHub'), 'h' => t('Learn new skills. <span>Earn real certificates.</span>'), 'p' => t('Create your free account in a minute and start learning with courses built for Telesom staff, partners and the community.'), 'pts' => [t('Free and paid courses for every level'), t('Track your progress on any device'), t('Share certificates employers can verify')]];
$sel = $_POST['role'] ?? (isset($_GET['org']) ? 'organization' : 'learner');
?>
<div class="split">
  <?php include 'includes/auth_side.php'; ?>
  <main class="split-r"><div class="form-wrap wide">
    <a class="m-brand brand" href="index.php"><?= logo() ?><span>Telesom <b>LearnHub</b></span></a>
    <a class="back" href="index.php"><?= t('← Back to home') ?></a>
    <h2><?= t('Create your account ✨') ?></h2><p class="lead"><?= t('It takes less than a minute. No credit card needed.') ?></p>
    <?= flash_html() ?>
    <form method="post"><?= csrf_field() ?>
      <label><?= t('I am joining as') ?></label>
      <div class="roles">
        <?php foreach (['learner' => ['🎓', 'Learner', 'Learn & get certified'], 'instructor' => ['👨‍🏫', 'Instructor', 'Create & teach'], 'organization' => ['🏢', 'Organization', 'Train your people']] as $k => $r): ?>
        <label class="role"><input type="radio" name="role" value="<?= $k ?>" <?= $sel === $k ? 'checked' : '' ?>><span class="box"><i><?= $r[0] ?></i><b><?= e(t($r[1])) ?></b><small><?= e(t($r[2])) ?></small></span></label>
        <?php endforeach; ?>
      </div>
      <p class="hint" style="margin:-4px 0 6px">🏢 Organizations are reviewed and approved by an administrator, then choose a monthly or yearly plan.</p>
      <label><?= t('Full name / Organization name') ?></label>
      <div class="field"><span>👤</span><input name="name" placeholder="e.g. Abdi Mohamed" required value="<?= e($_POST['name'] ?? '') ?>"></div>
      <label><?= t('Email address') ?></label>
      <div class="field"><span>✉️</span><input type="email" name="email" placeholder="you@example.com" required value="<?= e($_POST['email'] ?? '') ?>"></div>
      <div class="fr"><div><label><?= t('Country') ?></label><div class="field"><span>🌍</span><input name="country" value="<?= e($_POST['country'] ?? 'Somaliland') ?>"></div></div>
      <div><label><?= t('Phone') ?></label><div class="field"><span>📱</span><input name="phone" placeholder="Optional" value="<?= e($_POST['phone'] ?? '') ?>"></div></div></div>
      <div class="fr"><div><label><?= t('Password') ?></label><div class="field"><span>🔒</span><input type="password" id="pw" name="password" placeholder="Min. 6 characters" required minlength="6"><button type="button" class="eye" title="Show / hide">👁</button></div></div>
      <div><label><?= t('Confirm password') ?></label><div class="field"><span>🔒</span><input type="password" name="password2" placeholder="Repeat password" required></div></div></div>
      <div class="meter"><i id="pwbar"></i></div><small class="hint" id="pwtxt"><?= t('Use letters, numbers and symbols for a strong password.') ?></small>
      <label class="opt" style="margin-top:16px"><input type="checkbox" name="terms" value="1" required> <span><?= t('I agree to the') ?> <a href="terms.php" target="_blank"><?= t('Terms of use') ?></a> <?= t('and') ?> <a href="privacy.php" target="_blank"><?= t('Privacy policy') ?></a></span></label>
      <button class="btn btn-block btn-lg" style="margin-top:20px"><?= t('Create my account →') ?></button>
    </form>
    <p class="switch"><?= t('Already have an account?') ?> <a href="login.php"><b><?= t('Sign in') ?></b></a></p>
  </div></main>
</div>
<?php include 'includes/footer.php'; ?>
