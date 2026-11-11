<?php
require 'includes/config.php';
$u = me(); $id = (int)($_GET['id'] ?? 0);
$c = one("SELECT c.*, u.name inst, u.bio ibio, k.name cat FROM courses c JOIN users u ON u.id=c.instructor_id LEFT JOIN categories k ON k.id=c.category_id WHERE c.id=?", [$id]);
if (!$c) { flash('Course not found.', 'error'); redirect('courses.php'); }
$mgr = $u && can_manage($c, $u);
if ($c['status'] !== 'approved' && !$mgr) { flash('This course is not available yet.', 'error'); redirect('courses.php'); }
if ($c['org_id'] && !$mgr && (!$u || org_of($u) != $c['org_id'])) { flash('This is a private course.', 'error'); redirect('courses.php'); }
$en = $u ? one("SELECT * FROM enrollments WHERE user_id=? AND course_id=?", [$u['id'], $id]) : null;
$active = $en && $en['status'] === 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$u) redirect('login.php');
    $a = $_POST['action'] ?? '';
    if ($a === 'enroll' && !$en && $u['role'] === 'learner') {
        if ($c['enroll_type'] === 'paid') redirect('checkout.php?course='.$id);
        if ($c['enroll_type'] === 'private') flash('This course is assigned by your organization.', 'error');
        else {
            $st = $c['enroll_type'] === 'approval' ? 'pending' : 'active';
            q("INSERT INTO enrollments(user_id,course_id,status,paid) VALUES(?,?,?,?)", [$u['id'], $id, $st, $c['enroll_type'] === 'paid' ? 1 : 0]);
            notify($c['instructor_id'], $u['name'].($st === 'pending' ? ' requested to join ' : ' enrolled in ').$c['title'], 'course_edit.php?id='.$id.'&tab=learners');
            flash($st === 'pending' ? 'Request sent. The instructor will review it.' : 'You are enrolled! Happy learning.');
            if ($st === 'active') redirect('learn.php?course='.$id);
        }
    } elseif ($a === 'review' && $active) {
        $r = max(1, min(5, (int)$_POST['rating']));
        q("INSERT INTO reviews(course_id,user_id,rating,body) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating), body=VALUES(body)", [$id, $u['id'], $r, trim($_POST['body'] ?? '')]);
        flash('Thanks for your review!');
    } elseif ($a === 'report') { q("UPDATE reviews SET reported=1 WHERE id=? AND course_id=?", [(int)$_POST['rid'], $id]); flash('Review reported to administrators.'); }
    elseif ($a === 'comment' && ($active || $mgr) && trim($_POST['body'] ?? '') !== '') {
        q("INSERT INTO comments(course_id,user_id,body) VALUES(?,?,?)", [$id, $u['id'], trim($_POST['body'])]);
        if ($u['id'] != $c['instructor_id']) notify($c['instructor_id'], $u['name'].' posted in '.$c['title'], 'course.php?id='.$id.'#disc');
        flash('Posted.');
    }
    redirect("course.php?id=$id");
}
$lessons = all("SELECT * FROM lessons WHERE course_id=? ORDER BY position,id", [$id]);
$nq = one("SELECT COUNT(*) c FROM quizzes WHERE course_id=?", [$id])['c']; $na = one("SELECT COUNT(*) c FROM assignments WHERE course_id=?", [$id])['c'];
$rv = all("SELECT r.*, u.name FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.course_id=? ORDER BY r.id DESC", [$id]);
$avg = $rv ? array_sum(array_column($rv, 'rating')) / count($rv) : 0; $mine_rv = $u ? one("SELECT * FROM reviews WHERE course_id=? AND user_id=?", [$id, $u['id']]) : null;
$cm = all("SELECT m.*, u.name, u.role FROM comments m JOIN users u ON u.id=m.user_id WHERE m.course_id=? ORDER BY m.id DESC LIMIT 30", [$id]);
$n = one("SELECT COUNT(*) c FROM enrollments WHERE course_id=? AND status='active'", [$id])['c'];
$layout = $u ? 'app' : 'public'; $title = $c['title']; include 'includes/header.php';
if (!$u): ?><div class="hero-sm"><div class="wrap"><h1><?= e($c['title']) ?></h1><p><?= e($c['cat']) ?> · <?= e($c['level']) ?></p></div></div><div class="wrap pubc"><?php endif; ?>
<div class="cols">
 <div>
  <div class="card"><h3>About this course <?php if ($c['status'] !== 'approved'): ?><span class="badge warn"><?= e($c['status']) ?></span><?php endif; ?></h3>
    <p style="color:var(--ink);white-space:pre-line"><?= e($c['description']) ?></p>
    <?php if ($c['objectives']): ?><h4 style="margin:18px 0 6px">What you'll learn</h4><ul class="checks"><?php foreach (explode('|', str_replace("\n", '|', $c['objectives'])) as $o) if (trim($o)) echo '<li>'.e(trim($o)).'</li>'; ?></ul><?php endif; ?>
    <?php if ($c['prerequisites']): ?><h4 style="margin:18px 0 6px">Prerequisites</h4><p><?= e($c['prerequisites']) ?></p><?php endif; ?>
  </div>
  <div class="card"><h3>Course content <small class="muted"><?= count($lessons) ?> lessons · <?= $nq ?> quizzes/exams · <?= $na ?> assignments</small></h3>
    <?php foreach ($lessons as $i => $l): ?><div style="padding:10px 0;border-bottom:1px solid var(--line)"><b><?= $i + 1 ?>.</b> <?= e($l['title']) ?> <span class="badge grey"><?= e($l['type']) ?></span></div><?php endforeach; if (!$lessons) echo '<p class="muted">No lessons yet.</p>'; ?>
  </div>
  <div class="card" id="reviews"><h3>Ratings & reviews <span class="star"><?= $avg ? stars($avg).' '.number_format($avg, 1) : '' ?></span></h3>
    <?php if ($active && $u['role'] === 'learner'): ?><form method="post" style="background:var(--g-soft);padding:16px;border-radius:12px;margin-bottom:14px"><?= csrf_field() ?><input type="hidden" name="action" value="review">
      <div class="fr"><div><label style="margin-top:0">Your rating</label><select name="rating"><?php for ($i = 5; $i >= 1; $i--) echo '<option value="'.$i.'" '.(($mine_rv['rating'] ?? 5) == $i ? 'selected' : '').'>'.str_repeat('★', $i).'</option>'; ?></select></div></div>
      <label>Feedback</label><textarea name="body" style="min-height:70px"><?= e($mine_rv['body'] ?? '') ?></textarea><div class="form-actions"><button class="btn btn-sm">Submit review</button></div></form><?php endif; ?>
    <?php foreach ($rv as $r): ?><div class="cmt"><span class="av"><?= e(initials($r['name'])) ?></span><div style="flex:1"><b><?= e($r['name']) ?></b> <span class="star"><?= stars($r['rating']) ?></span><p><?= e($r['body']) ?></p><small><?= fdate($r['created_at']) ?></small></div>
      <?php if ($u && $u['id'] != $r['user_id']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="report"><input type="hidden" name="rid" value="<?= $r['id'] ?>"><button class="btn btn-outline btn-sm" data-confirm="Report this review as inappropriate?">🚩</button></form><?php endif; ?></div><?php endforeach; if (!$rv) echo '<p class="muted">No reviews yet.</p>'; ?>
  </div>
  <?php if ($active || $mgr): ?><div class="card" id="disc"><h3>💬 Course discussion</h3>
    <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="comment"><input name="body" placeholder="Ask a question or share a comment…" required><button class="btn">Post</button></form>
    <?php foreach ($cm as $m): ?><div class="cmt"><span class="av"><?= e(initials($m['name'])) ?></span><div><b><?= e($m['name']) ?></b> <?= $m['role'] === 'instructor' ? '<span class="badge">Instructor</span>' : '' ?> <small><?= fdate($m['created_at'], true) ?></small><p><?= e($m['body']) ?></p></div></div><?php endforeach; ?></div><?php endif; ?>
 </div>
 <div>
  <div class="card"><h2 style="margin-bottom:6px"><?= $c['enroll_type'] === 'paid' ? '$'.number_format($c['price'], 2) : ($c['enroll_type'] === 'private' ? 'Private' : 'Free') ?></h2>
    <p style="margin-bottom:14px"><?= ['free' => 'Open enrollment', 'paid' => 'One-time payment', 'approval' => 'Instructor approval required', 'private' => 'Assigned by your organization'][$c['enroll_type']] ?></p>
    <?php if ($active): ?><a class="btn btn-block" href="learn.php?course=<?= $id ?>">Continue learning</a>
    <?php elseif ($en): ?><span class="badge warn"><?= $c['enroll_type'] === 'paid' ? 'Payment awaiting confirmation' : 'Enrollment pending approval' ?></span><?php if ($c['enroll_type'] === 'paid'): ?><p style="margin-top:8px"><a href="payments.php">View payment status →</a></p><?php endif; ?>
    <?php elseif ($mgr): ?><a class="btn btn-block" href="course_edit.php?id=<?= $id ?>">Manage course</a>
    <?php elseif (!$u): ?><a class="btn btn-block" href="login.php">Log in to enroll</a>
    <?php elseif ($u['role'] !== 'learner'): ?><p class="muted">Only learner accounts can enroll.</p>
    <?php elseif ($c['enroll_type'] === 'private'): ?><p class="muted">Your organization will assign this course to you.</p>
    <?php elseif ($c['enroll_type'] === 'paid'): ?><a class="btn btn-block" href="checkout.php?course=<?= $id ?>">💳 Pay & enroll</a><small class="muted" style="display:block;margin-top:8px">ZAAD · eDahab · Bank transfer</small>
    <?php else: ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="enroll"><button class="btn btn-block"><?= ['free' => 'Enroll for free', 'approval' => 'Request to join'][$c['enroll_type']] ?></button></form><?php endif; ?>
    <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">
    <p>📂 <b>Category:</b> <?= e($c['cat']) ?></p><p>📶 <b>Level:</b> <?= e($c['level']) ?></p><p>⏱ <b>Duration:</b> <?= e($c['duration']) ?></p><p>👥 <b>Learners:</b> <?= $n ?></p><p>🎓 <b>Certificate</b> on completion</p>
  </div>
  <div class="card"><h3>Instructor</h3><div class="cmt" style="border:0"><span class="av"><?= e(initials($c['inst'])) ?></span><div><b><?= e($c['inst']) ?></b><p><?= e($c['ibio']) ?></p></div></div></div>
 </div>
</div>
<?php if (!$u) echo '</div>'; include 'includes/footer.php'; ?>
