<?php
// "Add to calendar" file for a live class
require 'includes/config.php';
$u = require_login(); $id = (int)($_GET['id'] ?? 0);
$L = one("SELECT l.*, c.instructor_id, c.org_id, c.title ct FROM live_classes l JOIN courses c ON c.id=l.course_id WHERE l.id=?", [$id]);
$ok = $L && (can_manage($L, $u) || one("SELECT id FROM enrollments WHERE user_id=? AND course_id=? AND status='active'", [$u['id'], $L['course_id']]));
if (!$ok) { flash('Live class not found.', 'error'); redirect('live.php'); }
function ics($s){ return str_replace(['\\', ';', ',', "\r", "\n"], ['\\\\', '\\;', '\\,', '', '\\n'], (string)$s); }
$s = strtotime($L['start_at']); $e = $s + $L['duration_min'] * 60;
header('Content-Type: text/calendar; charset=utf-8'); header('Content-Disposition: attachment; filename="live-class-'.$id.'.ics"');
echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Telesom LearnHub//Live Classes//EN\r\nBEGIN:VEVENT\r\nUID:live-$id@telesom-learnhub\r\n";
echo 'DTSTAMP:'.gmdate('Ymd\THis\Z')."\r\nDTSTART:".gmdate('Ymd\THis\Z', $s)."\r\nDTEND:".gmdate('Ymd\THis\Z', $e)."\r\n";
echo 'SUMMARY:'.ics($L['title'].' ('.$L['ct'].')')."\r\nDESCRIPTION:".ics(($L['description'] ?: 'Live class on Telesom LearnHub').'\nJoin: '.$L['meeting_url'])."\r\nURL:".$L['meeting_url']."\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
