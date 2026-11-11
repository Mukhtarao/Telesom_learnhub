<?php
require 'includes/config.php';
$u = require_login(['instructor', 'organization', 'admin']);
$scope = $u['role'] === 'admin' ? '1=1' : ($u['role'] === 'organization' ? 'c.org_id='.(int)$u['id'] : 'c.instructor_id='.(int)$u['id']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s = one("SELECT s.*, a.max_marks, a.title FROM submissions s JOIN assignments a ON a.id=s.assignment_id JOIN courses c ON c.id=a.course_id WHERE s.id=? AND $scope", [(int)$_POST['sid']]);
    if ($s) {
        $m = max(0, min($s['max_marks'], (int)$_POST['marks']));
        q("UPDATE submissions SET marks=?, feedback=? WHERE id=?", [$m, trim($_POST['feedback'] ?? ''), $s['id']]);
        notify($s['user_id'], 'Your assignment "'.$s['title'].'" was graded: '.$m.'/'.$s['max_marks'], 'assignment.php?id='.$s['assignment_id']);
        flash('Grade saved.');
    }
    redirect('grading.php');
}
$rows = all("SELECT s.*, a.title atitle, a.max_marks, c.title ct, u.name FROM submissions s JOIN assignments a ON a.id=s.assignment_id JOIN courses c ON c.id=a.course_id JOIN users u ON u.id=s.user_id WHERE $scope ORDER BY (s.marks IS NULL) DESC, s.submitted_at DESC");
$title = 'Grading'; include 'includes/header.php';
?>
<?php if (!$rows) echo '<div class="card empty">No submissions yet.</div>'; foreach ($rows as $r): ?>
<div class="card"><h3><span><?= e($r['name']) ?> <small class="muted">— <?= e($r['atitle']) ?> · <?= e($r['ct']) ?></small></span><span class="badge <?= $r['marks'] === null ? 'warn' : '' ?>"><?= $r['marks'] === null ? 'Needs grading' : 'Graded' ?></span></h3>
  <p style="color:var(--ink);white-space:pre-line"><?= e($r['body']) ?></p><?php if ($r['file']): ?><p><a href="<?= e($r['file']) ?>" target="_blank">📎 Download submitted file</a></p><?php endif; ?>
  <small class="muted">Submitted <?= fdate($r['submitted_at'], true) ?></small>
  <form method="post" class="inline-form" style="margin-top:12px"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= $r['id'] ?>">
    <div style="max-width:140px"><label style="margin-top:0">Marks (/<?= $r['max_marks'] ?>)</label><input type="number" name="marks" min="0" max="<?= $r['max_marks'] ?>" value="<?= e($r['marks']) ?>" required></div>
    <div style="flex:3"><label style="margin-top:0">Feedback</label><input name="feedback" value="<?= e($r['feedback']) ?>"></div><button class="btn">Save grade</button></form></div>
<?php endforeach; include 'includes/footer.php'; ?>
