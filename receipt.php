<?php
require 'includes/config.php';
$u = require_login();
$p = one("SELECT p.*, c.title, c.instructor_id, c.org_id, u.name, u.email FROM payments p JOIN courses c ON c.id=p.course_id JOIN users u ON u.id=p.user_id WHERE p.id=? AND p.status='paid'", [(int)($_GET['id'] ?? 0)]);
if (!$p || !($u['role'] === 'admin' || $p['user_id'] == $u['id'])) { flash('Receipt not found.', 'error'); redirect('payments.php'); }
$title = 'Receipt'; include 'includes/header.php';
?>
<div class="card receipt"><div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px"><a class="brand" href="index.php"><?= logo() ?><span>Telesom <b>LearnHub</b></span></a><span class="badge" style="font-size:14px">✔ PAID</span></div>
<hr style="border:0;border-top:2px solid var(--gl);margin:18px 0"><h2>Payment receipt</h2>
<div class="fr" style="margin:14px 0"><div><p>Invoice</p><b><?= e($p['invoice']) ?></b></div><div><p>Date</p><b><?= fdate($p['paid_at'] ?: $p['created_at'], true) ?></b></div><div style="margin-top:12px"><p>Billed to</p><b><?= e($p['name']) ?></b><br><small class="muted"><?= e($p['email']) ?></small></div><div style="margin-top:12px"><p>Payment method</p><b><?= ['zaad' => 'ZAAD', 'edahab' => 'eDahab', 'bank' => 'Bank transfer'][$p['method']] ?></b><br><small class="muted">Ref: <?= e($p['reference']) ?></small></div></div>
<table><tr><th>Description</th><th class="right">Amount</th></tr><tr><td><?= e($p['title']) ?> — full course access</td><td class="right"><?= money($p['amount']) ?></td></tr><tr><td class="right"><b>Total paid</b></td><td class="right"><b style="font-size:20px;color:var(--g)"><?= money($p['amount']) ?></b></td></tr></table>
<p style="margin-top:18px;text-align:center">Thank you for learning with Telesom LearnHub.</p>
<div class="form-actions no-print" style="justify-content:center"><button class="btn" onclick="window.print()">🖨 Print / Save PDF</button><a class="btn btn-outline" href="learn.php?course=<?= $p['course_id'] ?>">Start learning</a><a class="btn btn-outline" href="payments.php">All payments</a></div></div>
<?php include 'includes/footer.php'; ?>
