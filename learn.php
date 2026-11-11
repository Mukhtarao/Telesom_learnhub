<?php
require 'includes/config.php';
$u = require_login(); $cid = (int)($_GET['course'] ?? 0);
$c = one("SELECT c.*, i.name inst FROM courses c JOIN users i ON i.id=c.instructor_id WHERE c.id=?", [$cid]);
if (!$c) redirect('courses.php');
$mgr = can_manage($c, $u);
if (!$mgr && org_course_blocked($c)) { flash('This course belongs to an organization whose subscription is not active. Please contact your organization.', 'error'); redirect('dashboard.php'); }
$en = one("SELECT * FROM enrollments WHERE user_id=? AND course_id=? AND status='active'", [$u['id'], $cid]);
if (!$en && !$mgr) { flash('Please enroll first.', 'error'); redirect("course.php?id=$cid"); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'comment' && trim($_POST['body'] ?? '') !== '') {
    q("INSERT INTO comments(course_id,user_id,body) VALUES(?,?,?)", [$cid, $u['id'], trim($_POST['body'])]);
    if ($u['id'] != $c['instructor_id']) notify($c['instructor_id'], $u['name'].' asked a question in '.$c['title'], 'course.php?id='.$cid.'#disc');
    flash('Posted to the class discussion.'); redirect("learn.php?course=$cid&lesson=".(int)($_GET['lesson'] ?? 0).'&tab=qa');
}
$lessons = all("SELECT * FROM lessons WHERE course_id=? ORDER BY position,id", [$cid]);
$done = array_map('intval', array_column(all("SELECT p.lesson_id FROM progress p JOIN lessons l ON l.id=p.lesson_id WHERE p.user_id=? AND l.course_id=?", [$u['id'], $cid]), 'lesson_id'));
$lid = (int)($_GET['lesson'] ?? 0); $les = null; $idx = 0;
foreach ($lessons as $i => $row) { if ((int)$row['id'] === $lid) { $les = $row; $idx = $i; } }
if (!$les) foreach ($lessons as $i => $row) { if (!in_array((int)$row['id'], $done, true)) { $les = $row; $idx = $i; break; } }
if (!$les && $lessons) { $les = $lessons[0]; $idx = 0; }
$gated = $les && in_array($les['type'], ['video', 'audio']) && ($les['file'] || $les['url']);
$isDone = $les && in_array((int)$les['id'], $done, true); $locked = $gated && $en && !$isDone;
if ($gated) $_SESSION['lstart'][(int)$les['id']] = time();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'complete' && $les && $en) {
    if ($gated && !$isDone && empty($_SESSION['watched'][(int)$les['id']])) { flash('Please watch the whole '.$les['type'].' before completing this lesson.', 'error'); redirect("learn.php?course=$cid&lesson=".(int)$les['id']); }
    unset($_SESSION['watched'][(int)$les['id']]);
    q("INSERT IGNORE INTO progress(user_id,lesson_id) VALUES(?,?)", [$u['id'], $les['id']]);
    check_completion($u['id'], $cid);
    $nx = $lessons[$idx + 1] ?? null; flash('Lesson completed ✔');
    redirect("learn.php?course=$cid&lesson=".($nx ? $nx['id'] : $les['id']));
}
$quizzes = all("SELECT z.*, (SELECT MAX(passed) FROM attempts WHERE quiz_id=z.id AND user_id=?) ok FROM quizzes z WHERE course_id=? ORDER BY z.type, z.id", [$u['id'], $cid]);
$assn = all("SELECT a.*, (SELECT id FROM submissions WHERE assignment_id=a.id AND user_id=?) sub FROM assignments a WHERE course_id=?", [$u['id'], $cid]);
$pg = progress($u['id'], $cid);
$cert = one("SELECT code FROM certificates WHERE user_id=? AND course_id=?", [$u['id'], $cid]);
$cm = all("SELECT m.*, u.name, u.role FROM comments m JOIN users u ON u.id=m.user_id WHERE m.course_id=? ORDER BY m.id DESC LIMIT 25", [$cid]);
$icon = ['text' => '📄', 'video' => '🎬', 'audio' => '🎧', 'pdf' => '📕', 'document' => '📎', 'image' => '🖼️', 'link' => '🔗'];
$tab = in_array($_GET['tab'] ?? '', ['overview', 'materials', 'qa']) ? $_GET['tab'] : 'overview';
$nDone = count($done); $nTot = count($lessons);
$title = $c['title']; include 'includes/header.php';
$base = "learn.php?course=$cid";
?>
<div class="room-head">
  <div><a class="muted" href="mycourses.php">← My courses</a><h2><?= e($c['title']) ?></h2><small class="muted">👤 <?= e($c['inst']) ?> · <?= $nDone ?>/<?= $nTot ?> lessons completed</small></div>
  <div class="ring" style="--p:<?= $pg ?>"><b><?= $pg ?>%</b></div>
</div>
<?php if ($cert): ?><div class="flash">🎉 Course completed! <a href="verify.php?code=<?= e($cert['code']) ?>"><b>View your certificate</b></a></div><?php endif; ?>

<div class="room">
 <div class="stage">
  <?php if (!$les): ?>
    <div class="card empty">📭 This course has no lessons yet.<?php if ($mgr): ?><br><br><a class="btn" href="course_edit.php?id=<?= $cid ?>&tab=lessons">Add lessons</a><?php endif; ?></div>
  <?php else: ?>
    <?php if (in_array($les['type'], ['video', 'audio', 'pdf', 'image'])): echo render_media($les, true); endif; ?>
    <div class="card now">
      <div class="now-top"><div><span class="badge"><?= $icon[$les['type']] ?? '📄' ?> <?= e(ucfirst($les['type'])) ?> lesson <?= $idx + 1 ?> of <?= $nTot ?></span><h2><?= e($les['title']) ?></h2></div>
      <form method="post" action="<?= $base ?>&lesson=<?= (int)$les['id'] ?>"><?= csrf_field() ?><input type="hidden" name="action" value="complete">
        <div class="nav-btns">
          <?php if ($idx > 0): ?><a class="btn btn-outline" href="<?= $base ?>&lesson=<?= (int)$lessons[$idx - 1]['id'] ?>">← Previous</a><?php endif; ?>
          <?php if ($en): ?><button class="btn" id="completeBtn" <?= $locked ? 'disabled' : '' ?>><?= $isDone ? 'Next →' : ($locked ? '🔒 Finish the '.e($les['type']).' first' : 'Complete & continue ✔') ?></button><?php endif; ?>
        </div></form></div>
      <?php if ($gated && $en): ?><div class="gate <?= $isDone ? 'ok' : '' ?>" id="gate"><div class="gbar"><i style="width:<?= $isDone ? 100 : 0 ?>%"></i></div><span><?= $isDone ? '✅ You already completed this lesson.' : '🔒 Watch the whole '.e($les['type']).' to unlock “Complete & continue” — skipping ahead is disabled.' ?></span></div><?php endif; ?>
      <div class="tabs" style="margin-top:18px">
        <?php foreach (['overview' => '📖 Lesson notes', 'materials' => '📚 Materials', 'qa' => '💬 Class discussion'] as $k => $lbl): ?><a class="<?= $tab === $k ? 'on' : '' ?>" href="<?= $base ?>&lesson=<?= (int)$les['id'] ?>&tab=<?= $k ?>"><?= $lbl ?></a><?php endforeach; ?>
      </div>

      <?php if ($tab === 'overview'): ?>
        <?php if (in_array($les['type'], ['document', 'link']) && ($les['file'] || $les['url'])) echo render_media($les); ?>
        <?php if (trim((string)$les['content']) !== ''): ?><div class="txt"><?= e($les['content']) ?></div><?php elseif (!in_array($les['type'], ['video', 'audio', 'pdf', 'image', 'document', 'link'])): ?><p class="muted">No written notes for this lesson.</p><?php endif; ?>
        <?php if ($c['objectives']): ?><h4 style="margin:22px 0 6px">Course objectives</h4><ul class="checks"><?php foreach (explode('|', str_replace("\n", '|', $c['objectives'])) as $o) if (trim($o)) echo '<li>'.e(trim($o)).'</li>'; ?></ul><?php endif; ?>

      <?php elseif ($tab === 'materials'):
        $vids = []; $docs = []; $lnks = [];
        foreach ($lessons as $i => $l) { if (!($l['file'] || $l['url'])) continue;
          if ($l['type'] === 'video') $vids[] = [$i, $l]; elseif ($l['type'] === 'link') $lnks[] = [$i, $l]; elseif (in_array($l['type'], ['pdf', 'document', 'audio', 'image'])) $docs[] = [$i, $l]; }
        $afs = array_values(array_filter($assn, fn($a) => $a['file']));
        if (!$vids && !$docs && !$lnks && !$afs): ?><div class="empty">📂 No class materials yet.<br><small>Videos, documents, links and assignment files added by your instructor will appear here.</small></div><?php endif; ?>
        <?php if ($vids): ?><div class="mhead"><h4>🎬 Video lessons</h4><span class="badge grey"><?= count($vids) ?></span></div><div class="mgrid">
          <?php foreach ($vids as [$i, $l]): $d = in_array((int)$l['id'], $done, true); ?><div class="mcard"><div class="mi vid">🎬</div><div class="mb"><b><?= e($l['title']) ?></b><small>Lesson <?= $i + 1 ?> · <?= $d ? '✅ Completed' : 'Not finished yet' ?></small></div><div class="ma"><a class="btn btn-sm" href="<?= $base ?>&lesson=<?= (int)$l['id'] ?>">▶ <?= $d ? 'Replay' : 'Watch' ?></a></div></div><?php endforeach; ?></div><?php endif; ?>
        <?php if ($docs): ?><div class="mhead"><h4>📚 Documents & files</h4><span class="badge grey"><?= count($docs) ?></span></div><div class="mgrid">
          <?php foreach ($docs as [$i, $l]): $src = $l['file'] ?: $l['url']; $ext = strtoupper(pathinfo(parse_url($src, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)); $sz = $l['file'] ? fsize($l['file']) : ''; ?>
          <div class="mcard"><div class="mi doc"><?= $icon[$l['type']] ?></div><div class="mb"><b><?= e($l['title']) ?></b><small><?= e(ucfirst($l['type'])) ?><?= $ext ? ' · '.e($ext) : '' ?><?= $sz ? ' · '.$sz : '' ?></small></div>
            <div class="ma"><a class="btn btn-sm btn-outline" href="<?= e($src) ?>" target="_blank" rel="noopener">Open</a><?php if ($l['file']): ?><a class="btn btn-sm" href="<?= e($src) ?>" download>⬇ Download</a><?php endif; ?></div></div><?php endforeach; ?></div><?php endif; ?>
        <?php if ($lnks): ?><div class="mhead"><h4>🔗 Useful links</h4><span class="badge grey"><?= count($lnks) ?></span></div><div class="mgrid">
          <?php foreach ($lnks as [$i, $l]): $src = $l['file'] ?: $l['url']; ?><div class="mcard"><div class="mi lnk">🔗</div><div class="mb"><b><?= e($l['title']) ?></b><small><?= e(parse_url($src, PHP_URL_HOST) ?: 'External link') ?></small></div><div class="ma"><a class="btn btn-sm" href="<?= e($src) ?>" target="_blank" rel="noopener">Open ↗</a></div></div><?php endforeach; ?></div><?php endif; ?>
        <?php if ($afs): ?><div class="mhead"><h4>📝 Assignment files</h4><span class="badge grey"><?= count($afs) ?></span></div><div class="mgrid">
          <?php foreach ($afs as $a): ?><div class="mcard"><div class="mi asg">📝</div><div class="mb"><b><?= e($a['title']) ?></b><small>Instructions<?= fsize($a['file']) ? ' · '.fsize($a['file']) : '' ?><?= $a['deadline'] ? ' · due '.fdate($a['deadline']) : '' ?></small></div><div class="ma"><a class="btn btn-sm btn-outline" href="assignment.php?id=<?= (int)$a['id'] ?>">Open task</a><a class="btn btn-sm" href="<?= e($a['file']) ?>" download>⬇</a></div></div><?php endforeach; ?></div><?php endif; ?>

      <?php else: ?>
        <form method="post" action="<?= $base ?>&lesson=<?= (int)$les['id'] ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="comment"><input name="body" placeholder="Ask your instructor or classmates a question…" required><button class="btn">Post</button></form>
        <?php foreach ($cm as $m): ?><div class="cmt"><span class="av"><?= e(initials($m['name'])) ?></span><div><b><?= e($m['name']) ?></b> <?= $m['role'] === 'instructor' ? '<span class="badge">Instructor</span>' : '' ?> <small class="muted"><?= fdate($m['created_at'], true) ?></small><p style="color:var(--ink)"><?= e($m['body']) ?></p></div></div><?php endforeach; ?>
        <?php if (!$cm) echo '<p class="muted" style="margin-top:14px">No questions yet — be the first to ask!</p>'; ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>
 </div>

 <aside class="playlist">
  <div class="card">
    <div class="bar"><i style="width:<?= $pg ?>%"></i></div><small class="muted"><?= $pg ?>% complete</small>
    <h3 style="margin:14px 0 8px">Course content</h3>
    <?php foreach ($lessons as $i => $l): $d = in_array((int)$l['id'], $done, true); $on = $les && (int)$l['id'] === (int)$les['id']; ?>
      <a class="pl <?= $on ? 'on' : '' ?>" href="<?= $base ?>&lesson=<?= (int)$l['id'] ?>"><span class="ck <?= $d ? 'd' : '' ?>"><?= $d ? '✓' : $i + 1 ?></span><span class="pt"><b><?= e($l['title']) ?></b><small><?= $icon[$l['type']] ?? '📄' ?> <?= e(ucfirst($l['type'])) ?></small></span></a>
    <?php endforeach; if (!$lessons) echo '<p class="muted">No lessons yet.</p>'; ?>
    <?php if ($quizzes || $assn): ?><h3 style="margin:18px 0 8px">Assessments</h3>
      <?php foreach ($quizzes as $z): ?><a class="pl" href="quiz.php?id=<?= (int)$z['id'] ?>"><span class="ck <?= $z['ok'] ? 'd' : '' ?>"><?= $z['ok'] ? '✓' : ($z['type'] === 'exam' ? '🎯' : '🧠') ?></span><span class="pt"><b><?= e($z['title']) ?></b><small><?= $z['type'] === 'exam' ? 'Final exam' : 'Quiz' ?></small></span></a><?php endforeach; ?>
      <?php foreach ($assn as $a): ?><a class="pl" href="assignment.php?id=<?= (int)$a['id'] ?>"><span class="ck <?= $a['sub'] ? 'd' : '' ?>"><?= $a['sub'] ? '✓' : '📝' ?></span><span class="pt"><b><?= e($a['title']) ?></b><small>Assignment<?= $a['deadline'] ? ' · due '.fdate($a['deadline']) : '' ?></small></span></a><?php endforeach; ?>
    <?php endif; ?>
  </div>
 </aside>
</div>
<?php include 'includes/footer.php'; ?>
