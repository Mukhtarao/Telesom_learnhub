<?php
require 'includes/config.php';
$u = require_login();
$s = one("SELECT s.*, o.name oname, o.email FROM org_subscriptions s JOIN users o ON o.id=s.org_id WHERE s.id=?", [(int)($_GET['id'] ?? 0)]);
if (!$s || !($u['role'] === 'admin' || $s['org_id'] == $u['id'])) { flash('Invoice not found.', 'error'); redirect('dashboard.php'); }
$paid = $s['status'] === 'active';
$title = 'Invoice'; include 'includes/header.php';
?>
<div class="card receipt"><div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px"><a class="brand" href="index.php"><?= logo() ?><span>Telesom <b>LearnHub</b></span></a>
  <span class="badge <?= $paid ? '' : ($s['status'] === 'pending' ? 'warn' : 'bad') ?>" style="font-size:14px"><?= $paid ? ($s['sponsored'] ? '✔ SPONSORED' : '✔ PAID') : ($s['status'] === 'pending' ? '⏳ AWAITING CONFIRMATION' : strtoupper(e($s['status']))) ?></span></div>
<hr style="border:0;border-top:2px solid var(--gl);margin:18px 0"><h2>Subscription invoice</h2>
<div class="fr" style="margin:14px 0"><div><p>Invoice</p><b><?= e($s['invoice']) ?></b></div><div><p>Date</p><b><?= fdate($s['created_at'], true) ?></b></div>
 <div style="margin-top:12px"><p>Billed to</p><b><?= e($s['oname']) ?></b><br><small class="muted"><?= e($s['email']) ?></small></div>
 <div style="margin-top:12px"><p>Payment method</p><b><?= $s['sponsored'] ? 'Sponsored by Telesom' : ['zaad' => 'ZAAD', 'edahab' => 'eDahab', 'bank' => 'Bank transfer', 'none' => '—'][$s['method']] ?></b><br><small class="muted">Ref: <?= e($s['reference'] ?: '—') ?></small></div></div>
<table><tr><th>Description</th><th class="right">Amount</th></tr>
<tr><td><b><?= e($s['plan_name']) ?></b> — <?= $s['billing'] === 'yearly' ? 'yearly' : 'monthly' ?> subscription<br><small class="muted">Up to <?= $s['max_learners'] ?: 'unlimited' ?> learners · <?= $s['max_courses'] ?: 'unlimited' ?> private courses<?= $s['start_at'] ? ' · '.fdate($s['start_at']).' → '.fdate($s['end_at']) : '' ?></small></td><td class="right"><?= money($s['amount']) ?></td></tr>
<tr><td class="right"><b>Total</b></td><td class="right"><b style="font-size:20px;color:var(--g)"><?= money($s['amount']) ?></b></td></tr></table>
<p style="margin-top:18px;text-align:center">Thank you for partnering with Telesom LearnHub.</p>
<div class="form-actions no-print" style="justify-content:center"><button class="btn" onclick="window.print()">🖨 Print / Save PDF</button><a class="btn btn-outline" href="<?= $u['role'] === 'admin' ? 'admin.php?tab=subs' : 'subscription.php' ?>">Back</a></div></div>
<?php include 'includes/footer.php'; ?>
