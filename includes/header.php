<?php
require_once __DIR__.'/config.php';
$u = me(); $layout = $layout ?? 'app'; $title = $title ?? 'Telesom LearnHub';
$curpage = basename($_SERVER['SCRIPT_NAME']);
function menu($role){
    // [link, label, icon, other links that keep this item highlighted]
    $m = [
     'learner' => [['dashboard.php','Dashboard','🏠'],['courses.php','Browse Courses','🔎'],['mycourses.php','My Courses','📚'],['live.php','Live Classes','📡'],['certificates.php','Certificates','🎓'],['reports.php','My Report','📊']],
     'instructor' => [['dashboard.php','Dashboard','🏠'],['instructor.php','My Courses','📚'],['live.php','Live Classes','📡'],['grading.php','Grading','📝'],['reports.php','Reports','📊']],
     'organization' => [['dashboard.php','Dashboard','🏠'],['organization.php','Members & Assign','🏢'],['instructor.php','Private Courses','📚'],['live.php','Live Classes','📡'],['reports.php','Training Reports','📊'],['subscription.php','Subscription','💎']],
     'admin' => [['dashboard.php','Dashboard','🏠'],['admin.php?tab=users','People','👥',['admin.php?tab=orgs']],['admin.php?tab=courses','Courses & Content','📚',['admin.php?tab=categories','admin.php?tab=announcements','admin.php?tab=reviews']],['payments.php','Finance','💰',['admin.php?tab=subs','admin.php?tab=plans']],['live.php','Live Classes','📡'],['reports.php','Reports','📊']],
    ];
    return $m[$role] ?? [];
}
/** Tab strip shown at the top of grouped administrator pages */
function admin_subnav($cur){
    $groups = [
        'People' => [['admin.php?tab=users','Users'],['admin.php?tab=orgs','Organizations']],
        'Content' => [['admin.php?tab=courses','Courses'],['admin.php?tab=categories','Categories'],['admin.php?tab=announcements','Announcements'],['admin.php?tab=reviews','Reported Reviews']],
        'Finance' => [['payments.php','Payments'],['admin.php?tab=subs','Subscriptions'],['admin.php?tab=plans','Plans & Pricing']],
    ];
    foreach ($groups as $tabs) foreach ($tabs as $t) if (is_active($t[0], basename($_SERVER['SCRIPT_NAME']))) { $o = '<nav class="subnav" aria-label="Section">'; foreach ($tabs as $x) $o .= '<a class="'.(is_active($x[0], basename($_SERVER['SCRIPT_NAME'])) ? 'on' : '').'" href="'.$x[0].'">'.e(t($x[1])).'</a>'; return $o.'</nav>'; }
    return '';
}
function is_active($href, $cur){
    $p = parse_url($href); if ($p['path'] !== $cur) return false;
    if (!isset($p['query'])) return true;
    parse_str($p['query'], $want); foreach ($want as $k => $v) if (($_GET[$k] ?? ($k === 'tab' ? 'users' : null)) !== $v) return false;
    return true;
}
function menu_active($m, $cur){ foreach (array_merge([$m[0]], $m[3] ?? []) as $h) if (is_active($h, $cur)) return true; return false; }
if ($u && $layout === 'app') maybe_run_reminders();
$unread = $u ? (int)one("SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0", [$u['id']])['c'] : 0;
$unreadMsg = $u ? (int)one("SELECT COUNT(*) c FROM messages WHERE to_id=? AND is_read=0", [$u['id']])['c'] : 0;
?><!DOCTYPE html>
<html lang="<?= cur_lang() ?>" dir="<?= lang_dir() ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#059669"><link rel="manifest" href="manifest.json"><link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png"><meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes"><link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Crect width='40' height='40' rx='11' fill='%23059669'/%3E%3Cpath d='M20 9 6 16l14 7 11-5.5V25h2.5V16L20 9z' fill='white'/%3E%3C/svg%3E"><script>try{var _t=localStorage.getItem('lh_theme')||'light';document.documentElement.setAttribute('data-theme',_t);}catch(e){}</script>
<title><?= e(t($title)) ?> · Telesom LearnHub</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Arabic:wght@400;600;700;800&family=Great+Vibes&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"><link rel="stylesheet" href="assets/css/theme.css"></head>
<body class="<?= $layout === 'auth' ? 'authpage' : e($layout) ?>"><a class="skip" href="#main"><?= t('Skip to content') ?></a>
<?php if ($layout === 'auth'): ?>
<?= lang_switcher('fab') ?><button class="tg theme-btn theme-fab" type="button" title="Night / day mode" aria-label="Toggle night and day mode"><span class="ti">🌙</span></button>
<?php elseif ($layout === 'public'): ?>
<header class="nav"><div class="wrap nav-in">
  <a class="brand" href="index.php"><?= logo() ?><span>Telesom <b>LearnHub</b></span></a>
  <button class="burger" onclick="document.querySelector('.nav-links').classList.toggle('open')">☰</button>
  <nav class="nav-links"><?= lang_switcher() ?><button class="tg theme-btn" type="button" title="Night / day mode" aria-label="Toggle night and day mode"><span class="ti">🌙</span></button>
    <a href="index.php#about"><?= t('About Telesom') ?></a><a href="index.php#platform"><?= t('The Platform') ?></a><a href="courses.php"><?= t('Courses') ?></a><a href="verify.php"><?= t('Verify Certificate') ?></a><a href="index.php#contact"><?= t('Contact') ?></a>
    <?php if ($u): ?><a class="btn btn-sm" href="dashboard.php"><?= t('Dashboard') ?></a><?php else: ?><a class="btn btn-outline btn-sm" href="login.php"><?= t('Log in') ?></a><a class="btn btn-sm" href="register.php"><?= t('Get started') ?></a><?php endif; ?>
  </nav></div></header>
<main class="pub" id="main">
<?php else: ?>
<div class="shell">
<aside class="side" id="side">
  <a class="brand" href="dashboard.php"><?= logo('#fff') ?><span>Telesom <b>LearnHub</b></span></a>
  <nav aria-label="Main"><?php foreach (menu($u['role']) as $m): ?>
    <a class="<?= menu_active($m, $curpage) ? 'on' : '' ?>" href="<?= $m[0] ?>"><i><?= $m[2] ?></i><span class="lb"><?= e(t($m[1])) ?></span></a>
  <?php endforeach; ?></nav>
</aside>
<div class="side-bd" onclick="document.getElementById('side').classList.remove('open')"></div>
<div class="main">
<header class="top">
  <button class="burger" type="button" aria-label="Menu" onclick="document.getElementById('side').classList.toggle('open')">☰</button>
  <h1><?= e(t($title)) ?></h1>
  <div class="top-r">
    <a class="bell" href="messages.php" title="<?= e(t('Messages')) ?>" aria-label="<?= e(t('Messages')) ?>">💬<?php if ($unreadMsg): ?><span><?= $unreadMsg ?></span><?php endif; ?></a>
    <a class="bell" href="notifications.php" title="<?= e(t('Notifications')) ?>" aria-label="<?= e(t('Notifications')) ?>">🔔<?php if ($unread): ?><span><?= $unread ?></span><?php endif; ?></a>
    <div class="umenu">
      <button class="me" id="umBtn" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="umMenu"><span class="av"><?= e(initials($u['name'])) ?></span><span class="mn"><b><?= e($u['name']) ?></b><small><?= e(t(ucfirst($u['role']))) ?></small></span><svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></button>
      <div class="um-menu" id="umMenu" hidden>
        <div class="um-head"><span class="av"><?= e(initials($u['name'])) ?></span><div><b><?= e($u['name']) ?></b><small><?= e($u['email']) ?></small></div></div>
        <a class="um-i" href="profile.php"><i>👤</i><?= t('My profile') ?></a>
        <?php if ($u['role'] === 'learner'): ?><a class="um-i" href="payments.php"><i>💳</i><?= t('Payments') ?></a><?php endif; ?>
        <?php if ($u['role'] === 'admin'): ?><a class="um-i" href="admin.php?tab=health"><i>🩺</i><?= t('System Health') ?></a><?php endif; ?>
        <div class="um-sep"></div>
        <div class="um-lab"><?= t('Settings') ?></div>
        <div class="um-row"><span><i>🌐</i><?= t('Language') ?></span><?= lang_switcher() ?></div>
        <button class="um-i theme-btn" type="button" data-night="<?= e(t('Night mode')) ?>" data-day="<?= e(t('Day mode')) ?>"><i class="ti">🌙</i><span class="tl"><?= t('Night mode') ?></span></button>
        <button class="um-i install-btn" type="button" hidden><i>📲</i><?= t('Install app') ?></button>
        <div class="um-sep"></div>
        <a class="um-i out" href="logout.php"><i>⏻</i><?= t('Log out') ?></a>
      </div>
    </div>
  </div>
</header>
<main class="content" id="main">
<?php if ($layout === 'app' && $u['role'] === 'admin') echo admin_subnav($curpage); ?>
<?php endif; ?>
<?php if ($layout === 'app' && $u && $u['role'] === 'organization' && ($ob = org_sub($u['id'])) && (strtotime($ob['end_at']) - time()) <= 14 * 86400 && !one("SELECT id FROM org_subscriptions WHERE org_id=? AND status='active' AND start_at>?", [$u['id'], $ob['end_at']])): $dl = max(0, (int)ceil((strtotime($ob['end_at']) - time()) / 86400)); ?><div class="flash warn"><span>⏰ Your <b><?= e($ob['plan_name']) ?></b> subscription ends in <b><?= $dl ?> day<?= $dl == 1 ? '' : 's' ?></b> (<?= fdate($ob['end_at']) ?>). <a href="subscription.php"><b><?= t('Renew now →') ?></b></a></span></div><?php endif; ?>
<?php if (!empty($_SESSION['flash']) && $layout !== 'auth'): ?><div class="<?= $layout === 'public' ? 'wrap' : '' ?>"><?php foreach ($_SESSION['flash'] as $f): ?><div class="flash toast <?= $f[1] ?>"><?= e(t($f[0])) ?><button onclick="this.parentNode.remove()">×</button></div><?php endforeach; unset($_SESSION['flash']); ?></div><?php endif; ?>
