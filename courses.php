<?php
require 'includes/config.php';
$u = me(); $mine = !empty($_GET['mine']);
if ($mine) require_login();
$qs = trim($_GET['q'] ?? ''); $cat = (int)($_GET['cat'] ?? 0); $lv = $_GET['level'] ?? ''; $sort = $_GET['sort'] ?? 'new';
$w = ["c.status='approved'"]; $p = [];
$myorg = $u ? org_of($u) : null;
if ($mine) { $w[] = "c.id IN (SELECT course_id FROM enrollments WHERE user_id=?)"; $p[] = $u['id']; }
else { $w[] = $myorg ? "(c.org_id IS NULL OR c.org_id=?)" : "c.org_id IS NULL"; if ($myorg) $p[] = $myorg; }
if ($qs !== '') { $w[] = "(c.title LIKE ? OR c.description LIKE ?)"; $p[] = "%$qs%"; $p[] = "%$qs%"; }
if ($cat) { $w[] = "c.category_id=?"; $p[] = $cat; }
if (in_array($lv, ['Beginner', 'Intermediate', 'Advanced'])) { $w[] = "c.level=?"; $p[] = $lv; }
$ord = ['new' => 'c.id DESC', 'popular' => 'n DESC', 'rating' => 'rt DESC', 'title' => 'c.title ASC'][$sort] ?? 'c.id DESC';
$courses = all("SELECT c.*, u.name inst, k.name cat, (SELECT ROUND(AVG(rating),1) FROM reviews WHERE course_id=c.id) rt,
  (SELECT COUNT(*) FROM enrollments WHERE course_id=c.id) n FROM courses c JOIN users u ON u.id=c.instructor_id LEFT JOIN categories k ON k.id=c.category_id
  WHERE ".implode(' AND ', $w)." ORDER BY $ord", $p);
$cats = all("SELECT * FROM categories ORDER BY name");
$layout = $u ? 'app' : 'public'; $title = $mine ? 'My Courses' : 'Browse Courses';
include 'includes/header.php';
if (!$u): ?><div class="hero-sm"><div class="wrap"><h1>Explore courses</h1><p>Find the right course and start learning today.</p></div></div><div class="wrap pubc"><?php endif; ?>
<form class="card filters" method="get">
  <?php if ($mine): ?><input type="hidden" name="mine" value="1"><?php endif; ?>
  <div style="flex:2"><label>Search</label><input name="q" placeholder="🔍 Search courses…" value="<?= e($qs) ?>"></div>
  <div><label>Category</label><select name="cat"><option value="">All</option><?php foreach ($cats as $k): ?><option value="<?= $k['id'] ?>" <?= $cat == $k['id'] ? 'selected' : '' ?>><?= e($k['name']) ?></option><?php endforeach; ?></select></div>
  <div><label>Level</label><select name="level"><option value="">All</option><?php foreach (['Beginner', 'Intermediate', 'Advanced'] as $l): ?><option <?= $lv == $l ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div><label>Sort by</label><select name="sort"><?php foreach (['new' => 'Recently added', 'popular' => 'Most popular', 'rating' => 'Top rated', 'title' => 'Title A–Z'] as $k => $v): ?><option value="<?= $k ?>" <?= $sort == $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
  <button class="btn">Apply</button>
</form>
<?php if (!$courses): ?><div class="card empty">No courses found. <?= $mine ? '<a href="courses.php">Browse the catalog</a>' : '' ?></div><?php endif; ?>
<div class="grid"><?php foreach ($courses as $i => $c):
  $en = $u ? one("SELECT status FROM enrollments WHERE user_id=? AND course_id=?", [$u['id'], $c['id']]) : null; ?>
  <div class="cc"><div class="th c<?= $i % 3 + 1 ?>" data-i="<?= e(substr($c['title'], 0, 1)) ?>"><?= e($c['cat'] ?: 'General') ?></div>
  <div class="bd"><h3><?= e($c['title']) ?></h3>
    <div class="meta"><span>👤 <?= e($c['inst']) ?></span><span>⏱ <?= e($c['duration']) ?></span><span>📶 <?= e($c['level']) ?></span><span>👥 <?= $c['n'] ?></span></div>
    <?php if ($en && $en['status'] === 'active'): $pg = progress($u['id'], $c['id']); ?><div class="bar"><i style="width:<?= $pg ?>%"></i></div><small class="muted"><?= $pg ?>% complete</small><?php endif; ?>
    <div class="ft"><span><span class="star"><?= $c['rt'] ? '★ '.$c['rt'] : 'New' ?></span> · <span class="price"><?= $c['enroll_type'] === 'paid' ? '$'.number_format($c['price'], 2) : ($c['enroll_type'] === 'private' ? 'Private' : 'Free') ?></span></span>
    <a class="btn btn-sm" href="<?= $en && $en['status'] === 'active' ? 'learn.php?course='.$c['id'] : 'course.php?id='.$c['id'] ?>"><?= $en ? ($en['status'] === 'pending' ? 'Pending' : 'Continue') : 'View' ?></a></div>
  </div></div><?php endforeach; ?></div>
<?php if (!$u) echo '</div>'; include 'includes/footer.php'; ?>
