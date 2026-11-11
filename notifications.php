<?php
require 'includes/config.php';
$u = require_login();
if (isset($_GET['go'])) { $n = one("SELECT * FROM notifications WHERE id=? AND user_id=?", [(int)$_GET['go'], $u['id']]); if ($n) { q("UPDATE notifications SET is_read=1 WHERE id=?", [$n['id']]); redirect($n['link'] ?: 'notifications.php'); } }
if ($_SERVER['REQUEST_METHOD'] === 'POST') { q("UPDATE notifications SET is_read=1 WHERE user_id=?", [$u['id']]); redirect('notifications.php'); }
$rows = all("SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 60", [$u['id']]);
$title = 'Notifications'; include 'includes/header.php';
?>
<div class="card"><h3>Your notifications <form method="post"><?= csrf_field() ?><button class="btn btn-sm btn-outline">Mark all as read</button></form></h3>
<?php foreach ($rows as $n): ?><a href="notifications.php?go=<?= $n['id'] ?>" style="display:flex;gap:12px;padding:12px;border-bottom:1px solid var(--line);color:var(--ink);text-decoration:none;<?= $n['is_read'] ? '' : 'background:var(--g-soft);font-weight:600' ?>"><span>🔔</span><span style="flex:1"><?= e($n['message']) ?></span><small class="muted"><?= fdate($n['created_at'], true) ?></small></a><?php endforeach; if (!$rows) echo '<div class="empty">You are all caught up.</div>'; ?></div>
<?php include 'includes/footer.php'; ?>
