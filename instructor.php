<?php
require 'includes/config.php';
$u = require_login(['instructor', 'organization', 'admin']);
$sc = $u['role'] === 'admin' ? '1=1' : ($u['role'] === 'organization' ? 'c.org_id='.(int)$u['id'] : 'c.instructor_id='.(int)$u['id']);
$cs = all("SELECT c.*, k.name cat, (SELECT COUNT(*) FROM enrollments WHERE course_id=c.id AND status='active') n, (SELECT COUNT(*) FROM lessons WHERE course_id=c.id) ln FROM courses c LEFT JOIN categories k ON k.id=c.category_id WHERE $sc ORDER BY c.id DESC");
$title = $u['role'] === 'organization' ? 'Private Courses' : 'My Courses'; include 'includes/header.php';
?>
<div class="hdr-row"><p>Create and manage your courses, lessons, assessments and learners.</p><a class="btn" href="course_edit.php?new=1">+ New course</a></div>
<div class="card"><div class="tw"><table><tr><th>Course</th><th>Category</th><th>Enrollment</th><th>Lessons</th><th>Learners</th><th>Status</th><th></th></tr>
<?php foreach ($cs as $c): ?><tr><td><b><?= e($c['title']) ?></b></td><td><?= e($c['cat']) ?></td><td><?= e($c['enroll_type']) ?></td><td><?= $c['ln'] ?></td><td><?= $c['n'] ?></td>
<td><span class="badge <?= $c['status'] === 'approved' ? '' : ($c['status'] === 'pending' ? 'warn' : 'bad') ?>"><?= $c['status'] ?></span></td>
<td class="right"><a class="btn btn-sm btn-outline" href="course.php?id=<?= $c['id'] ?>">Preview</a> <a class="btn btn-sm" href="course_edit.php?id=<?= $c['id'] ?>">Manage</a></td></tr><?php endforeach; ?></table></div>
<?php if (!$cs) echo '<div class="empty">You have not created any courses yet.</div>'; ?></div>
<?php include 'includes/footer.php'; ?>
