<?php
require 'includes/config.php';
$u = require_login(['instructor', 'organization', 'admin']);
$id = (int)($_GET['id'] ?? 0); $new = !$id; $c = null;
if ($id) { $c = one("SELECT * FROM courses WHERE id=?", [$id]); if (!$c || !can_manage($c, $u)) { flash('Course not found.', 'error'); redirect('instructor.php'); } }
$tab = $_GET['tab'] ?? 'details';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? ''; $back = "course_edit.php?id=$id&tab=";
    if ($a === 'save') {
        $f = [trim($_POST['title']), trim($_POST['description']), (int)$_POST['category_id'] ?: null, $_POST['level'], trim($_POST['duration']), trim($_POST['objectives']), trim($_POST['prerequisites']), $_POST['enroll_type'], (float)$_POST['price']];
        if (!$f[0]) { flash('Title is required.', 'error'); redirect($new ? 'course_edit.php?new=1' : $back.'details'); }
        if ($u['role'] === 'organization') $f[7] = 'private'; elseif ($f[7] === 'private' && !org_of($u)) $f[7] = 'free';
        if ($new) {
            $org = org_of($u); $auto = $u['role'] !== 'instructor' || $org;
            if ($org && !org_can_add_course($org)) { flash(org_sub($org) ? 'The private-course limit of your plan is reached. Upgrade your plan to add more courses.' : 'Your organization subscription is not active.', 'error'); redirect($u['role'] === 'organization' ? 'subscription.php' : 'instructor.php'); }
            q("INSERT INTO courses(title,description,category_id,level,duration,objectives,prerequisites,enroll_type,price,instructor_id,org_id,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)", array_merge($f, [$u['id'], $org, $auto ? 'approved' : 'pending']));
            $id = q("SELECT LAST_INSERT_ID() i")->fetch()['i'];
            if (!$auto) foreach (all("SELECT id FROM users WHERE role='admin'") as $ad) notify($ad['id'], 'New course awaiting approval: '.$f[0], 'admin.php?tab=courses');
            flash($auto ? 'Course created. Add lessons now.' : 'Course created and sent for administrator approval. Add lessons now.'); redirect("course_edit.php?id=$id&tab=lessons");
        }
        q("UPDATE courses SET title=?,description=?,category_id=?,level=?,duration=?,objectives=?,prerequisites=?,enroll_type=?,price=? WHERE id=?", array_merge($f, [$id])); flash('Course saved.'); redirect($back.'details');
    }
    if ($new) redirect('instructor.php');
    if ($a === 'lesson') {
        $file = upload('file'); $pos = (int)one("SELECT COALESCE(MAX(position),0)+1 p FROM lessons WHERE course_id=?", [$id])['p'];
        q("INSERT INTO lessons(course_id,title,type,content,url,file,position) VALUES(?,?,?,?,?,?,?)", [$id, trim($_POST['title']), $_POST['type'], trim($_POST['content']), trim($_POST['url']) ?: null, $file, $pos]);
        foreach (all("SELECT user_id FROM enrollments WHERE course_id=? AND status='active'", [$id]) as $en) notify($en['user_id'], 'New lesson in '.$c['title'].': '.trim($_POST['title']), 'learn.php?course='.$id);
        flash('Lesson added.'); redirect($back.'lessons');
    }
    if ($a === 'edit_lesson') { $nf = upload('file'); q("UPDATE lessons SET title=?, content=?, url=?, file=COALESCE(?,file) WHERE id=? AND course_id=?", [trim($_POST['title']), trim($_POST['content']), trim($_POST['url']) ?: null, $nf, (int)$_POST['rid'], $id]); flash('Lesson updated.'); redirect($back.'lessons'); }
    if ($a === 'move_lesson') {
        $ids = array_map('intval', array_column(all("SELECT id FROM lessons WHERE course_id=? ORDER BY position,id", [$id]), 'id'));
        $k = array_search((int)$_POST['rid'], $ids, true); $j = ($_POST['dir'] ?? '') === 'up' ? $k - 1 : $k + 1;
        if ($k !== false && isset($ids[$j])) { $t = $ids[$k]; $ids[$k] = $ids[$j]; $ids[$j] = $t; }
        foreach ($ids as $p => $lid) q("UPDATE lessons SET position=? WHERE id=?", [$p + 1, $lid]);
        redirect($back.'lessons');
    }
    if ($a === 'live_add') {
        $t = trim($_POST['title'] ?? ''); $ts = strtotime(str_replace('T', ' ', $_POST['start_at'] ?? ''));
        $pv = in_array($_POST['provider'] ?? '', ['jitsi', 'zoom', 'meet', 'teams', 'other']) ? $_POST['provider'] : 'jitsi'; $url = trim($_POST['meeting_url'] ?? '');
        if ($pv === 'jitsi') $url = rtrim(JITSI_BASE, '/').'/TelesomLearnHub-'.substr(preg_replace('/[^A-Za-z0-9]/', '', $c['title']), 0, 24).'-'.bin2hex(random_bytes(4));
        if (!$t || !$ts) flash('Enter a title and a valid date and time.', 'error');
        elseif (!preg_match('~^https?://~i', $url)) flash('Enter a valid meeting link starting with https://', 'error');
        else {
            q("INSERT INTO live_classes(course_id,title,description,start_at,duration_min,meeting_url,provider) VALUES(?,?,?,?,?,?,?)", [$id, $t, trim($_POST['description'] ?? ''), date('Y-m-d H:i:s', $ts), max(10, (int)($_POST['duration_min'] ?? 60)), $url, $pv]);
            foreach (all("SELECT user_id FROM enrollments WHERE course_id=? AND status='active'", [$id]) as $en) notify($en['user_id'], '📡 Live class scheduled: '.$t.' — '.date('D d M, H:i', $ts), 'live.php');
            flash('Live class scheduled and learners were notified.');
        }
        redirect($back.'live');
    }
    if ($a === 'live_status') {
        $L = one("SELECT * FROM live_classes WHERE id=? AND course_id=?", [(int)$_POST['rid'], $id]); $map = ['golive' => 'live', 'end' => 'ended', 'cancel' => 'cancelled', 'reopen' => 'scheduled']; $do = $_POST['do'] ?? '';
        if ($L && isset($map[$do])) {
            q("UPDATE live_classes SET status=? WHERE id=?", [$map[$do], $L['id']]);
            if ($do === 'golive' || $do === 'cancel') foreach (all("SELECT user_id FROM enrollments WHERE course_id=? AND status='active'", [$id]) as $en) notify($en['user_id'], ($do === 'golive' ? '🔴 Live now: ' : '❌ Live class cancelled: ').$L['title'], 'live.php');
            flash(['golive' => 'Class is live — learners were notified.', 'end' => 'Class ended.', 'cancel' => 'Class cancelled.', 'reopen' => 'Class re-opened.'][$do]);
        }
        redirect($back.'live');
    }
    if ($a === 'live_rec') { $r = trim($_POST['recording_url'] ?? ''); if ($r === '' || preg_match('~^https?://~i', $r)) { q("UPDATE live_classes SET recording_url=? WHERE id=? AND course_id=?", [$r ?: null, (int)$_POST['rid'], $id]); flash('Recording link saved.'); } else flash('Recording link must start with https://', 'error'); redirect($back.'live'); }
    if ($a === 'live_del') { q("DELETE FROM live_classes WHERE id=? AND course_id=?", [(int)$_POST['rid'], $id]); flash('Live class deleted.'); redirect($back.'live'); }
    if ($a === 'del_lesson') { q("DELETE FROM lessons WHERE id=? AND course_id=?", [(int)$_POST['rid'], $id]); flash('Lesson deleted.'); redirect($back.'lessons'); }
    if ($a === 'assignment') {
        q("INSERT INTO assignments(course_id,title,instructions,file,deadline,max_marks) VALUES(?,?,?,?,?,?)", [$id, trim($_POST['title']), trim($_POST['instructions']), upload('file'), $_POST['deadline'] ?: null, (int)$_POST['max_marks'] ?: 100]);
        foreach (all("SELECT user_id FROM enrollments WHERE course_id=? AND status='active'", [$id]) as $en) notify($en['user_id'], 'New assignment in '.$c['title'].': '.trim($_POST['title']), 'learn.php?course='.$id);
        flash('Assignment created.'); redirect($back.'assess');
    }
    if ($a === 'del_assignment') { q("DELETE FROM assignments WHERE id=? AND course_id=?", [(int)$_POST['rid'], $id]); flash('Assignment deleted.'); redirect($back.'assess'); }
    if ($a === 'quiz') { q("INSERT INTO quizzes(course_id,title,type,pass_marks,time_limit,max_attempts) VALUES(?,?,?,?,?,?)", [$id, trim($_POST['title']), $_POST['type'], (int)$_POST['pass_marks'], (int)$_POST['time_limit'], (int)$_POST['max_attempts']]); flash('Created. Now add questions.'); redirect($back.'assess'); }
    if ($a === 'del_quiz') { q("DELETE FROM quizzes WHERE id=? AND course_id=?", [(int)$_POST['rid'], $id]); flash('Deleted.'); redirect($back.'assess'); }
    if ($a === 'question') {
        $z = one("SELECT id FROM quizzes WHERE id=? AND course_id=?", [(int)$_POST['quiz_id'], $id]); $t = $_POST['qtype'];
        $ans = $t === 'mcq' ? ($_POST['ans_mcq'] ?? 'a') : ($t === 'tf' ? ($_POST['ans_tf'] ?? 'true') : trim($_POST['ans_short']));
        if ($z && trim($_POST['question']) && $ans !== '') q("INSERT INTO questions(quiz_id,question,type,opt_a,opt_b,opt_c,opt_d,answer,marks) VALUES(?,?,?,?,?,?,?,?,?)", [$z['id'], trim($_POST['question']), $t, trim($_POST['a']), trim($_POST['b']), trim($_POST['c']), trim($_POST['d']), $ans, max(1, (int)$_POST['marks'])]);
        flash('Question added.'); redirect($back.'assess&open='.(int)$_POST['quiz_id']);
    }
    if ($a === 'del_question') { q("DELETE FROM questions WHERE id=? AND quiz_id IN (SELECT id FROM quizzes WHERE course_id=?)", [(int)$_POST['rid'], $id]); flash('Question deleted.'); redirect($back.'assess'); }
    if ($a === 'enr') {
        $e = one("SELECT * FROM enrollments WHERE id=? AND course_id=?", [(int)$_POST['rid'], $id]);
        if ($e && $_POST['do'] === 'approve') { q("UPDATE enrollments SET status='active' WHERE id=?", [$e['id']]); notify($e['user_id'], 'Your enrollment in "'.$c['title'].'" was approved.', 'learn.php?course='.$id); flash('Learner approved.'); }
        elseif ($e) { q("DELETE FROM enrollments WHERE id=?", [$e['id']]); notify($e['user_id'], 'Your enrollment request for "'.$c['title'].'" was declined.', 'courses.php'); flash('Request removed.'); }
        redirect($back.'learners');
    }
    redirect($back.$tab);
}
$cats = all("SELECT * FROM categories ORDER BY name");
$v = $c ?: ['title' => '', 'description' => '', 'category_id' => '', 'level' => 'Beginner', 'duration' => '', 'objectives' => '', 'prerequisites' => '', 'enroll_type' => 'free', 'price' => 0, 'status' => 'pending'];
$title = $new ? 'New Course' : 'Manage: '.$c['title']; include 'includes/header.php';
if (!$new): ?>
<div class="tabs"><?php foreach (['details' => 'Details', 'lessons' => 'Lessons', 'assess' => 'Assignments & Quizzes', 'learners' => 'Learners', 'live' => '📡 Live classes'] as $k => $l): ?><a class="<?= $tab === $k ? 'on' : '' ?>" href="course_edit.php?id=<?= $id ?>&tab=<?= $k ?>"><?= $l ?></a><?php endforeach; ?><a href="course.php?id=<?= $id ?>" style="margin-left:auto">👁 Preview</a></div>
<?php if ($c['status'] !== 'approved'): ?><div class="flash error">Status: <?= e($c['status']) ?> — learners can see this course only after administrator approval.</div><?php endif; endif;

if ($new || $tab === 'details'): ?>
<form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><h3><?= $new ? 'Create a course' : 'Course details' ?></h3>
 <label style="margin-top:0">Course title</label><input name="title" value="<?= e($v['title']) ?>" required>
 <label>Description</label><textarea name="description"><?= e($v['description']) ?></textarea>
 <div class="fr"><div><label>Category</label><select name="category_id"><option value="">—</option><?php foreach ($cats as $k): ?><option value="<?= $k['id'] ?>" <?= $v['category_id'] == $k['id'] ? 'selected' : '' ?>><?= e($k['name']) ?></option><?php endforeach; ?></select></div>
 <div><label>Level</label><select name="level"><?php foreach (['Beginner', 'Intermediate', 'Advanced'] as $l) echo '<option '.($v['level'] == $l ? 'selected' : '').'>'.$l.'</option>'; ?></select></div>
 <div><label>Duration</label><input name="duration" placeholder="e.g. 6 hours" value="<?= e($v['duration']) ?>"></div>
 <div><label>Enrollment type</label><select name="enroll_type"><?php foreach (['free' => 'Free', 'paid' => 'Paid', 'approval' => 'Instructor-approved', 'private' => 'Private (organization)'] as $k => $l) echo '<option value="'.$k.'" '.($v['enroll_type'] == $k ? 'selected' : '').'>'.$l.'</option>'; ?></select></div>
 <div><label>Price (USD, for paid)</label><input type="number" step="0.01" min="0" name="price" value="<?= e($v['price']) ?>"></div></div>
 <label>Learning objectives (one per line)</label><textarea name="objectives"><?= e($v['objectives'] ? str_replace('|', "\n", $v['objectives']) : '') ?></textarea>
 <label>Prerequisites</label><input name="prerequisites" value="<?= e($v['prerequisites']) ?>">
 <div class="form-actions"><button class="btn"><?= $new ? 'Create course' : 'Save course' ?></button></div></form>

<?php elseif ($tab === 'lessons'): $ls = all("SELECT * FROM lessons WHERE course_id=? ORDER BY position,id", [$id]); ?>
<div class="cols"><div class="card"><h3>Lessons (<?= count($ls) ?>)</h3>
 <?php foreach ($ls as $i => $l): ?><div class="lrow"><div class="lmain"><span><b><?= $i + 1 ?>.</b> <?= e($l['title']) ?> <span class="badge grey"><?= e($l['type']) ?></span></span><span class="lact">
 <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="move_lesson"><input type="hidden" name="rid" value="<?= $l['id'] ?>"><button name="dir" value="up" class="btn btn-sm btn-outline" <?= $i == 0 ? 'disabled' : '' ?>>↑</button> <button name="dir" value="down" class="btn btn-sm btn-outline" <?= $i == count($ls) - 1 ? 'disabled' : '' ?>>↓</button></form>
 <button type="button" class="btn btn-sm btn-outline" onclick="var d=this.closest('.lrow').querySelector('.ledit');d.style.display=d.style.display==='block'?'none':'block'">✏️ Edit</button>
 <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="del_lesson"><input type="hidden" name="rid" value="<?= $l['id'] ?>"><button class="btn btn-sm btn-danger" data-confirm="Delete this lesson?">Delete</button></form></span></div>
 <form class="ledit" method="post" enctype="multipart/form-data" style="display:none"><?= csrf_field() ?><input type="hidden" name="action" value="edit_lesson"><input type="hidden" name="rid" value="<?= $l['id'] ?>">
  <label style="margin-top:0">Title</label><input name="title" value="<?= e($l['title']) ?>" required><label>URL (YouTube / Vimeo / MP4 / link)</label><input name="url" value="<?= e($l['url']) ?>"><label>Replace file (optional)</label><input type="file" name="file"><label>Text / notes</label><textarea name="content"><?= e($l['content']) ?></textarea><div class="form-actions"><button class="btn btn-sm">Save lesson</button></div></form></div><?php endforeach; if (!$ls) echo '<div class="empty">No lessons yet — add the first one.</div>'; ?></div>
 <form class="card" method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="lesson"><h3>Add lesson</h3>
  <label style="margin-top:0">Title</label><input name="title" required>
  <label>Type</label><select name="type" id="ltype"><option value="text">Text lesson</option><option value="video">Video</option><option value="audio">Audio</option><option value="pdf">PDF document</option><option value="document">Word / PowerPoint / other file</option><option value="image">Image</option><option value="link">External link / interactive</option></select>
  <div data-lt="video audio pdf image link document"><label>URL (YouTube link, external link or direct file URL)</label><input name="url" placeholder="https://…"></div>
  <div data-lt="video audio pdf image document"><label>…or upload a file</label><input type="file" name="file"></div>
  <label>Text / notes</label><textarea name="content"></textarea><div class="form-actions"><button class="btn">Add lesson</button></div></form></div>

<?php elseif ($tab === 'assess'): $as = all("SELECT * FROM assignments WHERE course_id=?", [$id]); $zs = all("SELECT z.*, (SELECT COUNT(*) FROM questions WHERE quiz_id=z.id) qn FROM quizzes z WHERE course_id=?", [$id]); $open = (int)($_GET['open'] ?? 0); ?>
<div class="cols"><div>
 <div class="card"><h3>Quizzes & examinations</h3>
  <?php foreach ($zs as $z): $qq = all("SELECT * FROM questions WHERE quiz_id=?", [$z['id']]); ?><div style="border:1px solid var(--line);border-radius:12px;padding:14px;margin-bottom:12px">
   <div style="display:flex;justify-content:space-between;align-items:center"><b><?= e($z['title']) ?> <span class="badge"><?= $z['type'] ?></span></b>
   <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="del_quiz"><input type="hidden" name="rid" value="<?= $z['id'] ?>"><button class="btn btn-sm btn-danger" data-confirm="Delete this quiz and all its questions?">Delete</button></form></div>
   <small class="muted">Pass <?= $z['pass_marks'] ?>% · <?= $z['time_limit'] ?: 'no' ?> min limit · <?= $z['max_attempts'] ?: 'unlimited' ?> attempts · <?= $z['qn'] ?> questions</small>
   <?php foreach ($qq as $q): ?><div style="display:flex;justify-content:space-between;gap:10px;padding:6px 0;border-top:1px dashed var(--line);margin-top:6px"><span><?= e($q['question']) ?> <span class="badge grey"><?= $q['type'] ?> · <?= $q['marks'] ?>pt</span></span>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="del_question"><input type="hidden" name="rid" value="<?= $q['id'] ?>"><button class="btn btn-sm btn-outline">✕</button></form></div><?php endforeach; ?></div><?php endforeach; if (!$zs) echo '<p class="muted">No quizzes yet.</p>'; ?></div>
 <div class="card"><h3>Assignments</h3><?php foreach ($as as $a): ?><div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--line)"><span><b><?= e($a['title']) ?></b> <small class="muted">due <?= $a['deadline'] ? fdate($a['deadline']) : '—' ?> · <?= $a['max_marks'] ?> marks</small></span>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="del_assignment"><input type="hidden" name="rid" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-danger" data-confirm="Delete this assignment?">Delete</button></form></div><?php endforeach; if (!$as) echo '<p class="muted">No assignments yet.</p>'; ?></div>
</div><div>
 <form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="quiz"><h3>New quiz / exam</h3><label style="margin-top:0">Title</label><input name="title" required>
  <div class="fr"><div><label>Type</label><select name="type"><option value="quiz">Quiz</option><option value="exam">Final examination</option></select></div><div><label>Pass mark %</label><input type="number" name="pass_marks" value="50" min="1" max="100"></div>
  <div><label>Time limit (min, 0 = none)</label><input type="number" name="time_limit" value="0" min="0"></div><div><label>Max attempts (0 = ∞)</label><input type="number" name="max_attempts" value="0" min="0"></div></div><div class="form-actions"><button class="btn btn-sm">Create</button></div></form>
 <?php if ($zs): ?><form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="question"><h3>Add question</h3>
  <label style="margin-top:0">Quiz</label><select name="quiz_id"><?php foreach ($zs as $z) echo '<option value="'.$z['id'].'" '.($open == $z['id'] ? 'selected' : '').'>'.e($z['title']).'</option>'; ?></select>
  <label>Question</label><textarea name="question" style="min-height:60px" required></textarea>
  <div class="fr"><div><label>Type</label><select name="qtype" id="qtype"><option value="mcq">Multiple choice</option><option value="tf">True / False</option><option value="short">Short answer</option></select></div><div><label>Marks</label><input type="number" name="marks" value="1" min="1"></div></div>
  <div data-for="mcq"><div class="fr"><div><label>Option A</label><input name="a"></div><div><label>Option B</label><input name="b"></div><div><label>Option C</label><input name="c"></div><div><label>Option D</label><input name="d"></div></div>
   <label>Correct option</label><select name="ans_mcq"><option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option></select></div>
  <div data-for="tf"><label>Correct answer</label><select name="ans_tf"><option value="true">True</option><option value="false">False</option></select></div>
  <div data-for="short"><label>Accepted answer(s) — separate with |</label><input name="ans_short"></div>
  <div class="form-actions"><button class="btn btn-sm">Add question</button></div></form><?php endif; ?>
 <form class="card" method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="assignment"><h3>New assignment</h3><label style="margin-top:0">Title</label><input name="title" required>
  <label>Instructions</label><textarea name="instructions"></textarea><div class="fr"><div><label>Deadline</label><input type="datetime-local" name="deadline"></div><div><label>Max marks</label><input type="number" name="max_marks" value="100" min="1"></div></div>
  <label>Instruction file (optional)</label><input type="file" name="file"><div class="form-actions"><button class="btn btn-sm">Create assignment</button></div></form>
</div></div>

<?php elseif ($tab === 'live'):
  $lcs = all("SELECT l.*, (SELECT COUNT(*) FROM live_attendance WHERE live_id=l.id) att FROM live_classes l WHERE l.course_id=? ORDER BY l.start_at DESC", [$id]);
  $nEn = (int)one("SELECT COUNT(*) c FROM enrollments WHERE course_id=? AND status='active'", [$id])['c']; ?>
<div class="cols"><div>
 <div class="card"><h3>📡 Live classes (<?= count($lcs) ?>) <small class="muted">Times in <?= date('T (P)') ?></small></h3>
 <?php foreach ($lcs as $L): $st = live_state($L); $sa = strtotime($L['start_at']);
   $at = $L['att'] ? all("SELECT u.name, a.joined_at FROM live_attendance a JOIN users u ON u.id=a.user_id WHERE a.live_id=? ORDER BY a.joined_at", [$L['id']]) : []; ?>
  <div class="lrow"><div class="lmain"><span><b><?= e($L['title']) ?></b> <span class="badge <?= $st === 'live' ? 'bad' : ($st === 'upcoming' ? '' : 'grey') ?>"><?= $st === 'live' ? '● LIVE' : ucfirst($st) ?></span><br><small class="muted">🕒 <?= date('D d M Y, H:i', $sa) ?> · <?= (int)$L['duration_min'] ?> min · <?= e(['jitsi' => 'Jitsi Meet', 'zoom' => 'Zoom', 'meet' => 'Google Meet', 'teams' => 'Microsoft Teams', 'other' => 'Online'][$L['provider']] ?? 'Online') ?> · <a href="<?= e($L['meeting_url']) ?>" target="_blank" rel="noopener">open room ↗</a></small></span>
   <span class="lact"><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="live_status"><input type="hidden" name="rid" value="<?= $L['id'] ?>">
     <?php if ($st === 'upcoming'): ?><button name="do" value="golive" class="btn btn-sm">▶ Go live</button> <?php endif; ?>
     <?php if ($st === 'live'): ?><button name="do" value="end" class="btn btn-sm">■ End class</button> <?php endif; ?>
     <?php if ($st === 'upcoming' || $st === 'live'): ?><button name="do" value="cancel" class="btn btn-sm btn-outline" data-confirm="Cancel this class? Learners will be notified.">Cancel</button> <?php endif; ?>
     <?php if ($st === 'cancelled' || $st === 'ended'): ?><button name="do" value="reopen" class="btn btn-sm btn-outline">Re-open</button> <?php endif; ?></form>
    <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="live_del"><input type="hidden" name="rid" value="<?= $L['id'] ?>"><button class="btn btn-sm btn-danger" data-confirm="Delete this live class?">Delete</button></form></span></div>
   <form method="post" class="inline-form" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="action" value="live_rec"><input type="hidden" name="rid" value="<?= $L['id'] ?>"><input name="recording_url" placeholder="Recording link (YouTube, Drive, Zoom…)" value="<?= e($L['recording_url']) ?>"><button class="btn btn-sm btn-outline">Save recording</button></form>
   <details style="margin-top:8px"><summary style="cursor:pointer;font-weight:600">👥 Attendance: <?= (int)$L['att'] ?> of <?= $nEn ?> learners</summary><?php foreach ($at as $x) echo '<div style="padding:4px 0"><small>✅ '.e($x['name']).' · joined '.fdate($x['joined_at'], true).'</small></div>'; if (!$at) echo '<small class="muted">Nobody has joined yet.</small>'; ?></details></div>
 <?php endforeach; if (!$lcs) echo '<div class="empty">No live classes yet — schedule your first one.</div>'; ?></div>
</div>
<form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="live_add"><h3>Schedule a live class</h3>
  <label style="margin-top:0">Title</label><input name="title" placeholder="e.g. Live Q&A — Week 1" required>
  <label>Description (optional)</label><textarea name="description" style="min-height:70px"></textarea>
  <div class="fr"><div><label>Date & time</label><input type="datetime-local" name="start_at" required></div><div><label>Duration (minutes)</label><input type="number" name="duration_min" value="60" min="10" max="480"></div></div>
  <label>Platform</label><select name="provider" onchange="document.getElementById('mlink').style.display=this.value==='jitsi'?'none':''"><option value="jitsi">Jitsi Meet — free room, created automatically</option><option value="zoom">Zoom</option><option value="meet">Google Meet</option><option value="teams">Microsoft Teams</option><option value="other">Other link</option></select>
  <div id="mlink" style="display:none"><label>Meeting link</label><input name="meeting_url" placeholder="https://…"></div>
  <small class="hint">Learners can enter the room from 15 minutes before the start. Attendance is recorded when they press Join. Everyone enrolled is notified.</small>
  <div class="form-actions"><button class="btn">Schedule class</button></div></form></div>

<?php else: $en = all("SELECT e.*, u.name, u.email FROM enrollments e JOIN users u ON u.id=e.user_id WHERE e.course_id=? ORDER BY e.status, e.id DESC", [$id]); ?>
<div class="card"><h3>Enrolled learners (<?= count($en) ?>)</h3><div class="tw"><table><tr><th>Learner</th><th>Enrolled</th><th>Progress</th><th>Status</th><th></th></tr>
<?php foreach ($en as $e): $pg = $e['status'] === 'active' ? progress($e['user_id'], $id) : 0; ?><tr><td><b><?= e($e['name']) ?></b><br><small class="muted"><?= e($e['email']) ?></small></td><td><?= fdate($e['enrolled_at']) ?></td><td style="min-width:140px"><div class="bar"><i style="width:<?= $pg ?>%"></i></div><small><?= $pg ?>%</small></td><td><span class="badge <?= $e['status'] === 'pending' ? 'warn' : '' ?>"><?= $e['status'] ?></span></td>
<td class="right"><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="enr"><input type="hidden" name="rid" value="<?= $e['id'] ?>">
<?php if ($e['status'] === 'pending'): ?><button name="do" value="approve" class="btn btn-sm">Approve</button> <?php endif; ?><button name="do" value="remove" class="btn btn-sm btn-danger" data-confirm="Remove this learner?"><?= $e['status'] === 'pending' ? 'Decline' : 'Remove' ?></button></form></td></tr><?php endforeach; ?></table></div>
<?php if (!$en) echo '<div class="empty">No learners yet.</div>'; ?></div>
<?php endif; include 'includes/footer.php'; ?>
