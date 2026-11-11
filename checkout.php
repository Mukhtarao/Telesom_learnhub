<?php
require 'includes/config.php';
$u = require_login('learner'); $cid = (int)($_GET['course'] ?? 0);
$c = one("SELECT c.*, u.name inst FROM courses c JOIN users u ON u.id=c.instructor_id WHERE c.id=? AND c.status='approved' AND c.enroll_type='paid'", [$cid]);
if (!$c) { flash('This course is not available for purchase.', 'error'); redirect('courses.php'); }
$en = one("SELECT * FROM enrollments WHERE user_id=? AND course_id=?", [$u['id'], $cid]);
if ($en && $en['status'] === 'active') { flash('You already have access to this course.'); redirect("learn.php?course=$cid"); }
$pend = one("SELECT * FROM payments WHERE user_id=? AND course_id=? AND status='pending'", [$u['id'], $cid]);
$manual = PAYMENT_MODE === 'manual';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pend) {
    $m = $_POST['method'] ?? ''; $ph = preg_replace('/[^0-9+]/', '', $_POST['phone'] ?? ''); $ref = trim($_POST['reference'] ?? '');
    if (!in_array($m, ['zaad', 'edahab', 'bank'])) flash('Choose a payment method.', 'error');
    elseif ($m !== 'bank' && strlen($ph) < 7) flash('Enter the mobile number you are paying from.', 'error');
    elseif ($manual && strlen($ref) < 4) flash('Enter the transaction ID from your payment confirmation.', 'error');
    else {
        $status = $manual ? 'pending' : 'paid'; if (!$manual) $ref = 'DEMO-'.strtoupper($m).'-'.random_int(100000, 999999);
        q("INSERT INTO payments(user_id,course_id,amount,method,phone,reference,status,paid_at) VALUES(?,?,?,?,?,?,?,?)", [$u['id'], $cid, $c['price'], $m, $ph, $ref, $status, $manual ? null : date('Y-m-d H:i:s')]);
        $pid = q("SELECT LAST_INSERT_ID() i")->fetch()['i'];
        q("UPDATE payments SET invoice=? WHERE id=?", ['INV-'.date('Ymd').'-'.str_pad($pid, 4, '0', STR_PAD_LEFT), $pid]);
        if ($manual) {
            q("INSERT IGNORE INTO enrollments(user_id,course_id,status,paid) VALUES(?,?, 'pending',0)", [$u['id'], $cid]);
            foreach (all("SELECT id FROM users WHERE role='admin'") as $ad) notify($ad['id'], '💳 New payment to confirm: '.$u['name'].' — '.money($c['price']).' for '.$c['title'], 'payments.php');
            flash('Payment submitted. You will get access as soon as it is confirmed.'); redirect('payments.php');
        }
        grant_paid_access($u['id'], $cid); flash('Payment successful! Welcome to the course.'); redirect("receipt.php?id=$pid");
    }
}
$title = 'Checkout'; include 'includes/header.php';
?>
<div class="cols">
 <div>
 <?php if ($pend): ?><div class="card empty"><div style="font-size:48px">⏳</div><h3 style="justify-content:center">Payment awaiting confirmation</h3><p>We received your payment details (ref <b><?= e($pend['reference']) ?></b>). An administrator will confirm it shortly.</p><a class="btn" href="payments.php">View my payments</a></div>
 <?php else: ?>
 <form method="post" class="card"><?= csrf_field() ?><h3>1 · Choose payment method</h3>
  <div class="pay-grid">
   <label class="pay-opt"><input type="radio" name="method" value="zaad" required checked><span class="pm zaad">Z</span><b>ZAAD</b><small>Telesom mobile money</small></label>
   <label class="pay-opt"><input type="radio" name="method" value="edahab"><span class="pm edahab">e</span><b>eDahab</b><small>Mobile money</small></label>
   <label class="pay-opt"><input type="radio" name="method" value="bank"><span class="pm bank">🏦</span><b>Bank transfer</b><small>Manual confirmation</small></label>
  </div>
  <h3 style="margin-top:22px">2 · Payment details</h3>
  <?php if ($manual): ?>
   <div class="demo" style="margin:0 0 14px"><b>How to pay:</b> send <b><?= money($c['price']) ?></b> to<br>ZAAD: <b><?= e(MERCHANT_ZAAD) ?></b> · eDahab: <b><?= e(MERCHANT_EDAHAB) ?></b><br><?= e(MERCHANT_BANK) ?><br>Then enter the transaction ID below.</div>
  <?php else: ?><div class="demo" style="margin:0 0 14px"><b>Demo mode:</b> payment is confirmed instantly (no real money is moved). Set <code>PAYMENT_MODE</code> to <code>manual</code> in <code>includes/config.php</code> for real payments.</div><?php endif; ?>
  <label style="margin-top:0">Mobile number you are paying from</label><input name="phone" placeholder="e.g. 063 4xx xxxx" value="<?= e($u['phone']) ?>">
  <?php if ($manual): ?><label>Transaction ID / reference</label><input name="reference" placeholder="From your SMS confirmation" required><?php endif; ?>
  <div class="form-actions"><button class="btn btn-block" style="padding:14px"><?= $manual ? 'Submit payment' : 'Pay '.money($c['price']).' now' ?></button></div>
  <p style="text-align:center;margin-top:10px;font-size:13px">🔒 Never share your PIN. We only record your number and transaction reference.</p>
 </form><?php endif; ?>
 </div>
 <div class="card sticky"><h3>Order summary</h3>
  <div class="cc" style="box-shadow:none"><div class="th c1" data-i="<?= e(substr($c['title'], 0, 1)) ?>" style="height:90px"><?= e($c['level']) ?></div></div>
  <h3 style="margin-top:14px;font-size:18px"><?= e($c['title']) ?></h3><p>by <?= e($c['inst']) ?> · <?= e($c['duration']) ?></p>
  <div style="display:flex;justify-content:space-between;padding:14px 0;border-top:1px solid var(--line);margin-top:12px"><span>Price</span><b><?= money($c['price']) ?></b></div>
  <div style="display:flex;justify-content:space-between;font-size:20px"><b>Total</b><b style="color:var(--g)"><?= money($c['price']) ?></b></div>
  <ul class="checks"><li>Lifetime access to course content</li><li>Quizzes, exams & assignments</li><li>Verified certificate</li></ul></div>
</div>
<?php include 'includes/footer.php'; ?>
