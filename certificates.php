<?php
require 'includes/config.php';
$u = require_login();
$rows = all("SELECT ce.*, c.title FROM certificates ce JOIN courses c ON c.id=ce.course_id WHERE ce.user_id=? ORDER BY ce.issued_at DESC", [$u['id']]);
$title = 'My Certificates'; include 'includes/header.php';
if (!$rows) echo '<div class="card empty">🎓 No certificates yet. Complete a course (all lessons and quizzes/exams) to earn one.<br><br><a class="btn" href="mycourses.php">Go to my courses</a></div>'; ?>
<div class="grid"><?php foreach ($rows as $r): ?><div class="card" style="border-top:5px solid var(--g)"><div style="font-size:34px">🎓</div><h3><?= e($r['title']) ?></h3>
<p>Issued <?= fdate($r['issued_at']) ?> · Score <?= $r['score'] ?>%</p><p>Code: <b><?= e($r['code']) ?></b></p><a class="btn btn-sm" href="verify.php?code=<?= e($r['code']) ?>">View / Print</a></div><?php endforeach; ?></div>
<?php include 'includes/footer.php'; ?>
