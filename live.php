<?php
require 'includes/config.php';
$u = require_login();
[$cond, $p] = live_scope($u);
$rows = all("SELECT l.*, c.title ct, c.id cid, c.instructor_id, c.org_id, i.name inst,
  (SELECT COUNT(*) FROM live_attendance WHERE live_id=l.id) att,
  (SELECT COUNT(*) FROM live_attendance WHERE live_id=l.id AND user_id=?) went
  FROM live_classes l JOIN courses c ON c.id=l.course_id JOIN users i ON i.id=c.instructor_id WHERE $cond ORDER BY l.start_at", array_merge([$u['id']], $p));
$live = []; $up = []; $past = [];
foreach ($rows as $r) { $r['st'] = live_state($r); if ($r['st'] === 'live') $live[] = $r; elseif ($r['st'] === 'upcoming') $up[] = $r; else $past[] = $r; }
$past = array_reverse($past);
$prov = ['jitsi' => 'Jitsi Meet', 'zoom' => 'Zoom', 'meet' => 'Google Meet', 'teams' => 'Microsoft Teams', 'other' => 'Online room'];

function lv_card($r, $u, $prov){
    $s = strtotime($r['start_at']); $e = $s + $r['duration_min'] * 60; $st = $r['st']; $mgr = can_manage($r, $u);
    $join = $st === 'live' || ($st === 'upcoming' && ($mgr || live_can_join($r)));
    ?>
<div class="lv-card <?= $st ?>">
  <div class="lv-date"><b><?= date('d', $s) ?></b><span><?= date('M', $s) ?></span><small><?= date('H:i', $s) ?></small></div>
  <div class="lv-body">
    <h3><?= e($r['title']) ?>
      <?php if ($st === 'live'): ?><span class="badge bad"><i class="dot"></i> LIVE NOW</span>
      <?php elseif ($st === 'upcoming'): ?><span class="badge">Upcoming</span>
      <?php elseif ($st === 'cancelled'): ?><span class="badge bad">Cancelled</span>
      <?php else: ?><span class="badge grey">Ended</span><?php endif; ?></h3>
    <div class="lv-meta"><span>📚 <?= e($r['ct']) ?></span><span>👨‍🏫 <?= e($r['inst']) ?></span><span>🕒 <?= date('D d M · H:i', $s) ?> – <?= date('H:i', $e) ?></span><span>🎥 <?= e($prov[$r['provider']] ?? 'Online') ?></span></div>
    <?php if ($r['description']): ?><p><?= e($r['description']) ?></p><?php endif; ?>
    <?php if ($st === 'upcoming' || $st === 'live'): ?><div class="lv-count" data-start="<?= $s ?>" data-end="<?= $e ?>" data-now="<?= time() ?>" data-state="<?= $st ?>" data-joinable="<?= $join ? 1 : 0 ?>"></div><?php endif; ?>
  </div>
  <div class="lv-act">
    <?php if ($join): ?><a class="btn" href="live_join.php?id=<?= (int)$r['id'] ?>" target="_blank" rel="noopener"><?= $st === 'live' ? '🔴 Join now' : '🚪 Enter room' ?></a>
    <?php elseif ($st === 'upcoming'): ?><button class="btn" disabled>Opens 15 min before</button><?php endif; ?>
    <?php if ($st === 'upcoming' || $st === 'live'): ?><a class="btn btn-sm btn-outline" href="live_ics.php?id=<?= (int)$r['id'] ?>">📅 Add to calendar</a><?php endif; ?>
    <?php if ($st === 'ended' && $r['recording_url']): ?><a class="btn btn-sm" href="<?= e($r['recording_url']) ?>" target="_blank" rel="noopener">▶ Watch recording</a><?php endif; ?>
    <?php if ($u['role'] === 'learner' && $st === 'ended'): ?><span class="badge <?= $r['went'] ? '' : 'warn' ?>"><?= $r['went'] ? '✅ You attended' : 'Not attended' ?></span><?php endif; ?>
    <?php if ($mgr): ?><a class="btn btn-sm btn-outline" href="course_edit.php?id=<?= (int)$r['cid'] ?>&tab=live">⚙ Manage · 👥 <?= (int)$r['att'] ?></a><?php endif; ?>
  </div>
</div>
<?php }

$title = 'Live Classes'; include 'includes/header.php';
?>
<div class="hdr-row"><p>Join scheduled online classes with your instructors. Times are shown in <b><?= date('T (P)') ?></b>.</p>
<?php if (in_array($u['role'], ['instructor', 'organization'])): ?><a class="btn" href="instructor.php">+ Schedule a class (open a course → Live classes)</a><?php endif; ?></div>

<?php if ($live): ?><div class="lv-sec"><h3>🔴 Live now</h3></div><?php foreach ($live as $r) lv_card($r, $u, $prov); endif; ?>
<div class="lv-sec"><h3>📅 Upcoming</h3><span class="badge grey"><?= count($up) ?></span></div>
<?php foreach ($up as $r) lv_card($r, $u, $prov); if (!$up) echo '<div class="card empty">No upcoming live classes.<br><small>'.($u['role'] === 'learner' ? 'When your instructors schedule one for a course you are enrolled in, it will appear here and you will get a notification.' : 'Open one of your courses → <b>Live classes</b> to schedule one.').'</small></div>'; ?>
<?php if ($past): ?><div class="lv-sec"><h3>🕘 Past classes & recordings</h3><span class="badge grey"><?= count($past) ?></span></div><?php foreach ($past as $r) lv_card($r, $u, $prov); endif; ?>
<?php include 'includes/footer.php'; ?>
