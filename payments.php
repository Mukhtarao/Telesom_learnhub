<?php
require 'includes/config.php';
$u = require_login(); $role = $u['role']; $id = $u['id'];
if (!in_array($role, ['learner', 'admin'])) { flash('Course sales are only visible to administrators.', 'error'); redirect('dashboard.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'admin') {
    $p = one("SELECT p.*, c.title FROM payments p JOIN courses c ON c.id=p.course_id WHERE p.id=?", [(int)$_POST['pid']]);
    if ($p && $p['status'] === 'pending') {
        if ($_POST['do'] === 'confirm') { q("UPDATE payments SET status='paid', paid_at=NOW() WHERE id=?", [$p['id']]); grant_paid_access($p['user_id'], $p['course_id']); flash('Payment confirmed and access granted.'); }
        else { q("UPDATE payments SET status='rejected' WHERE id=?", [$p['id']]); q("DELETE FROM enrollments WHERE user_id=? AND course_id=? AND status='pending'", [$p['user_id'], $p['course_id']]); notify($p['user_id'], '❌ Your payment for "'.$p['title'].'" could not be verified. Please try again.', 'checkout.php?course='.$p['course_id']); flash('Payment rejected.'); }
    }
    redirect('payments.php');
}
$sc = $role === 'admin' ? '1=1' : ($role === 'learner' ? "p.user_id=$id" : ($role === 'organization' ? "c.org_id=$id" : "c.instructor_id=$id"));
$st = $_GET['status'] ?? ''; $w = in_array($st, ['pending', 'paid', 'rejected']) ? " AND p.status='$st'" : '';
$rows = all("SELECT p.*, c.title, u.name FROM payments p JOIN courses c ON c.id=p.course_id JOIN users u ON u.id=p.user_id WHERE $sc $w ORDER BY (p.status='pending') DESC, p.id DESC");
$tot = one("SELECT COALESCE(SUM(p.amount),0) s, COUNT(*) n FROM payments p JOIN courses c ON c.id=p.course_id WHERE p.status='paid' AND $sc");
$pn = one("SELECT COUNT(*) n FROM payments p JOIN courses c ON c.id=p.course_id WHERE p.status='pending' AND $sc")['n'];
$title = $role === 'learner' ? 'My Payments' : ($role === 'admin' ? 'Payments' : 'Course Sales'); include 'includes/header.php';
?>
<div class="cards"><div class="kpi solid"><div class="ic">💰</div><div><h3><?= money($tot['s']) ?></h3><p><?= $role === 'learner' ? 'Total paid' : 'Total revenue' ?></p></div></div>
<div class="kpi"><div class="ic">🧾</div><div><h3><?= $tot['n'] ?></h3><p>Completed payments</p></div></div><div class="kpi"><div class="ic">⏳</div><div><h3><?= $pn ?></h3><p>Awaiting confirmation</p></div></div></div>
<div class="tabs"><?php foreach (['' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'rejected' => 'Rejected'] as $k => $l): ?><a class="<?= $st === $k ? 'on' : '' ?>" href="payments.php?status=<?= $k ?>"><?= $l ?></a><?php endforeach; ?></div>
<div class="card"><div class="tw"><table><tr><th>Invoice</th><?php if ($role !== 'learner'): ?><th>Learner</th><?php endif; ?><th>Course</th><th>Method</th><th>Reference</th><th>Amount</th><th>Date</th><th>Status</th><th></th></tr>
<?php foreach ($rows as $p): ?><tr><td><b><?= e($p['invoice']) ?></b></td><?php if ($role !== 'learner'): ?><td><?= e($p['name']) ?></td><?php endif; ?><td><?= e($p['title']) ?></td><td><?= ['zaad' => 'ZAAD', 'edahab' => 'eDahab', 'bank' => 'Bank'][$p['method']] ?><br><small class="muted"><?= e($p['phone']) ?></small></td><td><small><?= e($p['reference']) ?></small></td><td><b><?= money($p['amount']) ?></b></td><td><?= fdate($p['created_at']) ?></td>
<td><span class="badge <?= ['paid' => '', 'pending' => 'warn', 'rejected' => 'bad'][$p['status']] ?>"><?= $p['status'] ?></span></td>
<td class="right"><?php if ($p['status'] === 'paid'): ?><a class="btn btn-sm btn-outline" href="receipt.php?id=<?= $p['id'] ?>">Receipt</a><?php endif;
 if ($role === 'admin' && $p['status'] === 'pending'): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="pid" value="<?= $p['id'] ?>"><button name="do" value="confirm" class="btn btn-sm">Confirm</button> <button name="do" value="reject" class="btn btn-sm btn-danger" data-confirm="Reject this payment?">Reject</button></form><?php endif;
 if ($role === 'learner' && $p['status'] === 'rejected'): ?><a class="btn btn-sm" href="checkout.php?course=<?= $p['course_id'] ?>">Retry</a><?php endif; ?></td></tr><?php endforeach; ?></table></div>
<?php if (!$rows) echo '<div class="empty">🧾 No payments yet.</div>'; ?></div>
<?php include 'includes/footer.php'; ?>
