<?php
require 'includes/config.php';
$u = require_login(); $id = $u['id'];
if ($u['role'] === 'admin') $contacts = all("SELECT id,name,role FROM users WHERE id<>? ORDER BY name", [$id]);
else $contacts = all("SELECT DISTINCT x.id,x.name,x.role FROM users x WHERE x.id<>? AND x.status='active' AND (
   x.role='admin'
   OR x.id IN (SELECT from_id FROM messages WHERE to_id=?) OR x.id IN (SELECT to_id FROM messages WHERE from_id=?)
   OR x.id IN (SELECT c.instructor_id FROM courses c JOIN enrollments e ON e.course_id=c.id WHERE e.user_id=?)
   OR x.id IN (SELECT e.user_id FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE c.instructor_id=? OR c.org_id=?)
   OR (x.org_id IS NOT NULL AND x.org_id=?) OR x.id=(SELECT org_id FROM users WHERE id=?)) ORDER BY x.name", [$id, $id, $id, $id, $id, $id, $id, $id]);
$ids = array_column($contacts, 'id'); $with = (int)($_GET['with'] ?? 0);
if ($with && !in_array($with, $ids)) $with = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $with && trim($_POST['body'] ?? '') !== '') {
    q("INSERT INTO messages(from_id,to_id,body) VALUES(?,?,?)", [$id, $with, trim($_POST['body'])]);
    notify($with, 'New message from '.$u['name'], 'messages.php?with='.$id); redirect("messages.php?with=$with");
}
$thread = []; $peer = null;
if ($with) { q("UPDATE messages SET is_read=1 WHERE to_id=? AND from_id=?", [$id, $with]);
  $thread = all("SELECT * FROM messages WHERE (from_id=? AND to_id=?) OR (from_id=? AND to_id=?) ORDER BY id", [$id, $with, $with, $id]);
  foreach ($contacts as $c) if ($c['id'] == $with) $peer = $c; }
$title = 'Messages'; include 'includes/header.php';
?>
<div class="cols" style="grid-template-columns:1fr 2fr">
 <div class="card"><h3>Contacts</h3><?php foreach ($contacts as $c): $un = one("SELECT COUNT(*) c FROM messages WHERE to_id=? AND from_id=? AND is_read=0", [$id, $c['id']])['c']; ?>
  <a href="messages.php?with=<?= $c['id'] ?>" class="cmt" style="text-decoration:none;color:var(--ink);<?= $with == $c['id'] ? 'background:var(--gl);border-radius:10px;padding-left:8px' : '' ?>"><span class="av"><?= e(initials($c['name'])) ?></span><div style="flex:1"><b><?= e($c['name']) ?></b><br><small class="muted"><?= e(ucfirst($c['role'])) ?></small></div><?= $un ? '<span class="badge">'.$un.'</span>' : '' ?></a><?php endforeach; if (!$contacts) echo '<p class="muted">No contacts yet. Enroll in a course to message your instructor.</p>'; ?></div>
 <div class="card"><?php if (!$peer): ?><div class="empty">💬 Select a contact to start chatting.</div><?php else: ?><h3>Chat with <?= e($peer['name']) ?></h3>
  <div class="chat"><?php foreach ($thread as $m): ?><div class="msg <?= $m['from_id'] == $id ? 'mine' : '' ?>"><?= nl2br(e($m['body'])) ?><small><?= fdate($m['created_at'], true) ?></small></div><?php endforeach; if (!$thread) echo '<p class="muted">No messages yet — say hello!</p>'; ?></div>
  <form method="post" class="inline-form"><?= csrf_field() ?><input name="body" placeholder="Type a message…" required autocomplete="off"><button class="btn">Send</button></form><?php endif; ?></div>
</div>
<?php include 'includes/footer.php'; ?>
