<?php
require 'includes/config.php';
$u = require_login('organization'); $oid = $u['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    if ($a === 'add') {
        $em = strtolower(trim($_POST['email'])); $role = in_array($_POST['role'], ['learner', 'instructor']) ? $_POST['role'] : 'learner';
        if (!trim($_POST['name']) || !filter_var($em, FILTER_VALIDATE_EMAIL) || strlen($_POST['password']) < 6) flash('Provide name, valid email and a 6+ character password.', 'error');
        elseif ($ex = one("SELECT * FROM users WHERE email=?", [$em])) {
            if ($ex['role'] !== 'learner' && $ex['role'] !== 'instructor' || $ex['org_id']) flash('That email belongs to an account that cannot be added.', 'error');
            elseif ($ex['role'] === 'learner' && !org_can_add_learner($oid)) flash('Learner limit reached for your plan — upgrade to add more members.', 'error');
            else { q("UPDATE users SET org_id=? WHERE id=?", [$oid, $ex['id']]); flash('Existing user added to your organization.'); }
        } elseif ($role === 'learner' && !org_can_add_learner($oid)) { flash('Learner limit reached for your plan — upgrade to add more members.', 'error');
        } else { q("INSERT INTO users(name,email,password,role,org_id) VALUES(?,?,?,?,?)", [trim($_POST['name']), $em, password_hash($_POST['password'], PASSWORD_DEFAULT), $role, $oid]); flash('Member created.'); }
    } elseif ($a === 'remove') { q("UPDATE users SET org_id=NULL WHERE id=? AND org_id=?", [(int)$_POST['rid'], $oid]); flash('Member removed from organization.'); }
    elseif ($a === 'assign') {
        $c = one("SELECT * FROM courses WHERE id=? AND (org_id=? OR (org_id IS NULL AND status='approved'))", [(int)$_POST['course_id'], $oid]); $n = 0;
        foreach ((array)($_POST['users'] ?? []) as $uid) {
            $m = one("SELECT id FROM users WHERE id=? AND org_id=? AND role='learner'", [(int)$uid, $oid]);
            if ($c && $m && q("INSERT IGNORE INTO enrollments(user_id,course_id,status) VALUES(?,?, 'active')", [$m['id'], $c['id']])->rowCount()) { $n++; notify($m['id'], 'Your organization assigned you a course: '.$c['title'], 'learn.php?course='.$c['id']); }
        }
        flash($n ? "Course assigned to $n learner(s)." : 'No new assignments were made.', $n ? 'success' : 'error');
    }
    redirect('organization.php');
}
$mem = all("SELECT u.*, (SELECT COUNT(*) FROM enrollments WHERE user_id=u.id AND status='active') n, (SELECT COUNT(*) FROM enrollments WHERE user_id=u.id AND completed_at IS NOT NULL) d FROM users u WHERE u.org_id=? ORDER BY u.name", [$oid]);
$cs = all("SELECT id,title,org_id FROM courses WHERE status='approved' AND (org_id=? OR org_id IS NULL) ORDER BY org_id DESC, title", [$oid]);
$title = 'Members & Course Assignment'; include 'includes/header.php';
?>
<?php $os = org_sub($oid); $ou = org_usage($oid); ?><div class="card" style="display:flex;gap:26px;flex-wrap:wrap;align-items:center;justify-content:space-between"><div><b>💎 <?= e($os['plan_name'] ?? 'No active plan') ?></b><br><small class="muted"><?= $os ? 'Valid until '.fdate($os['end_at']) : 'Subscribe to add members' ?></small></div><div><b><?= $ou['learners'] ?> / <?= ($os['max_learners'] ?? 0) ?: '∞' ?></b><br><small class="muted">learners</small></div><div><b><?= $ou['courses'] ?> / <?= ($os['max_courses'] ?? 0) ?: '∞' ?></b><br><small class="muted">private courses</small></div><a class="btn btn-sm btn-outline" href="subscription.php">Manage subscription</a></div>
<div class="cols"><div class="card"><h3>Organization members (<?= count($mem) ?>)</h3><div class="tw"><table><tr><th>Member</th><th>Role</th><th>Courses</th><th>Completed</th><th></th></tr>
<?php foreach ($mem as $m): ?><tr><td><b><?= e($m['name']) ?></b><br><small class="muted"><?= e($m['email']) ?></small></td><td><span class="badge grey"><?= $m['role'] ?></span></td><td><?= $m['n'] ?></td><td><?= $m['d'] ?></td>
<td class="right"><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="rid" value="<?= $m['id'] ?>"><button class="btn btn-sm btn-outline" data-confirm="Remove from organization?">Remove</button></form></td></tr><?php endforeach; ?></table></div><?php if (!$mem) echo '<div class="empty">No members yet — add your first one.</div>'; ?></div>
<div>
 <form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add"><h3>Add instructor / learner</h3><label style="margin-top:0">Name</label><input name="name" required><label>Email</label><input type="email" name="email" required><label>Temporary password</label><input type="password" name="password" required minlength="6"><label>Role</label><select name="role"><option value="learner">Learner</option><option value="instructor">Instructor</option></select><div class="form-actions"><button class="btn">Add member</button></div></form>
 <form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="assign"><h3>Assign a course</h3><label style="margin-top:0">Course</label><select name="course_id" required><?php foreach ($cs as $c) echo '<option value="'.$c['id'].'">'.($c['org_id'] ? '🔒 ' : '').e($c['title']).'</option>'; ?></select>
 <label>Learners</label><?php foreach ($mem as $m) if ($m['role'] === 'learner') echo '<label class="opt"><input type="checkbox" name="users[]" value="'.$m['id'].'"> '.e($m['name']).'</label>'; ?><div class="form-actions"><button class="btn">Assign course</button></div></form>
</div></div>
<?php include 'includes/footer.php'; ?>
