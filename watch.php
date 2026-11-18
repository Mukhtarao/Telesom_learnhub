<?php
// Records that a learner really finished a video/audio lesson (checked again when the lesson is completed)
require 'includes/config.php';
header('Content-Type: application/json');
function out($ok, $msg = ''){ echo json_encode(['ok' => $ok, 'msg' => $msg]); exit; }
$u = me(); if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Please log in again.');
$lid = (int)($_POST['lesson'] ?? 0);
$l = one("SELECT * FROM lessons WHERE id=?", [$lid]);
$en = $l ? one("SELECT id FROM enrollments WHERE user_id=? AND course_id=? AND status='active'", [$u['id'], $l['course_id']]) : null;
if (!$l || !$en || !in_array($l['type'], ['video', 'audio'])) out(false, 'Not allowed.');
$start = $_SESSION['lstart'][$lid] ?? 0; $dur = max(0, min(4 * 3600, (int)($_POST['duration'] ?? 0)));
if (!$start || time() - $start < ($dur > 0 ? $dur * 0.45 : 5)) out(false, 'Please watch the whole lesson at normal speed.');
$_SESSION['watched'][$lid] = time();
out(true);
