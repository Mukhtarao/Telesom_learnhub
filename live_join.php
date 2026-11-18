<?php
// Records attendance, then sends the person to the meeting room
require 'includes/config.php';
$u = require_login(); $id = (int)($_GET['id'] ?? 0);
$L = one("SELECT l.*, c.instructor_id, c.org_id FROM live_classes l JOIN courses c ON c.id=l.course_id WHERE l.id=?", [$id]);
if (!$L) { flash('Live class not found.', 'error'); redirect('live.php'); }
$mgr = can_manage($L, $u);
if (!$mgr && org_course_blocked($L)) { flash('This course belongs to an organization whose subscription is not active. Please contact your organization.', 'error'); redirect('dashboard.php'); }
$en = one("SELECT id FROM enrollments WHERE user_id=? AND course_id=? AND status='active'", [$u['id'], $L['course_id']]);
if (!$mgr && !$en) { flash('Enroll in the course to join its live classes.', 'error'); redirect('course.php?id='.$L['course_id']); }
$st = live_state($L);
if ($st === 'cancelled' || $st === 'ended') { flash('This live class is not available any more.', 'error'); redirect('live.php'); }
if (!$mgr && !live_can_join($L)) { flash('The room opens 15 minutes before the start time.', 'error'); redirect('live.php'); }
if (!preg_match('~^https?://~i', $L['meeting_url'])) { flash('The meeting link is invalid. Please tell your instructor.', 'error'); redirect('live.php'); }
if (!$mgr) q("INSERT IGNORE INTO live_attendance(live_id,user_id) VALUES(?,?)", [$id, $u['id']]);
header('Location: '.$L['meeting_url']); exit;
