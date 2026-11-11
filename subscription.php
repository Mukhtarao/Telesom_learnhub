<?php
require 'includes/config.php';
$u = require_login('organization'); $oid = $u['id'];
$state = org_state($u); $sub = org_sub($oid); $use = org_usage($oid);
$plans = all("SELECT * FROM plans WHERE active=1 ORDER BY sort_order, price");
$hist = all("SELECT * FROM org_subscriptions WHERE org_id=? ORDER BY id DESC", [$oid]);
$pend = one("SELECT * FROM org_subscriptions WHERE org_id=? AND status='pending' ORDER BY id DESC LIMIT 1", [$oid]);
$last = one("SELECT * FROM org_subscriptions WHERE org_id=? AND status='active' ORDER BY end_at DESC LIMIT 1", [$oid]);
$title = 'Subscription'; include 'includes/header.php';
function usage_bar($label, $used, $max){ $pct = $max ? min(100, round($used * 100 / $max)) : 0; ?>
  <div style="margin:12px 0"><div style="display:flex;justify-content:space-between;font-weight:600;font-size:14px"><span><?= $label ?></span><span><?= $used ?> / <?= $max ?: '∞' ?></span></div><div class="bar" style="margin-top:6px"><i style="width:<?= $max ? $pct : 8 ?>%;<?= $pct >= 90 ? 'background:linear-gradient(90deg,#f59e0b,#dc2626)' : '' ?>"></i></div></div>
<?php } ?>

<?php if ($state === 'pending'): ?>
<div class="card empty"><div style="font-size:54px">⏳</div><h2>Your organization is waiting for approval</h2>
  <p style="max-width:560px;margin:8px auto 0">Our team reviews every organization before activation. You will get a notification here as soon as it is approved — then you can pick a monthly or yearly plan and start adding your learners.</p></div>
<?php elseif ($state === 'rejected'): ?>
<div class="card empty"><div style="font-size:54px">❌</div><h2>Your request was not approved</h2><p style="max-width:560px;margin:8px auto 0">Please contact the Telesom LearnHub administrator at <b>learnhub@telesom.com</b> if you think this is a mistake.</p></div>
<?php else: ?>
<?php if ($state === 'need_plan'): ?><div class="flash">✅ <span>Your organization is <b>approved</b>. Choose a plan below to activate your account.</span></div>
<?php elseif ($state === 'expired'): ?><div class="flash error"><span>⚠ Your subscription ended on <b><?= fdate($last['end_at']) ?></b>. Renew to restore access for your members and courses.</span></div>
<?php elseif ($state === 'payment_pending'): ?><div class="flash warn"><span>💳 Payment received for <b><?= e($pend['plan_name']) ?></b> (ref <b><?= e($pend['reference']) ?></b>) — waiting for an administrator to confirm it.</span></div><?php endif; ?>

<?php if ($sub): $left = max(0, (int)ceil((strtotime($sub['end_at']) - time()) / 86400)); ?>
<div class="cols"><div class="card"><h3>Current plan <span class="badge"><?= $sub['sponsored'] ? 'Sponsored' : 'Active' ?></span></h3>
  <h2 style="font-size:28px"><?= e($sub['plan_name']) ?> <small class="muted" style="font-size:15px">· <?= $sub['billing'] === 'yearly' ? 'Yearly' : 'Monthly' ?></small></h2>
  <p>Valid <b><?= fdate($sub['start_at']) ?></b> → <b><?= fdate($sub['end_at']) ?></b> · <b><?= $left ?></b> day<?= $left == 1 ? '' : 's' ?> left</p>
  <div class="form-actions"><a class="btn btn-sm btn-outline" href="org_invoice.php?id=<?= (int)$sub['id'] ?>">🧾 Invoice</a><a class="btn btn-sm" href="organization.php">Manage members</a></div></div>
 <div class="card"><h3>Usage</h3><?php usage_bar('Learners', $use['learners'], (int)$sub['max_learners']); usage_bar('Private courses', $use['courses'], (int)$sub['max_courses']); ?></div></div>
<?php endif; ?>

<div class="card"><h3><?= $sub ? 'Renew or change plan' : 'Choose your plan' ?></h3>
<p style="margin-bottom:18px">Your organization pays one subscription — your learners never pay for private courses. <?= $sub ? 'A renewal starts the day after your current period ends.' : '' ?></p>
<?php $cta = fn($p) => $pend ? ['Payment pending', '#'] : [$sub ? 'Renew / switch' : 'Choose '.$p['name'], 'subscribe.php?plan='.$p['id']]; $cur = $sub['plan_id'] ?? null; include 'includes/plans_view.php'; ?></div>
<?php endif; ?>

<?php if ($hist): ?><div class="card"><h3>Subscription history</h3><div class="tw"><table><tr><th>Invoice</th><th>Plan</th><th>Amount</th><th>Period</th><th>Status</th><th></th></tr>
<?php foreach ($hist as $h): ?><tr><td><b><?= e($h['invoice']) ?></b><br><small class="muted"><?= fdate($h['created_at']) ?></small></td><td><?= e($h['plan_name']) ?> <span class="badge grey"><?= e($h['billing']) ?></span></td><td><?= $h['sponsored'] ? 'Free (sponsored)' : money($h['amount']) ?></td>
<td><?= $h['start_at'] ? fdate($h['start_at']).' → '.fdate($h['end_at']) : '—' ?></td><td><span class="badge <?= ['active' => '', 'pending' => 'warn', 'rejected' => 'bad', 'cancelled' => 'grey'][$h['status']] ?>"><?= $h['status'] === 'pending' ? 'awaiting confirmation' : e($h['status']) ?></span></td><td class="right"><a class="btn btn-sm btn-outline" href="org_invoice.php?id=<?= (int)$h['id'] ?>">Invoice</a></td></tr><?php endforeach; ?></table></div></div><?php endif; ?>
<?php include 'includes/footer.php'; ?>
