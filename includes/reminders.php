<?php
// Automatic reminders: live classes, assignment deadlines, subscription expiry (in-app + e-mail + SMS)

/** Sends once per (kind, ref, user). Returns true when it was new. */
function remind($uid, $kind, $ref, $title, $body, $link = ''){
    if (!q("INSERT IGNORE INTO reminders_sent(kind,ref_id,user_id) VALUES(?,?,?)", [$kind, $ref, $uid])->rowCount()) return false;
    notify($uid, $title, $link);
    $u = one("SELECT name,email,phone,notify_email,notify_sms FROM users WHERE id=? AND status='active'", [$uid]);
    if ($u) {
        if ($u['notify_email'] && $u['email']) send_mail($u['email'], $title, mail_html($u['name'], $title, $body, $link), $title."\n".$body."\n".base_url().'/'.$link);
        if ($u['notify_sms'] && $u['phone']) send_sms($u['phone'], $title.' — '.$body);
    }
    return true;
}

function run_reminders($limit = 60){
    $sent = ['live' => 0, 'assignment' => 0, 'subscription' => 0]; $left = $limit;
    // 1) live classes starting within 24 hours (one reminder at ~24h, one inside the last hour)
    foreach (all("SELECT l.*, c.title ct, c.instructor_id FROM live_classes l JOIN courses c ON c.id=l.course_id WHERE l.status='scheduled' AND l.start_at > NOW() AND l.start_at <= DATE_ADD(NOW(), INTERVAL 24 HOUR)") as $L) {
        $mins = (int)round((strtotime($L['start_at']) - time()) / 60); $kind = $mins <= 60 ? 'live1h' : 'live24h';
        $who = array_column(all("SELECT user_id FROM enrollments WHERE course_id=? AND status='active'", [$L['course_id']]), 'user_id'); $who[] = $L['instructor_id'];
        foreach (array_unique($who) as $uid) {
            if ($left <= 0) break 2;
            if (remind($uid, $kind, $L['id'], '📡 Live class '.($mins <= 60 ? 'starts in about '.max(1, $mins).' min' : 'tomorrow').': '.$L['title'], $L['ct'].' — '.date('D d M, H:i', strtotime($L['start_at'])).'. You can enter the room 15 minutes before the start.', 'live.php')) { $sent['live']++; $left--; }
        }
    }
    // 2) assignments due within 24 hours and not submitted yet
    foreach (all("SELECT a.id, a.title, a.deadline, c.title ct, e.user_id FROM assignments a JOIN courses c ON c.id=a.course_id JOIN enrollments e ON e.course_id=c.id AND e.status='active' WHERE a.deadline > NOW() AND a.deadline <= DATE_ADD(NOW(), INTERVAL 24 HOUR) AND NOT EXISTS (SELECT 1 FROM submissions s WHERE s.assignment_id=a.id AND s.user_id=e.user_id)") as $A) {
        if ($left <= 0) break;
        if (remind($A['user_id'], 'assign24h', $A['id'], '📝 Assignment due soon: '.$A['title'], $A['ct'].' — deadline '.date('D d M, H:i', strtotime($A['deadline'])).'.', 'assignment.php?id='.$A['id'])) { $sent['assignment']++; $left--; }
    }
    // 3) organization subscriptions ending within 7 days (no reminder if a renewal is already queued)
    foreach (all("SELECT s.* FROM org_subscriptions s WHERE s.status='active' AND s.end_at >= CURDATE() AND s.end_at <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND NOT EXISTS (SELECT 1 FROM org_subscriptions n WHERE n.org_id=s.org_id AND n.status='active' AND n.start_at > s.end_at)") as $S) {
        if ($left <= 0) break;
        $days = (int)round((strtotime($S['end_at']) - strtotime(date('Y-m-d'))) / 86400); $kind = $days <= 1 ? 'sub1d' : ($days <= 3 ? 'sub3d' : 'sub7d');
        if (remind($S['org_id'], $kind, $S['id'], '⏰ Your subscription ends '.($days <= 0 ? 'today' : 'in '.$days.' day'.($days == 1 ? '' : 's')), 'Your '.$S['plan_name'].' plan ends on '.date('d M Y', strtotime($S['end_at'])).'. Renew now so your members keep access.', 'subscription.php')) { $sent['subscription']++; $left--; }
    }
    q("INSERT INTO app_state(k,v) VALUES('reminders_last',?) ON DUPLICATE KEY UPDATE v=VALUES(v)", [time()]);
    return $sent;
}

/** Runs at most every 10 minutes, after the page has been sent (so XAMPP without a scheduler still works). */
function maybe_run_reminders(){
    try {
        $r = one("SELECT v FROM app_state WHERE k='reminders_last'");
        if ($r && time() - (int)$r['v'] < 600) return;
        q("INSERT INTO app_state(k,v) VALUES('reminders_last',?) ON DUPLICATE KEY UPDATE v=VALUES(v)", [time()]);
    } catch (Throwable $ex) { return; }
    register_shutdown_function(function () {
        if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request(); else { @ob_flush(); @flush(); }
        try { run_reminders(40); } catch (Throwable $ex) { error_log('reminders: '.$ex->getMessage()); }
    });
}
