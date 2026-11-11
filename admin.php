<?php
require 'includes/config.php';
$u = require_login('admin'); $tab = $_GET['tab'] ?? 'users';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? ''; $rid = (int)($_POST['rid'] ?? 0);
    if ($a === 'add_user') {
        $em = strtolower(trim($_POST['email']));
        if (!trim($_POST['name']) || !filter_var($em, FILTER_VALIDATE_EMAIL) || strlen($_POST['password']) < 6) flash('Provide name, valid email and a 6+ character password.', 'error');
        elseif (one("SELECT id FROM users WHERE email=?", [$em])) flash('Email already exists.', 'error');
        else { q("INSERT INTO users(name,email,password,role,org_id) VALUES(?,?,?,?,?)", [trim($_POST['name']), $em, password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['role'], (int)$_POST['org_id'] ?: null]); flash('User created.'); }
    } elseif ($a === 'role' && $rid != $u['id'] && in_array($_POST['role'], ['admin', 'instructor', 'learner', 'organization'])) { q("UPDATE users SET role=? WHERE id=?", [$_POST['role'], $rid]); flash('Role updated.'); }
    elseif ($a === 'status' && $rid != $u['id']) { q("UPDATE users SET status=IF(status='active','suspended','active') WHERE id=?", [$rid]); flash('Account status changed.'); }
    elseif ($a === 'del_user' && $rid != $u['id']) { q("DELETE FROM users WHERE id=?", [$rid]); flash('User deleted.'); }
    elseif ($a === 'course_status' && in_array($_POST['status'], ['approved', 'rejected', 'pending'])) {
        q("UPDATE courses SET status=? WHERE id=?", [$_POST['status'], $rid]); $c = one("SELECT * FROM courses WHERE id=?", [$rid]);
        notify($c['instructor_id'], 'Your course "'.$c['title'].'" was '.$_POST['status'].'.', 'course_edit.php?id='.$rid); flash('Course '.$_POST['status'].'.');
    } elseif ($a === 'del_course') { q("DELETE FROM courses WHERE id=?", [$rid]); flash('Course deleted.'); }
    elseif ($a === 'add_cat' && trim($_POST['name'])) { q("INSERT IGNORE INTO categories(name) VALUES(?)", [trim($_POST['name'])]); flash('Category added.'); }
    elseif ($a === 'del_cat') { q("DELETE FROM categories WHERE id=?", [$rid]); flash('Category deleted.'); }
    elseif ($a === 'add_ann' && trim($_POST['title'])) {
        q("INSERT INTO announcements(title,body) VALUES(?,?)", [trim($_POST['title']), trim($_POST['body'])]);
        q("INSERT INTO notifications(user_id,message,link) SELECT id, ?, 'dashboard.php' FROM users WHERE status='active'", ['📣 '.trim($_POST['title'])]); flash('Announcement published to everyone.');
    } elseif ($a === 'del_ann') { q("DELETE FROM announcements WHERE id=?", [$rid]); flash('Announcement removed.'); }
    elseif ($a === 'review') { if ($_POST['do'] === 'delete') q("DELETE FROM reviews WHERE id=?", [$rid]); else q("UPDATE reviews SET reported=0 WHERE id=?", [$rid]); flash('Done.'); }
    if ($a === 'org_status' && in_array($_POST['do'] ?? '', ['approve', 'reject'])) {
        $o = one("SELECT * FROM users WHERE id=? AND role='organization'", [$rid]);
        if ($o) { $ap = $_POST['do'] === 'approve'; q("UPDATE users SET org_status=? WHERE id=?", [$ap ? 'approved' : 'rejected', $rid]);
            notify($rid, $ap ? '✅ Your organization was approved. Choose a plan to activate your account.' : '❌ Your organization request was not approved. Please contact the administrator.', 'subscription.php');
            flash($ap ? 'Organization approved — they can now choose a plan.' : 'Organization rejected.'); }
    }
    if ($a === 'grant_plan') {
        $o = one("SELECT * FROM users WHERE id=? AND role='organization'", [$rid]); $pl = one("SELECT * FROM plans WHERE id=?", [(int)($_POST['plan_id'] ?? 0)]);
        if ($o && $pl) {
            $free = !empty($_POST['free']); $amt = $free ? 0 : ((($_POST['amount'] ?? '') !== '') ? (float)$_POST['amount'] : (float)$pl['price']);
            $ml = ($_POST['max_learners'] ?? '') !== '' ? (int)$_POST['max_learners'] : (int)$pl['max_learners']; $mc = ($_POST['max_courses'] ?? '') !== '' ? (int)$_POST['max_courses'] : (int)$pl['max_courses'];
            q("UPDATE users SET org_status='approved' WHERE id=?", [$rid]);
            q("INSERT INTO org_subscriptions(org_id,plan_id,plan_name,billing,amount,max_learners,max_courses,status,sponsored,method,reference) VALUES(?,?,?,?,?,?,?,'pending',?,'none',?)", [$rid, $pl['id'], $pl['name'], $pl['billing'], $amt, $ml, $mc, $free ? 1 : 0, $free ? 'SPONSORED' : 'ADMIN-GRANT']);
            $sid = (int)q("SELECT LAST_INSERT_ID() i")->fetch()['i']; sub_set_invoice($sid); org_activate($sid);
            flash('Plan granted — the organization is now active.');
        } else flash('Choose an organization and a plan.', 'error');
    }
    if ($a === 'sub_confirm') {
        $sb = one("SELECT * FROM org_subscriptions WHERE id=? AND status='pending'", [$rid]);
        if ($sb && ($_POST['do'] ?? '') === 'confirm') { org_activate($rid); flash('Payment confirmed — subscription activated.'); }
        elseif ($sb) { q("UPDATE org_subscriptions SET status='rejected' WHERE id=?", [$rid]); notify($sb['org_id'], '❌ Your subscription payment could not be confirmed. Please check the transaction ID and try again.', 'subscription.php'); flash('Payment rejected.'); }
    }
    if ($a === 'plan_save') {
        $f = [trim($_POST['name'] ?? ''), $_POST['billing'] === 'yearly' ? 'yearly' : 'monthly', max(0, (float)($_POST['price'] ?? 0)), max(0, (int)($_POST['max_learners'] ?? 0)), max(0, (int)($_POST['max_courses'] ?? 0)), trim($_POST['features'] ?? ''), !empty($_POST['active']) ? 1 : 0];
        if (!$f[0]) flash('Plan name is required.', 'error');
        elseif ($rid) { q("UPDATE plans SET name=?,billing=?,price=?,max_learners=?,max_courses=?,features=?,active=? WHERE id=?", array_merge($f, [$rid])); flash('Plan updated.'); }
        else { q("INSERT INTO plans(name,billing,price,max_learners,max_courses,features,active,sort_order) VALUES(?,?,?,?,?,?,?,99)", $f); flash('Plan added.'); }
    }
    redirect("admin.php?tab=$tab");
}
$title = ['users' => 'User Management', 'courses' => 'Course Management', 'categories' => 'Categories', 'announcements' => 'Announcements', 'orgs' => 'Organizations', 'subs' => 'Subscriptions', 'plans' => 'Plans & Pricing', 'reviews' => 'Reported Reviews'][$tab] ?? 'Administration';
include 'includes/header.php';
function pf($action, $rid, $label, $cls = 'btn-sm', $extra = '', $conf = ''){ return '<form method="post" class="inline">'.csrf_field().'<input type="hidden" name="action" value="'.$action.'"><input type="hidden" name="rid" value="'.$rid.'">'.$extra.'<button class="btn '.$cls.'" '.($conf ? 'data-confirm="'.$conf.'"' : '').'>'.$label.'</button></form> '; }

if ($tab === 'users'):
  $s = trim($_GET['s'] ?? ''); $rs = $_GET['r'] ?? '';
  $us = all("SELECT u.*, o.name oname FROM users u LEFT JOIN users o ON o.id=u.org_id WHERE (u.name LIKE ? OR u.email LIKE ?)".($rs ? " AND u.role=?" : "")." ORDER BY u.id DESC", $rs ? ["%$s%", "%$s%", $rs] : ["%$s%", "%$s%"]);
  $orgs = all("SELECT id,name FROM users WHERE role='organization'"); ?>
<form class="card filters" method="get"><input type="hidden" name="tab" value="users"><div style="flex:2"><label>Search</label><input name="s" value="<?= e($s) ?>" placeholder="Name or email"></div><div><label>Role</label><select name="r"><option value="">All</option><?php foreach (['admin', 'instructor', 'learner', 'organization'] as $r) echo '<option '.($rs == $r ? 'selected' : '').'>'.$r.'</option>'; ?></select></div><button class="btn">Filter</button></form>
<div class="cols"><div class="card"><h3>Users (<?= count($us) ?>)</h3><div class="tw"><table><tr><th>User</th><th>Role</th><th>Status</th><th></th></tr>
<?php foreach ($us as $r): ?><tr><td><b><?= e($r['name']) ?></b><br><small class="muted"><?= e($r['email']) ?><?= $r['oname'] ? ' · '.e($r['oname']) : '' ?></small></td>
<td><?php if ($r['id'] != $u['id']): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="rid" value="<?= $r['id'] ?>"><select name="role" onchange="this.form.submit()" style="padding:5px 8px;width:auto"><?php foreach (['admin', 'instructor', 'learner', 'organization'] as $x) echo '<option '.($r['role'] == $x ? 'selected' : '').'>'.$x.'</option>'; ?></select></form><?php else: echo '<span class="badge">you</span>'; endif; ?></td>
<td><span class="badge <?= $r['status'] === 'active' ? '' : 'bad' ?>"><?= $r['status'] ?></span></td>
<td class="right"><?php if ($r['id'] != $u['id']) echo pf('status', $r['id'], $r['status'] === 'active' ? 'Suspend' : 'Activate', 'btn-sm btn-outline').pf('del_user', $r['id'], 'Delete', 'btn-sm btn-danger', '', 'Delete this user and all their data?'); ?></td></tr><?php endforeach; ?></table></div></div>
<form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add_user"><h3>Add user</h3><label style="margin-top:0">Name</label><input name="name" required><label>Email</label><input type="email" name="email" required><label>Password</label><input type="password" name="password" required minlength="6">
<label>Role</label><select name="role"><option>learner</option><option>instructor</option><option>organization</option><option>admin</option></select><label>Organization (optional)</label><select name="org_id"><option value="">—</option><?php foreach ($orgs as $o) echo '<option value="'.$o['id'].'">'.e($o['name']).'</option>'; ?></select><div class="form-actions"><button class="btn">Create user</button></div></form></div>

<?php elseif ($tab === 'courses'): $cs = all("SELECT c.*, u.name inst, (SELECT COUNT(*) FROM enrollments WHERE course_id=c.id) n FROM courses c JOIN users u ON u.id=c.instructor_id ORDER BY FIELD(c.status,'pending','approved','rejected'), c.id DESC"); ?>
<div class="card"><div class="tw"><table><tr><th>Course</th><th>Instructor</th><th>Learners</th><th>Status</th><th></th></tr>
<?php foreach ($cs as $c): ?><tr><td><a href="course.php?id=<?= $c['id'] ?>"><b><?= e($c['title']) ?></b></a></td><td><?= e($c['inst']) ?></td><td><?= $c['n'] ?></td><td><span class="badge <?= $c['status'] === 'approved' ? '' : ($c['status'] === 'pending' ? 'warn' : 'bad') ?>"><?= $c['status'] ?></span></td>
<td class="right"><?php if ($c['status'] !== 'approved') echo pf('course_status', $c['id'], 'Approve', 'btn-sm', '<input type="hidden" name="status" value="approved">');
 if ($c['status'] !== 'rejected') echo pf('course_status', $c['id'], 'Reject', 'btn-sm btn-outline', '<input type="hidden" name="status" value="rejected">');
 echo pf('del_course', $c['id'], 'Delete', 'btn-sm btn-danger', '', 'Delete this course permanently?'); ?></td></tr><?php endforeach; ?></table></div></div>

<?php elseif ($tab === 'categories'): $ks = all("SELECT k.*, (SELECT COUNT(*) FROM courses WHERE category_id=k.id) n FROM categories k ORDER BY name"); ?>
<div class="cols"><div class="card"><h3>Course categories</h3><?php foreach ($ks as $k): ?><div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--line)"><span><b><?= e($k['name']) ?></b> <small class="muted"><?= $k['n'] ?> courses</small></span><?= pf('del_cat', $k['id'], 'Delete', 'btn-sm btn-danger', '', 'Delete this category?') ?></div><?php endforeach; ?></div>
<form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add_cat"><h3>Add category</h3><input name="name" placeholder="e.g. Finance" required><div class="form-actions"><button class="btn">Add</button></div></form></div>

<?php elseif ($tab === 'announcements'): $an = all("SELECT * FROM announcements ORDER BY id DESC"); ?>
<div class="cols"><div class="card"><h3>Published announcements</h3><?php foreach ($an as $x): ?><div style="padding:12px 0;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;gap:12px"><div><b><?= e($x['title']) ?></b><p><?= e($x['body']) ?></p><small class="muted"><?= fdate($x['created_at'], true) ?></small></div><?= pf('del_ann', $x['id'], 'Remove', 'btn-sm btn-danger', '', 'Remove?') ?></div><?php endforeach; if (!$an) echo '<p class="muted">None yet.</p>'; ?></div>
<form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add_ann"><h3>New announcement</h3><label style="margin-top:0">Title</label><input name="title" required><label>Message</label><textarea name="body"></textarea><div class="form-actions"><button class="btn">Publish & notify all</button></div></form></div>

<?php elseif ($tab === 'orgs'):
  $os = all("SELECT u.*, (SELECT COUNT(*) FROM users WHERE org_id=u.id AND role='learner') ln, (SELECT COUNT(*) FROM courses WHERE org_id=u.id) cn FROM users u WHERE u.role='organization' ORDER BY FIELD(u.org_status,'pending','approved','rejected'), u.id DESC");
  $allp = all("SELECT * FROM plans WHERE active=1 ORDER BY name, FIELD(billing,'monthly','yearly')"); ?>
<div class="card"><h3>Organizations (<?= count($os) ?>) <small class="muted">Approve new organizations, then they choose a plan — or grant one yourself.</small></h3>
<?php foreach ($os as $o): $stt = org_state($o); $sbn = org_sub($o['id']); ?>
 <div class="lrow"><div class="lmain"><span><b><?= e($o['name']) ?></b> <span class="badge <?= $o['org_status'] === 'approved' ? '' : ($o['org_status'] === 'pending' ? 'warn' : 'bad') ?>"><?= e($o['org_status']) ?></span><br><small class="muted"><?= e($o['email']) ?> · registered <?= fdate($o['created_at']) ?></small></span>
  <span class="lact"><?php if ($o['org_status'] !== 'approved') echo pf('org_status', $o['id'], 'Approve', 'btn-sm', '<input type="hidden" name="do" value="approve">'); if ($o['org_status'] !== 'rejected') echo pf('org_status', $o['id'], 'Reject', 'btn-sm btn-outline', '<input type="hidden" name="do" value="reject">', 'Reject this organization?'); ?></span></div>
  <p style="margin:8px 0 4px;font-size:14px">💎 <?= $sbn ? '<b>'.e($sbn['plan_name']).'</b> ('.e($sbn['billing']).') until '.fdate($sbn['end_at']).($sbn['sponsored'] ? ' · sponsored' : '') : ['pending' => 'Waiting for approval', 'rejected' => 'Rejected', 'payment_pending' => 'Payment waiting to be confirmed', 'expired' => 'Subscription expired', 'need_plan' => 'Approved — no plan yet'][$stt] ?>
   <?php if ($sbn): ?> · 👥 <?= (int)$o['ln'] ?>/<?= $sbn['max_learners'] ?: '∞' ?> learners · 📚 <?= (int)$o['cn'] ?>/<?= $sbn['max_courses'] ?: '∞' ?> courses<?php endif; ?></p>
  <details><summary style="cursor:pointer;font-weight:600">🎁 Grant a plan (free / sponsored / custom price)</summary>
   <form method="post" class="ledit"><?= csrf_field() ?><input type="hidden" name="action" value="grant_plan"><input type="hidden" name="rid" value="<?= $o['id'] ?>">
    <div class="fr"><div><label style="margin-top:0">Plan</label><select name="plan_id"><?php foreach ($allp as $p) echo '<option value="'.$p['id'].'">'.e($p['name']).' · '.ucfirst($p['billing']).' ('.money($p['price']).')</option>'; ?></select></div>
    <div><label style="margin-top:0">Custom price (optional)</label><input type="number" step="0.01" min="0" name="amount" placeholder="Plan price"></div>
    <div><label>Learner limit (optional, 0 = unlimited)</label><input type="number" min="0" name="max_learners" placeholder="From plan"></div><div><label>Course limit (optional, 0 = unlimited)</label><input type="number" min="0" name="max_courses" placeholder="From plan"></div></div>
    <label class="opt" style="margin-top:12px"><input type="checkbox" name="free" value="1"> Free / sponsored (internal Telesom department or partner — no payment)</label>
    <div class="form-actions"><button class="btn btn-sm">Grant & activate</button></div></form></details></div>
<?php endforeach; if (!$os) echo '<div class="empty">No organizations have registered yet.</div>'; ?></div>

<?php elseif ($tab === 'subs'):
  $ss = all("SELECT s.*, u.name oname FROM org_subscriptions s JOIN users u ON u.id=s.org_id ORDER BY (s.status='pending') DESC, s.id DESC");
  $srev = one("SELECT COALESCE(SUM(amount),0) s FROM org_subscriptions WHERE status='active' AND sponsored=0")['s']; $sact = count(array_filter($ss, fn($x) => $x['status'] === 'active' && $x['end_at'] >= date('Y-m-d') && $x['start_at'] <= date('Y-m-d'))); ?>
<div class="cards"><div class="kpi solid"><div class="ic">💰</div><div><h3><?= money($srev) ?></h3><p>Subscription revenue</p></div></div><div class="kpi"><div class="ic">✅</div><div><h3><?= $sact ?></h3><p>Active subscriptions</p></div></div><div class="kpi"><div class="ic">⏳</div><div><h3><?= count(array_filter($ss, fn($x) => $x['status'] === 'pending')) ?></h3><p>Payments to confirm</p></div></div></div>
<div class="card"><h3>Subscription payments</h3><div class="tw"><table><tr><th>Invoice</th><th>Organization</th><th>Plan</th><th>Amount</th><th>Paid via</th><th>Period</th><th>Status</th><th></th></tr>
<?php foreach ($ss as $x): ?><tr><td><a href="org_invoice.php?id=<?= (int)$x['id'] ?>"><b><?= e($x['invoice']) ?></b></a><br><small class="muted"><?= fdate($x['created_at']) ?></small></td><td><?= e($x['oname']) ?></td><td><?= e($x['plan_name']) ?> <span class="badge grey"><?= e($x['billing']) ?></span></td><td><?= $x['sponsored'] ? 'Free' : money($x['amount']) ?></td>
<td><?= $x['sponsored'] ? 'Sponsored' : strtoupper(e($x['method'])).'<br><small class="muted">'.e($x['phone']).' · '.e($x['reference']).'</small>' ?></td><td><?= $x['start_at'] ? fdate($x['start_at']).' → '.fdate($x['end_at']) : '—' ?></td>
<td><span class="badge <?= ['active' => '', 'pending' => 'warn', 'rejected' => 'bad', 'cancelled' => 'grey'][$x['status']] ?>"><?= e($x['status']) ?></span></td>
<td class="right"><?php if ($x['status'] === 'pending') echo pf('sub_confirm', $x['id'], 'Confirm', 'btn-sm', '<input type="hidden" name="do" value="confirm">').pf('sub_confirm', $x['id'], 'Reject', 'btn-sm btn-danger', '<input type="hidden" name="do" value="reject">', 'Reject this payment?'); ?></td></tr><?php endforeach; ?></table></div><?php if (!$ss) echo '<div class="empty">No subscriptions yet.</div>'; ?></div>

<?php elseif ($tab === 'plans'): $pls = all("SELECT * FROM plans ORDER BY sort_order, id"); ?>
<div class="card"><h3>Plans & pricing <small class="muted">Prices are in USD. Use 0 for unlimited learners/courses. Put "|" between feature lines.</small></h3>
<?php foreach ($pls as $p): ?><form method="post" class="ledit" style="margin-bottom:12px"><?= csrf_field() ?><input type="hidden" name="action" value="plan_save"><input type="hidden" name="rid" value="<?= $p['id'] ?>">
 <div class="fr"><div><label style="margin-top:0">Name</label><input name="name" value="<?= e($p['name']) ?>" required></div><div><label style="margin-top:0">Billing</label><select name="billing"><option value="monthly" <?= $p['billing'] === 'monthly' ? 'selected' : '' ?>>Monthly</option><option value="yearly" <?= $p['billing'] === 'yearly' ? 'selected' : '' ?>>Yearly</option></select></div>
 <div><label>Price (USD)</label><input type="number" step="0.01" min="0" name="price" value="<?= e($p['price']) ?>"></div><div><label>Learners</label><input type="number" min="0" name="max_learners" value="<?= (int)$p['max_learners'] ?>"></div><div><label>Private courses</label><input type="number" min="0" name="max_courses" value="<?= (int)$p['max_courses'] ?>"></div>
 <div><label>Features (separate with |)</label><input name="features" value="<?= e($p['features']) ?>"></div></div>
 <label class="opt" style="margin-top:10px"><input type="checkbox" name="active" value="1" <?= $p['active'] ? 'checked' : '' ?>> Visible to organizations</label><div class="form-actions"><button class="btn btn-sm">Save plan</button></div></form><?php endforeach; ?></div>
<form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="plan_save"><input type="hidden" name="rid" value="0"><h3>Add a new plan</h3>
 <div class="fr"><div><label style="margin-top:0">Name</label><input name="name" placeholder="e.g. Government" required></div><div><label style="margin-top:0">Billing</label><select name="billing"><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select></div>
 <div><label>Price (USD)</label><input type="number" step="0.01" min="0" name="price" value="0"></div><div><label>Learners</label><input type="number" min="0" name="max_learners" value="50"></div><div><label>Private courses</label><input type="number" min="0" name="max_courses" value="10"></div><div><label>Features</label><input name="features" placeholder="Feature one|Feature two"></div></div>
 <input type="hidden" name="active" value="1"><div class="form-actions"><button class="btn">Add plan</button></div></form>

<?php else: $rv = all("SELECT r.*, u.name, c.title FROM reviews r JOIN users u ON u.id=r.user_id JOIN courses c ON c.id=r.course_id WHERE r.reported=1"); ?>
<div class="card"><h3>Reported reviews</h3><?php foreach ($rv as $r): ?><div style="padding:12px 0;border-bottom:1px solid var(--line)"><b><?= e($r['name']) ?></b> on <i><?= e($r['title']) ?></i> <span class="star"><?= stars($r['rating']) ?></span><p><?= e($r['body']) ?></p>
<form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="review"><input type="hidden" name="rid" value="<?= $r['id'] ?>"><button name="do" value="delete" class="btn btn-sm btn-danger">Delete review</button> <button name="do" value="keep" class="btn btn-sm btn-outline">Dismiss report</button></form></div><?php endforeach; if (!$rv) echo '<div class="empty">No reported content 🎉</div>'; ?></div>
<?php endif; include 'includes/footer.php'; ?>
