<?php
// Scheduled reminders. Call it every 10-15 minutes:
//   Linux cron:    */10 * * * *  php /path/to/telesom-learnhub/cron.php YOUR_CRON_KEY
//   Windows Task:  php.exe C:\xampp\htdocs\telesom-learnhub\cron.php YOUR_CRON_KEY
//   Web (cPanel):  https://yourdomain/telesom-learnhub/cron.php?key=YOUR_CRON_KEY
define('NO_CSRF', true);
require __DIR__.'/includes/config.php';
$given = PHP_SAPI === 'cli' ? ($argv[1] ?? '') : ($_GET['key'] ?? '');
if (CRON_KEY === '' || !hash_equals(CRON_KEY, (string)$given)) { if (PHP_SAPI !== 'cli') http_response_code(403); echo "forbidden\n"; exit(1); }
if (PHP_SAPI !== 'cli') header('Content-Type: application/json');
echo json_encode(['ok' => true, 'sent' => run_reminders(200), 'time' => date('c')]), "\n";
