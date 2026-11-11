<?php
require 'includes/config.php';
$u = require_login(); $id = (int)($_GET['id'] ?? 0);
$z = one("SELECT z.*, c.title ct, c.instructor_id, c.org_id FROM quizzes z JOIN courses c ON c.id=z.course_id WHERE z.id=?", [$id]);
if (!$z) redirect('dashboard.php');
$mgr = can_manage($z, $u);
if (!$mgr && org_course_blocked($z)) { flash('This course belongs to an organization whose subscription is not active. Please contact your organization.', 'error'); redirect('dashboard.php'); }
$en = one("SELECT id FROM enrollments WHERE user_id=? AND course_id=? AND status='active'", [$u['id'], $z['course_id']]);
if (!$en && !$mgr) { flash('Please enroll first.', 'error'); redirect('course.php?id='.$z['course_id']); }
$qs = all("SELECT * FROM questions WHERE quiz_id=? ORDER BY id", [$id]);
$total = array_sum(array_column($qs, 'marks'));
$used = (int)one("SELECT COUNT(*) c FROM attempts WHERE quiz_id=? AND user_id=?", [$id, $u['id']])['c'];
$left = $z['max_attempts'] ? $z['max_attempts'] - $used : 99;
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $en && $qs && $left > 0) {
    $score = 0; $rev = [];
    $late = $z['time_limit'] && time() - ($_SESSION['qs'.$id] ?? 0) > $z['time_limit'] * 60 + 45;
    foreach ($qs as $q) {
        $a = trim($_POST['q'.$q['id']] ?? '');
        if ($q['type'] === 'short') { $ok = in_array(strtolower($a), array_map(fn($x) => strtolower(trim($x)), explode('|', $q['answer']))); }
        else $ok = $a === $q['answer'];
        if ($ok && !$late) $score += $q['marks'];
        $rev[$q['id']] = [$a, $ok];
    }
    $pct = $total ? round($score * 100 / $total) : 0; $pass = $pct >= $z['pass_marks'] ? 1 : 0;
    q("INSERT INTO attempts(quiz_id,user_id,score,total,passed) VALUES(?,?,?,?,?)", [$id, $u['id'], $score, $total, $pass]);
    if ($pass) check_completion($u['id'], $z['course_id']);
    $result = compact('score', 'pct', 'pass', 'rev', 'late'); $used++; $left = $z['max_attempts'] ? $z['max_attempts'] - $used : 99;
    unset($_SESSION['qs'.$id]);
}
$hist = all("SELECT * FROM attempts WHERE quiz_id=? AND user_id=? ORDER BY id DESC", [$id, $u['id']]);
$taking = !$result && $left > 0 && $qs && ($_GET['start'] ?? '') && $en;
if ($taking) $_SESSION['qs'.$id] = time();
$title = $z['title']; include 'includes/header.php';
?>
<?php if ($result): ?>
<div class="card" style="text-align:center"><div style="font-size:56px"><?= $result['pass'] ? '🎉' : '📘' ?></div>
  <h2><?= $result['pass'] ? 'You passed!' : 'Not passed yet' ?></h2><p style="font-size:18px"><b><?= $result['score'] ?>/<?= $total ?></b> (<?= $result['pct'] ?>%) — pass mark <?= $z['pass_marks'] ?>%</p>
  <?php if ($result['late']): ?><p class="flash error">Time limit exceeded — answers were not scored.</p><?php endif; ?>
  <div class="form-actions" style="justify-content:center"><a class="btn" href="learn.php?course=<?= $z['course_id'] ?>">Back to course</a><?php if (!$result['pass'] && $left > 0): ?><a class="btn btn-outline" href="quiz.php?id=<?= $id ?>&start=1">Try again</a><?php endif; ?></div></div>
<div class="card"><h3>Review</h3><?php foreach ($qs as $q): [$a, $ok] = $result['rev'][$q['id']]; ?>
  <div class="q <?= $ok ? 'ok' : 'no' ?>"><p class="t"><?= $ok ? '✅' : '❌' ?> <?= e($q['question']) ?></p>
  <p>Your answer: <b><?= e($q['type'] === 'mcq' && $a ? strtoupper($a).'. '.$q['opt_'.$a] : ($a ?: '—')) ?></b></p>
  <?php if (!$ok): ?><p>Correct answer: <b><?= e($q['type'] === 'mcq' ? strtoupper($q['answer']).'. '.$q['opt_'.$q['answer']] : explode('|', $q['answer'])[0]) ?></b></p><?php endif; ?></div><?php endforeach; ?></div>
<?php elseif ($taking): ?>
<form method="post" id="quizform"><?= csrf_field() ?>
  <?php if ($z['time_limit']): ?><div class="timer" id="timer" data-seconds="<?= $z['time_limit'] * 60 ?>">⏱</div><?php endif; ?>
  <div class="card"><h3><?= e($z['title']) ?> <span class="badge"><?= $z['type'] === 'exam' ? 'Examination' : 'Quiz' ?></span></h3>
  <?php foreach ($qs as $i => $q): ?><div class="q"><p class="t"><?= $i + 1 ?>. <?= e($q['question']) ?> <small class="muted">(<?= $q['marks'] ?> mark<?= $q['marks'] > 1 ? 's' : '' ?>)</small></p>
    <?php if ($q['type'] === 'mcq'): foreach (['a', 'b', 'c', 'd'] as $o) { if ($q['opt_'.$o] !== null && $q['opt_'.$o] !== '') { ?><label class="opt"><input type="radio" name="q<?= $q['id'] ?>" value="<?= $o ?>"> <?= e($q['opt_'.$o]) ?></label><?php } }
    elseif ($q['type'] === 'tf'): ?><label class="opt"><input type="radio" name="q<?= $q['id'] ?>" value="true"> True</label><label class="opt"><input type="radio" name="q<?= $q['id'] ?>" value="false"> False</label>
    <?php else: ?><input name="q<?= $q['id'] ?>" placeholder="Type your answer"><?php endif; ?></div><?php endforeach; ?>
  <button class="btn" data-confirm="Submit your answers?">Submit answers</button></div>
</form>
<?php else: ?>
<div class="card"><h3><?= e($z['title']) ?> <span class="badge"><?= $z['type'] === 'exam' ? 'Examination' : 'Quiz' ?></span></h3>
  <p>Course: <b><?= e($z['ct']) ?></b></p>
  <p>📋 <?= count($qs) ?> questions · <?= $total ?> marks · pass mark <?= $z['pass_marks'] ?>%</p>
  <p>⏱ <?= $z['time_limit'] ? $z['time_limit'].' minutes' : 'No time limit' ?> · 🔁 <?= $z['max_attempts'] ? $left.' of '.$z['max_attempts'].' attempts left' : 'Unlimited attempts' ?></p>
  <div class="form-actions"><?php if ($left > 0 && $qs && $en): ?><a class="btn" href="quiz.php?id=<?= $id ?>&start=1">Start <?= $z['type'] === 'exam' ? 'examination' : 'quiz' ?></a><?php elseif (!$qs): ?><span class="muted">No questions yet.</span><?php else: ?><span class="badge bad">No attempts left</span><?php endif; ?><a class="btn btn-outline" href="learn.php?course=<?= $z['course_id'] ?>">Back</a></div></div>
<?php if ($hist): ?><div class="card"><h3>Your attempts</h3><div class="tw"><table><tr><th>Date</th><th>Score</th><th>Result</th></tr><?php foreach ($hist as $h): ?><tr><td><?= fdate($h['created_at'], true) ?></td><td><?= $h['score'] ?>/<?= $h['total'] ?></td><td><span class="badge <?= $h['passed'] ? '' : 'bad' ?>"><?= $h['passed'] ? 'Passed' : 'Failed' ?></span></td></tr><?php endforeach; ?></table></div></div><?php endif; ?>
<?php endif; include 'includes/footer.php'; ?>
