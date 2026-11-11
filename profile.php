<?php
require 'includes/config.php';
$u = require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'password') {
        if (!password_verify($_POST['old'] ?? '', $u['password'])) flash('Current password is incorrect.', 'error');
        elseif (strlen($_POST['new'] ?? '') < 6 || $_POST['new'] !== $_POST['new2']) flash('New passwords must match and be at least 6 characters.', 'error');
        else { q("UPDATE users SET password=? WHERE id=?", [password_hash($_POST['new'], PASSWORD_DEFAULT), $u['id']]); flash('Password changed.'); }
    } else {
        $em = strtolower(trim($_POST['email'] ?? ''));
        if (!trim($_POST['name'] ?? '') || !filter_var($em, FILTER_VALIDATE_EMAIL)) flash('Name and valid email are required.', 'error');
        elseif (one("SELECT id FROM users WHERE email=? AND id<>?", [$em, $u['id']])) flash('Email already in use.', 'error');
        else { q("UPDATE users SET name=?,email=?,phone=?,country=?,bio=?,education=?,skills=?,signatory=?,signatory_title=? WHERE id=?", [trim($_POST['name']), $em, trim($_POST['phone']), trim($_POST['country']), trim($_POST['bio']), trim($_POST['education']), trim($_POST['skills']), isset($_POST['signatory']) ? trim($_POST['signatory']) : $u['signatory'], isset($_POST['signatory_title']) ? trim($_POST['signatory_title']) : $u['signatory_title'], $u['id']]); flash('Profile updated.'); }
    }
    redirect('profile.php');
}
$certs = all("SELECT ce.*, c.title FROM certificates ce JOIN courses c ON c.id=ce.course_id WHERE ce.user_id=?", [$u['id']]);
$done = all("SELECT c.title FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=? AND e.completed_at IS NOT NULL", [$u['id']]);
$title = 'My Profile'; include 'includes/header.php';
?>
<div class="cols"><div>
 <form class="card" method="post"><?= csrf_field() ?><h3>Personal information</h3>
  <div class="fr"><div><label style="margin-top:0">Full name</label><input name="name" value="<?= e($u['name']) ?>" required></div><div><label style="margin-top:0">Email</label><input type="email" name="email" value="<?= e($u['email']) ?>" required></div>
  <div><label>Phone</label><input name="phone" value="<?= e($u['phone']) ?>"></div><div><label>Country</label><input name="country" value="<?= e($u['country']) ?>"></div>
  <div><label>Education</label><input name="education" value="<?= e($u['education']) ?>"></div><div><label>Skills</label><input name="skills" value="<?= e($u['skills']) ?>" placeholder="e.g. Networking, Excel"></div></div>
  <label>Biography</label><textarea name="bio"><?= e($u['bio']) ?></textarea>
  <?php if ($u['role'] === 'organization'): ?><div class="fr"><div><label>Certificate signatory (name)</label><input name="signatory" value="<?= e($u['signatory']) ?>" placeholder="e.g. Training Manager's name"></div><div><label>Signatory title</label><input name="signatory_title" value="<?= e($u['signatory_title']) ?>" placeholder="e.g. Head of Training"></div></div><small class="hint">Appears as the signature on certificates for your organization's private courses.</small><?php endif; ?><div class="form-actions"><button class="btn">Save changes</button></div></form>
 <form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password"><h3>Change password</h3>
  <div class="fr"><div><label style="margin-top:0">Current password</label><input type="password" name="old" required></div><div></div><div><label>New password</label><input type="password" name="new" required></div><div><label>Confirm new password</label><input type="password" name="new2" required></div></div><div class="form-actions"><button class="btn">Update password</button></div></form>
</div><div>
 <div class="card" style="text-align:center"><span class="av" style="width:80px;height:80px;font-size:28px;margin:0 auto 10px"><?= e(initials($u['name'])) ?></span><h3 style="justify-content:center"><?= e($u['name']) ?></h3><span class="badge"><?= e(ucfirst($u['role'])) ?></span><p style="margin-top:10px">Member since <?= fdate($u['created_at']) ?></p></div>
 <?php if ($u['role'] === 'learner'): ?><div class="card"><h3>Courses completed</h3><?php foreach ($done as $d) echo '<p>✅ '.e($d['title']).'</p>'; if (!$done) echo '<p class="muted">None yet.</p>'; ?></div>
 <div class="card"><h3>Certificates obtained</h3><?php foreach ($certs as $c) echo '<p>🎓 <a href="verify.php?code='.e($c['code']).'">'.e($c['title']).'</a></p>'; if (!$certs) echo '<p class="muted">None yet.</p>'; ?></div><?php endif; ?>
</div></div>
<?php include 'includes/footer.php'; ?>
