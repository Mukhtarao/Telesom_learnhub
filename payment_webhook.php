<?php
// Automatic payment confirmation. A payment gateway / SMS-forwarder / Telesom's IT team POSTs JSON here when money arrives:
//   {"reference":"TXN12345","amount":25.00,"status":"paid","phone":"0634000000","timestamp":1700000000}
// Header  X-Signature: hex( HMAC-SHA256( raw request body, WEBHOOK_SECRET ) )
// It confirms the pending course payment or organization subscription that has the same reference (transaction ID).
define('NO_CSRF', true);
require __DIR__.'/includes/config.php';
header('Content-Type: application/json');
function jout($code, $arr){ http_response_code($code); echo json_encode($arr); exit; }
if (WEBHOOK_SECRET === '') jout(503, ['ok' => false, 'error' => 'Automatic payment confirmation is not enabled (set WEBHOOK_SECRET in includes/config.php).']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jout(405, ['ok' => false, 'error' => 'POST only']);
$raw = (string)file_get_contents('php://input'); $sig = strtolower(trim($_SERVER['HTTP_X_SIGNATURE'] ?? ''));
if (!hash_equals(hash_hmac('sha256', $raw, WEBHOOK_SECRET), $sig)) jout(401, ['ok' => false, 'error' => 'Bad signature']);
$d = json_decode($raw, true); if (!is_array($d)) jout(400, ['ok' => false, 'error' => 'Invalid JSON']);
$ref = trim((string)($d['reference'] ?? '')); $amt = (float)($d['amount'] ?? 0); $st = strtolower((string)($d['status'] ?? '')); $ts = (int)($d['timestamp'] ?? 0); $ph = preg_replace('/\D/', '', (string)($d['phone'] ?? ''));
if ($ref === '' || $amt <= 0) jout(400, ['ok' => false, 'error' => 'reference and amount are required']);
if (abs(time() - $ts) > 600) jout(400, ['ok' => false, 'error' => 'Missing or stale timestamp (must be within 10 minutes)']);
if (!in_array($st, ['paid', 'success', 'completed'], true)) jout(200, ['ok' => true, 'ignored' => 'status '.$st]);
$phoneOk = function ($stored) use ($ph) { $s = preg_replace('/\D/', '', (string)$stored); return $ph === '' || $s === '' || substr($ph, -7) === substr($s, -7); };

$p = one("SELECT * FROM payments WHERE reference=? AND status='pending' ORDER BY id DESC LIMIT 1", [$ref]);
if ($p) {
    if ($amt + 0.001 < (float)$p['amount']) jout(422, ['ok' => false, 'error' => 'Amount is lower than the course price']);
    if (!$phoneOk($p['phone'])) jout(422, ['ok' => false, 'error' => 'Phone number does not match']);
    q("UPDATE payments SET status='paid', paid_at=NOW() WHERE id=?", [$p['id']]); grant_paid_access($p['user_id'], $p['course_id']);
    jout(200, ['ok' => true, 'applied' => 'course payment #'.$p['id']]);
}
$s = one("SELECT * FROM org_subscriptions WHERE reference=? AND status='pending' ORDER BY id DESC LIMIT 1", [$ref]);
if ($s) {
    if ($amt + 0.001 < (float)$s['amount']) jout(422, ['ok' => false, 'error' => 'Amount is lower than the plan price']);
    if (!$phoneOk($s['phone'])) jout(422, ['ok' => false, 'error' => 'Phone number does not match']);
    org_activate($s['id']); jout(200, ['ok' => true, 'applied' => 'subscription #'.$s['id']]);
}
if (one("SELECT id FROM payments WHERE reference=? AND status='paid'", [$ref]) || one("SELECT id FROM org_subscriptions WHERE reference=? AND status='active'", [$ref])) jout(200, ['ok' => true, 'already' => true]);
jout(404, ['ok' => false, 'error' => 'No pending payment with that reference']);
