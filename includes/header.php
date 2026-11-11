<?php
require_once __DIR__.'/config.php';
$u = me(); $layout = $layout ?? 'app'; $title = $title ?? 'Telesom LearnHub';
$curpage = basename($_SERVER['SCRIPT_NAME']);
function menu($role){
    $m = [
     'learner' => [['dashboard.php','Dashboard','🏠'],['courses.php','Browse Courses','🔎'],['mycourses.php','My Courses','📚'],['live.php','Live Classes','📡'],['certificates.php','Certificates','🎓'],['payments.php','Payments','💳'],['messages.php','Messages','💬'],['reports.php','My Report','📊'],['profile.php','Profile','👤']],
     'instructor' => [['dashboard.php','Dashboard','🏠'],['instructor.php','My Courses','📚'],['live.php','Live Classes','📡'],['grading.php','Grading','📝'],['messages.php','Messages','💬'],['reports.php','Reports','📊'],['courses.php','Browse Courses','🔎'],['profile.php','Profile','👤']],
     'organization' => [['dashboard.php','Dashboard','🏠'],['subscription.php','Subscription','💎'],['organization.php','Members & Assign','🏢'],['instructor.php','Private Courses','📚'],['live.php','Live Classes','📡'],['reports.php','Training Reports','📊'],['messages.php','Messages','💬'],['courses.php','Browse Courses','🔎'],['profile.php','Profile','👤']],
     'admin' => [['dashboard.php','Dashboard','🏠'],['admin.php?tab=users','Users','👥'],['admin.php?tab=orgs','Organizations','🏢'],['admin.php?tab=subs','Subscriptions','🧾'],['admin.php?tab=plans','Plans & Pricing','🏷️'],['admin.php?tab=courses','Courses','📚'],['live.php','Live Classes','📡'],['admin.php?tab=categories','Categories','🗂️'],['admin.php?tab=announcements','Announcements','📣'],['payments.php','Payments','💳'],['admin.php?tab=reviews','Reported Reviews','🚩'],['reports.php','Reports','📊'],['messages.php','Messages','💬'],['profile.php','Profile','👤']],
    ];
    return $m[$role] ?? [];
}
function is_active($href, $cur){
    $p = parse_url($href); if ($p['path'] !== $cur) return false;
    return isset($p['query']) ? (strpos($_SERVER['QUERY_STRING'] ?? '', $p['query']) !== false) : true;
}
$unread = $u ? (int)one("SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0", [$u['id']])['c'] : 0;
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><script>try{var _t=localStorage.getItem('lh_theme')||'light';document.documentElement.setAttribute('data-theme',_t);if(localStorage.getItem('lh_side')==='1')document.documentElement.classList.add('side-c');}catch(e){}</script>
<title><?= e($title) ?> · Telesom LearnHub</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Great+Vibes&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"><link rel="stylesheet" href="assets/css/theme.css"></head>
<body class="<?= $layout === 'auth' ? 'authpage' : e($layout) ?>">
<?php if ($layout === 'auth'): ?>
<button class="tg theme-btn theme-fab" type="button" title="Night / day mode" aria-label="Toggle night and day mode"><span class="ti">🌙</span></button>
<?php elseif ($layout === 'public'): ?>
<header class="nav"><div class="wrap nav-in">
  <a class="brand" href="index.php"><?= logo() ?><span>Telesom <b>LearnHub</b></span></a>
  <button class="burger" onclick="document.querySelector('.nav-links').classList.toggle('open')">☰</button>
  <nav class="nav-links"><button class="tg theme-btn" type="button" title="Night / day mode" aria-label="Toggle night and day mode"><span class="ti">🌙</span></button>
    <a href="index.php#about">About Telesom</a><a href="index.php#platform">The Platform</a><a href="courses.php">Courses</a><a href="verify.php">Verify Certificate</a><a href="index.php#contact">Contact</a>
    <?php if ($u): ?><a class="btn btn-sm" href="dashboard.php">Dashboard</a><?php else: ?><a class="btn btn-outline btn-sm" href="login.php">Log in</a><a class="btn btn-sm" href="register.php">Get started</a><?php endif; ?>
  </nav></div></header>
<main class="pub">
<?php else: ?>
<div class="shell">
<aside class="side" id="side">
  <a class="brand" href="index.php"><?= logo('#fff') ?><span>Telesom <b>LearnHub</b></span></a>
  <nav><?php foreach (menu($u['role']) as $m): ?>
    <a class="<?= is_active($m[0], $curpage) ? 'on' : '' ?>" href="<?= $m[0] ?>" title="<?= e($m[1]) ?>"><i><?= $m[2] ?></i><span class="lb"><?= $m[1] ?></span></a>
  <?php endforeach; ?></nav>
  <a class="logout" href="logout.php" title="Log out"><i>⏻</i><span class="lb">Log out</span></a>
</aside>
<div class="main">
<header class="top">
  <button class="tg side-tg" id="sideToggle" type="button" title="Collapse / expand menu" aria-label="Collapse or expand the sidebar"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3"/><path d="M9 4v16"/></svg></button>
  <button class="burger" onclick="document.getElementById('side').classList.toggle('open')">☰</button>
  <h1><?= e($title) ?></h1>
  <div class="top-r">
    <button class="tg theme-btn" type="button" title="Night / day mode" aria-label="Toggle night and day mode"><span class="ti">🌙</span></button>
    <a class="bell" href="notifications.php" title="Notifications">🔔<?php if ($unread): ?><span><?= $unread ?></span><?php endif; ?></a>
    <a class="me" href="profile.php"><span class="av"><?= e(initials($u['name'])) ?></span><span class="mn"><b><?= e($u['name']) ?></b><small><?= e(ucfirst($u['role'])) ?></small></span></a>
  </div>
</header>
<main class="content">
<?php endif; ?>
<?php if ($layout === 'app' && $u && $u['role'] === 'organization' && ($ob = org_sub($u['id'])) && (strtotime($ob['end_at']) - time()) <= 14 * 86400 && !one("SELECT id FROM org_subscriptions WHERE org_id=? AND status='active' AND start_at>?", [$u['id'], $ob['end_at']])): $dl = max(0, (int)ceil((strtotime($ob['end_at']) - time()) / 86400)); ?><div class="flash warn"><span>⏰ Your <b><?= e($ob['plan_name']) ?></b> subscription ends in <b><?= $dl ?> day<?= $dl == 1 ? '' : 's' ?></b> (<?= fdate($ob['end_at']) ?>). <a href="subscription.php"><b>Renew now →</b></a></span></div><?php endif; ?>
<?php if (!empty($_SESSION['flash']) && $layout !== 'auth'): ?><div class="<?= $layout === 'public' ? 'wrap' : '' ?>"><?php foreach ($_SESSION['flash'] as $f): ?><div class="flash <?= $f[1] ?>"><?= e($f[0]) ?><button onclick="this.parentNode.remove()">×</button></div><?php endforeach; unset($_SESSION['flash']); ?></div><?php endif; ?>
