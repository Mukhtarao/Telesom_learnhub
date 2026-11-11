<?php
require 'includes/config.php';
$u = require_login(); $id = (int)($_GET['id'] ?? 0);
$a = one("SELECT a.*, c.title ct, c.instructor_id, c.org_id FROM assignments a JOIN courses c ON c.id=a.course_id WHERE a.id=?", [$id]);
if (!$a) redirect('dashboard.php');
$mgr = can_manage($a, $u);
if (!$mgr && org_course_blocked($a)) { flash('This course belongs to an organization whose subscription is not active. Please contact your organization.', 'error'); redirect('dashboard.php'); }
$en = one("SELECT id FROM enrollments WHERE user_id=? AND course_id=? AND status='active'", [$u['id'], $a['course_id']]);
if (!$en && !$mgr) { flash('Please enroll first.', 'error'); redirect('course.php?id='.$a['course_id']); }
$sub = one("SELECT * FROM submissions WHERE assignment_id=? AND user_id=?", [$id, $u['id']]);
$closed = $a['deadline'] && strtotime($a['deadline']) < time();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $en) {
    if ($closed) flash('The deadline has passed.', 'error');
    elseif ($sub && $sub['marks'] !== null) flash('Already graded; cannot resubmit.', 'error');
    else {
        $f = upload('file'); $body = trim($_POST['body'] ?? '');
        if (!$body && !$f && !$sub) flash('Write an answer or upload a file.', 'error');
        else {
            q("INSERT INTO submissions(assignment_id,user_id,body,file) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE body=VALUES(body), file=COALESCE(VALUES(file),file), submitted_at=NOW()", [$id, $u['id'], $body, $f]);
            notify($a['instructor_id'], $u['name'].' submitted "'.$a['title'].'"', 'grading.php');
            flash('Assignment submitted.'); redirect("assignment.php?id=$id");
        }
    }
}
$title = $a['title']; include 'includes/header.php';
?>
<div class="cols"><div>
 <div class="card"><h3><?= e($a['title']) ?> <span class="badge"><?= $a['max_marks'] ?> marks</span></h3>
  <p><b>Course:</b> <?= e($a['ct']) ?> · <b>Deadline:</b> <?= $a['deadline'] ? fdate($a['deadline'], true) : 'None' ?> <?= $closed ? '<span class="badge bad">Closed</span>' : '' ?></p>
  <p style="color:var(--ink);white-space:pre-line;margin-top:12px"><?= e($a['instructions']) ?></p>
  <?php if ($a['file']): ?><p style="margin-top:10px"><a class="btn btn-sm btn-outline" href="<?= e($a['file']) ?>" target="_blank">⬇ Instruction file</a></p><?php endif; ?></div>
 <?php if ($en): ?><div class="card"><h3>Your submission</h3>
  <form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
   <label>Your answer</label><textarea name="body"><?= e($sub['body'] ?? '') ?></textarea>
   <label>Upload file (optional)</label><input type="file" name="file">
   <?php if (!empty($sub['file'])): ?><p><a href="<?= e($sub['file']) ?>" target="_blank">📎 Current file</a></p><?php endif; ?>
   <div class="form-actions"><button class="btn" <?= $closed || ($sub && $sub['marks'] !== null) ? 'disabled' : '' ?>><?= $sub ? 'Update submission' : 'Submit assignment' ?></button><a class="btn btn-outline" href="learn.php?course=<?= $a['course_id'] ?>">Back</a></div></form></div><?php endif; ?>
</div><div>
 <?php if ($sub): ?><div class="card"><h3>Status</h3><p>Submitted: <?= fdate($sub['submitted_at'], true) ?></p>
  <?php if ($sub['marks'] !== null): ?><h2 style="color:var(--g);margin:10px 0"><?= $sub['marks'] ?> / <?= $a['max_marks'] ?></h2><p><b>Feedback:</b> <?= e($sub['feedback'] ?: '—') ?></p><?php else: ?><span class="badge warn">Awaiting grading</span><?php endif; ?></div><?php endif; ?>
</div></div>
<?php include 'includes/footer.php'; ?>
