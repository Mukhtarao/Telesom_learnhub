<?php
require 'includes/config.php';
$u = require_login('organization'); $oid = $u['id'];
$state = org_state($u);
if (in_array($state, ['pending', 'rejected'])) redirect('subscription.php');
$plan = one("SELECT * FROM plans WHERE id=? AND active=1", [(int)($_GET['plan'] ?? 0)]);
if (!$plan) { flash('That plan is not available.', 'error'); redirect('subscription.php'); }
if (one("SELECT id FROM org_subscriptions WHERE org_id=? AND status='pending'", [$oid])) { flash('You already have a payment waiting for confirmation.', 'error'); redirect('subscription.php'); }
$manual = PAYMENT_MODE === 'manual';
[$pStart, $pEnd] = org_period($oid, $plan['billing']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $m = $_POST['method'] ?? ''; $ph = preg_replace('/[^0-9+]/', '', $_POST['phone'] ?? ''); $ref = trim($_POST['reference'] ?? '');
    if (!in_array($m, ['zaad', 'edahab', 'bank'])) flash('Choose a payment method.', 'error');
    elseif ($m !== 'bank' && strlen($ph) < 7) flash('Enter the mobile number you are paying from.', 'error');
    elseif ($manual && strlen($ref) < 4) flash('Enter the transaction ID from your payment confirmation.', 'error');
    else {
        if (!$manual) $ref = 'DEMO-'.strtoupper($m).'-'.random_int(100000, 999999);
        q("INSERT INTO org_subscriptions(org_id,plan_id,plan_name,billing,amount,max_learners,max_courses,status,method,phone,reference) VALUES(?,?,?,?,?,?,?,'pending',?,?,?)",
          [$oid, $plan['id'], $plan['name'], $plan['billing'], $plan['price'], $plan['max_learners'], $plan['max_courses'], $m, $ph, $ref]);
        $sid = (int)q("SELECT LAST_INSERT_ID() i")->fetch()['i']; sub_set_invoice($sid);
        if ($manual) {
            foreach (all("SELECT id FROM users WHERE role='admin'") as $ad) notify($ad['id'], '🧾 New subscription payment to confirm: '.$u['name'].' — '.$plan['name'].' ('.$plan['billing'].') '.money($plan['price']), 'admin.php?tab=subs');
            flash('Payment submitted. Your plan starts as soon as an administrator confirms it.'); redirect('subscription.php');
        }
        org_activate($sid); flash('Subscription activated — welcome aboard!'); redirect("org_invoice.php?id=$sid");
    }
}
$title = 'Subscribe'; include 'includes/header.php';
?>
<div class="cols"><div>
 <form method="post" class="card"><?= csrf_field() ?><h3>1 · Choose payment method</h3>
  <div class="pay-grid">
   <label class="pay-opt"><input type="radio" name="method" value="zaad" required checked><span class="pm zaad">Z</span><b>ZAAD</b><small>Telesom mobile money</small></label>
   <label class="pay-opt"><input type="radio" name="method" value="edahab"><span class="pm edahab">e</span><b>eDahab</b><small>Mobile money</small></label>
   <label class="pay-opt"><input type="radio" name="method" value="bank"><span class="pm bank">🏦</span><b>Bank transfer</b><small>Manual confirmation</small></label>
  </div>
  <h3 style="margin-top:22px">2 · Payment details</h3>
  <?php if ($manual): ?><div class="demo" style="margin:0 0 14px"><b>How to pay:</b> send <b><?= money($plan['price']) ?></b> to<br>ZAAD: <b><?= e(MERCHANT_ZAAD) ?></b> · eDahab: <b><?= e(MERCHANT_EDAHAB) ?></b><br><?= e(MERCHANT_BANK) ?><br>Then enter the transaction ID below. An administrator confirms it, usually within one working day.</div>
  <?php else: ?><div class="demo" style="margin:0 0 14px"><b>Demo mode:</b> the subscription activates instantly (no real money is moved). Set <code>PAYMENT_MODE</code> to <code>manual</code> in <code>includes/config.php</code> for real payments.</div><?php endif; ?>
  <label style="margin-top:0">Mobile number you are paying from</label><input name="phone" placeholder="e.g. 063 4xx xxxx" value="<?= e($u['phone']) ?>">
  <?php if ($manual): ?><label>Transaction ID / reference</label><input name="reference" placeholder="From your payment confirmation" required><?php endif; ?>
  <div class="form-actions"><button class="btn btn-block" style="padding:14px"><?= $manual ? 'Submit payment' : 'Pay '.money($plan['price']).' & activate' ?></button></div>
  <p style="text-align:center;margin-top:10px;font-size:13px">🔒 Never share your PIN. We only record your number and transaction reference.</p>
 </form></div>
 <div class="card sticky"><h3>Order summary</h3>
  <h2 style="font-size:24px"><?= e($plan['name']) ?> <span class="badge"><?= $plan['billing'] === 'yearly' ? 'Yearly' : 'Monthly' ?></span></h2>
  <ul class="checks"><li><b><?= ((int)$plan['max_learners']) ?: 'Unlimited' ?></b> learners</li><li><b><?= ((int)$plan['max_courses']) ?: 'Unlimited' ?></b> private courses</li><?php foreach (array_filter(array_map('trim', explode('|', (string)$plan['features']))) as $f) echo '<li>'.e($f).'</li>'; ?></ul>
  <div style="display:flex;justify-content:space-between;padding:14px 0;border-top:1px solid var(--line);margin-top:12px"><span>Period</span><b><?= fdate($pStart) ?> → <?= fdate($pEnd) ?></b></div>
  <div style="display:flex;justify-content:space-between;font-size:20px"><b>Total</b><b style="color:var(--g)"><?= money($plan['price']) ?></b></div>
  <p class="muted" style="margin-top:10px;font-size:13px">Billed <?= $plan['billing'] === 'yearly' ? 'once a year' : 'every month' ?>. Renew any time from the Subscription page.</p></div>
</div>
<?php include 'includes/footer.php'; ?>
