<?php
require 'includes/config.php';
$u = require_login(); $role = $u['role']; $id = $u['id'];
$csv = isset($_GET['csv']);
if ($role === 'learner') {
    $h = ['Course', 'Progress %', 'Quiz/Exam avg %', 'Assignment avg %', 'Status', 'Certificate'];
    $rows = array_map(fn($c) => [$c['title'], progress($id, $c['id']), ($x = one("SELECT ROUND(AVG(score*100/total)) a FROM attempts a JOIN quizzes z ON z.id=a.quiz_id WHERE a.user_id=? AND z.course_id=?", [$id, $c['id']])['a']) ?? '—', one("SELECT ROUND(AVG(s.marks*100/a.max_marks)) a FROM submissions s JOIN assignments a ON a.id=s.assignment_id WHERE s.user_id=? AND a.course_id=? AND s.marks IS NOT NULL", [$id, $c['id']])['a'] ?? '—', $c['completed_at'] ? 'Completed' : 'In progress', one("SELECT code FROM certificates WHERE user_id=? AND course_id=?", [$id, $c['id']])['code'] ?? '—'],
        all("SELECT c.*, e.completed_at FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=? AND e.status='active'", [$id]));
    $head = 'Learner report';
} elseif ($role === 'organization' && !isset($_GET['courses'])) {
    $h = ['Member', 'Role', 'Courses enrolled', 'Courses completed', 'Avg assessment %', 'Certificates'];
    $rows = array_map(fn($m) => [$m['name'], $m['role'], $m['n'], $m['d'], $m['avg'] ?? '—', $m['ce']],
        all("SELECT u.name,u.role,(SELECT COUNT(*) FROM enrollments WHERE user_id=u.id AND status='active') n,(SELECT COUNT(*) FROM enrollments WHERE user_id=u.id AND completed_at IS NOT NULL) d,(SELECT ROUND(AVG(score*100/total)) FROM attempts WHERE user_id=u.id) avg,(SELECT COUNT(*) FROM certificates WHERE user_id=u.id) ce FROM users u WHERE u.org_id=? ORDER BY u.name", [$id]));
    $head = 'Organization training report';
} else {
    $sc = $role === 'admin' ? '1=1' : ($role === 'organization' ? "c.org_id=$id" : "c.instructor_id=$id");
    $h = ['Course', 'Active learners', 'Completed', 'Completion %', 'Avg quiz score %', 'Rating', 'Revenue ($)'];
    $rows = array_map(fn($c) => [$c['title'], $c['n'], $c['d'], $c['n'] ? round($c['d'] * 100 / $c['n']) : 0, $c['sc'] ?? '—', $c['rt'] ?? '—', number_format($c['rev'], 2)],
        all("SELECT c.title,(SELECT COUNT(*) FROM enrollments WHERE course_id=c.id AND status='active') n,(SELECT COUNT(*) FROM enrollments WHERE course_id=c.id AND completed_at IS NOT NULL) d,(SELECT ROUND(AVG(a.score*100/a.total)) FROM attempts a JOIN quizzes z ON z.id=a.quiz_id WHERE z.course_id=c.id) sc,(SELECT ROUND(AVG(rating),1) FROM reviews WHERE course_id=c.id) rt,(SELECT COALESCE(SUM(amount),0) FROM payments WHERE course_id=c.id AND status='paid') rev FROM courses c WHERE $sc ORDER BY c.title"));
    if ($role !== 'admin') { array_pop($h); $rows = array_map(fn($r) => array_slice($r, 0, -1), $rows); }   // revenue is admin-only
    $head = ($role === 'admin' ? 'Platform' : ($role === 'organization' ? 'Course' : 'Instructor')).' course report';
}
if ($csv) { header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="learnhub-report.csv"'); $o = fopen('php://output', 'w'); fputcsv($o, $h); foreach ($rows as $r) fputcsv($o, $r); exit; }
$title = 'Reports'; include 'includes/header.php';
?>
<div class="hdr-row"><h2><?= e($head) ?></h2><div class="form-actions" style="margin:0">
<?php if ($role === 'organization'): ?><a class="btn btn-sm btn-outline" href="reports.php<?= isset($_GET['courses']) ? '' : '?courses=1' ?>"><?= isset($_GET['courses']) ? 'Member report' : 'Course report' ?></a><?php endif; ?>
<a class="btn btn-sm" href="reports.php?csv=1<?= isset($_GET['courses']) ? '&courses=1' : '' ?>">⬇ Export CSV</a><button class="btn btn-sm btn-outline no-print" onclick="window.print()">🖨 Print</button></div></div>
<div class="card"><div class="tw"><table><tr><?php foreach ($h as $x) echo '<th>'.e($x).'</th>'; ?></tr>
<?php foreach ($rows as $r): ?><tr><?php foreach ($r as $x) echo '<td>'.e($x).'</td>'; ?></tr><?php endforeach; ?></table></div><?php if (!$rows) echo '<div class="empty">No data yet.</div>'; ?></div>
<?php include 'includes/footer.php'; ?>
