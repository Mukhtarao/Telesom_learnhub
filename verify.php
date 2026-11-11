<?php
require 'includes/config.php';
$u = me(); $code = strtoupper(trim($_GET['code'] ?? ''));
$ce = $code ? one("SELECT ce.*, c.title, c.org_id, u.name, i.name inst, o.name oname, o.signatory, o.signatory_title FROM certificates ce JOIN courses c ON c.id=ce.course_id JOIN users u ON u.id=ce.user_id JOIN users i ON i.id=c.instructor_id LEFT JOIN users o ON o.id=c.org_id WHERE ce.code=?", [$code]) : null;
$vurl = ''; $nm = ''; $isOrg = false;
if ($ce) {
    $isOrg = !empty($ce['org_id']);
    $vurl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\').'/verify.php?code='.$ce['code'];
    $nm = function_exists('mb_convert_case') ? mb_convert_case(mb_strtolower($ce['name'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8') : ucwords(strtolower($ce['name']));
}
$layout = $u ? 'app' : 'public'; $title = 'Verify Certificate'; include 'includes/header.php';
if (!$u) echo '<div class="wrap pubc">'; ?>
<form class="card no-print" method="get"><h3>🔍 Verify a certificate</h3><div class="inline-form"><div><label style="margin-top:0">Certificate code</label><input name="code" placeholder="TLH-XXXXXXXX" value="<?= e($code) ?>" required></div><button class="btn">Verify</button></div><small class="muted">Tip: scanning the QR code on a certificate opens this page automatically.</small></form>
<?php if ($code && !$ce): ?><div class="flash error">❌ No certificate found with that code.</div><?php endif;
if ($ce): ?><div class="flash no-print">✅ Genuine certificate — issued by Telesom LearnHub to <b>&nbsp;<?= e($nm) ?></b><?= $isOrg ? ' in partnership with <b>&nbsp;'.e($ce['oname']).'</b>' : '' ?>.</div>
<div class="card no-print"><h3>Certificate details</h3><div class="vgrid">
<div><small>Holder</small><b><?= e($nm) ?></b></div><div><small>Course</small><b><?= e($ce['title']) ?></b></div>
<div><small>Type</small><b><?= $isOrg ? 'Organization training' : 'Public course' ?></b></div><div><small>Issued by</small><b><?= $isOrg ? e($ce['oname']).' × Telesom LearnHub' : 'Telesom LearnHub' ?></b></div>
<div><small>Instructor</small><b><?= e($ce['inst']) ?></b></div><div><small>Issued on</small><b><?= fdate($ce['issued_at']) ?></b></div>
<div><small>Final score</small><b><?= (int)$ce['score'] ?>%</b></div><div><small>Certificate ID</small><b><?= e($ce['code']) ?></b></div></div></div>
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs><linearGradient id="gold" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f7e27c"/><stop offset=".5" stop-color="#d4a017"/><stop offset="1" stop-color="#a07412"/></linearGradient></defs></svg>
<div class="cert-wrap"><div class="certificate<?= $isOrg ? ' org' : '' ?>" id="certificate">
 <i class="wm"><?= logo() ?></i>
 <svg class="corner tl" viewBox="0 0 100 100" aria-hidden="true"><path d="M3 97V32Q3 3 32 3h65" fill="none" stroke="url(#gold)" stroke-width="2.2"/><path d="M12 97V36Q12 12 36 12h61" fill="none" stroke="#059669" stroke-width="1" opacity=".55"/><circle cx="32" cy="32" r="5.5" fill="url(#gold)"/><circle cx="32" cy="32" r="2.2" fill="#064e3b"/><path d="M45 3q6 9 0 18M3 45q9 6 18 0" fill="none" stroke="url(#gold)" stroke-width="1.4"/></svg><svg class="corner tr" viewBox="0 0 100 100" aria-hidden="true"><path d="M3 97V32Q3 3 32 3h65" fill="none" stroke="url(#gold)" stroke-width="2.2"/><path d="M12 97V36Q12 12 36 12h61" fill="none" stroke="#059669" stroke-width="1" opacity=".55"/><circle cx="32" cy="32" r="5.5" fill="url(#gold)"/><circle cx="32" cy="32" r="2.2" fill="#064e3b"/><path d="M45 3q6 9 0 18M3 45q9 6 18 0" fill="none" stroke="url(#gold)" stroke-width="1.4"/></svg><svg class="corner bl" viewBox="0 0 100 100" aria-hidden="true"><path d="M3 97V32Q3 3 32 3h65" fill="none" stroke="url(#gold)" stroke-width="2.2"/><path d="M12 97V36Q12 12 36 12h61" fill="none" stroke="#059669" stroke-width="1" opacity=".55"/><circle cx="32" cy="32" r="5.5" fill="url(#gold)"/><circle cx="32" cy="32" r="2.2" fill="#064e3b"/><path d="M45 3q6 9 0 18M3 45q9 6 18 0" fill="none" stroke="url(#gold)" stroke-width="1.4"/></svg><svg class="corner br" viewBox="0 0 100 100" aria-hidden="true"><path d="M3 97V32Q3 3 32 3h65" fill="none" stroke="url(#gold)" stroke-width="2.2"/><path d="M12 97V36Q12 12 36 12h61" fill="none" stroke="#059669" stroke-width="1" opacity=".55"/><circle cx="32" cy="32" r="5.5" fill="url(#gold)"/><circle cx="32" cy="32" r="2.2" fill="#064e3b"/><path d="M45 3q6 9 0 18M3 45q9 6 18 0" fill="none" stroke="url(#gold)" stroke-width="1.4"/></svg>
 <div class="c-in">
  <div class="c-brand"><?= logo() ?><span>Telesom LearnHub</span></div>
  <div class="c-title">Certificate</div>
  <div class="c-sub"><span><?= $isOrg ? 'of Training' : 'of Completion' ?></span></div>
  <div class="c-small">This certificate is proudly presented to</div>
  <div class="c-name"><?= e($nm) ?></div>
  <div class="c-small"><?= $isOrg ? 'for successfully completing the training programme' : 'for successfully completing the course' ?></div>
  <div class="c-course"><?= e($ce['title']) ?></div>
  <div class="c-score">with a final score of <b><?= (int)$ce['score'] ?>%</b></div>
  <?php if ($isOrg): ?><div class="c-partner">Issued in partnership with <b><?= e($ce['oname']) ?></b></div><?php endif; ?>
  <div class="c-foot">
   <div class="sig"><div class="sig-name"><?= e($isOrg ? ($ce['signatory'] ?: $ce['oname']) : $ce['inst']) ?></div><div class="sig-line"></div><small><?= $isOrg ? e(($ce['signatory_title'] ?: 'Training Department').' · '.$ce['oname']) : 'Course Instructor' ?></small><em>Issued <?= fdate($ce['issued_at']) ?><?= $isOrg ? ' · Instructor: '.e($ce['inst']) : '' ?></em></div>
   <div class="seal"><svg viewBox="0 0 130 160" aria-hidden="true"><path d="M38 96 20 156l28-14 14 16 8-58z" fill="#047857"/><path d="M92 96l18 60-28-14-14 16-8-58z" fill="#059669"/><g transform="translate(65 66)"><polygon points="0.0,-58.0 6.7,-50.6 15.0,-56.0 19.5,-47.1 29.0,-50.2 31.0,-40.5 41.0,-41.0 40.5,-31.0 50.2,-29.0 47.1,-19.5 56.0,-15.0 50.6,-6.7 58.0,0.0 50.6,6.7 56.0,15.0 47.1,19.5 50.2,29.0 40.5,31.0 41.0,41.0 31.0,40.5 29.0,50.2 19.5,47.1 15.0,56.0 6.7,50.6 0.0,58.0 -6.7,50.6 -15.0,56.0 -19.5,47.1 -29.0,50.2 -31.0,40.5 -41.0,41.0 -40.5,31.0 -50.2,29.0 -47.1,19.5 -56.0,15.0 -50.6,6.7 -58.0,0.0 -50.6,-6.7 -56.0,-15.0 -47.1,-19.5 -50.2,-29.0 -40.5,-31.0 -41.0,-41.0 -31.0,-40.5 -29.0,-50.2 -19.5,-47.1 -15.0,-56.0 -6.7,-50.6" fill="url(#gold)"/><circle r="43" fill="url(#gold)" stroke="#fff6cf" stroke-width="2"/><circle r="36" fill="none" stroke="#fff6cf" stroke-width="1.6" stroke-dasharray="1.5 3.2"/><circle r="30" fill="#064e3b"/><path d="M-12 1l8 9 17-19" fill="none" stroke="#f7e27c" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/><text y="-17" text-anchor="middle" font-size="6.5" font-weight="800" letter-spacing="1.5" fill="#f7e27c" font-family="Plus Jakarta Sans,Arial,sans-serif">TELESOM</text><text y="23" text-anchor="middle" font-size="6.5" font-weight="800" letter-spacing="1.5" fill="#f7e27c" font-family="Plus Jakarta Sans,Arial,sans-serif">VERIFIED</text></g></svg></div>
   <div class="c-qr"><div class="qrb" id="qrbox" data-url="<?= e($vurl) ?>" data-fg="<?= $isOrg ? '#0f2a4a' : '#064e3b' ?>"></div><small>Scan to verify</small><b><?= e($ce['code']) ?></b></div>
  </div>
 </div>
</div></div>
<script src="assets/js/qr.js"></script>
<script>(function(){var b=document.getElementById('qrbox');if(b&&window.QR){b.innerHTML=QR.svg(b.dataset.url,{fg:b.dataset.fg||'#064e3b',bg:'#fff',margin:0});}})();</script>
<p class="no-print cert-actions"><button class="btn" onclick="window.print()">🖨 Print / Save as PDF</button> <button class="btn btn-outline" type="button" onclick="navigator.clipboard&&navigator.clipboard.writeText('<?= e($vurl) ?>').then(function(){alert('Verification link copied')})">🔗 Copy verification link</button></p><?php endif;
if (!$u) echo '</div>'; include 'includes/footer.php'; ?>
