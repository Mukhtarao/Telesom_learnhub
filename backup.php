<?php
// Administrator-only backups: ?type=sql (database) or ?type=files (uploads folder as .zip)
require 'includes/config.php';
$u = require_login('admin');
$type = $_GET['type'] ?? 'sql';
if ($type === 'files') {
    if (!class_exists('ZipArchive')) { flash('The PHP zip extension is not enabled (enable extension=zip in php.ini) — or copy the uploads folder by hand.', 'error'); redirect('admin.php?tab=health'); }
    $tmp = tempnam(sys_get_temp_dir(), 'lhz'); $z = new ZipArchive();
    if ($z->open($tmp, ZipArchive::OVERWRITE) !== true) { flash('Could not create the zip file.', 'error'); redirect('admin.php?tab=health'); }
    $root = realpath(__DIR__.'/uploads'); $z->addEmptyDir('uploads');
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $f) if ($f->isFile()) $z->addFile($f->getPathname(), 'uploads/'.substr($f->getPathname(), strlen($root) + 1));
    $z->close();
    header('Content-Type: application/zip'); header('Content-Disposition: attachment; filename="learnhub-uploads-'.date('Y-m-d-His').'.zip"'); header('Content-Length: '.filesize($tmp));
    readfile($tmp); unlink($tmp); exit;
}
header('Content-Type: application/sql; charset=utf-8'); header('Content-Disposition: attachment; filename="telesom-learnhub-'.date('Y-m-d-His').'.sql"');
echo '-- Telesom LearnHub database backup '.date('c')."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
foreach (all("SHOW TABLES") as $row) {
    $t = array_values($row)[0]; $c = one("SHOW CREATE TABLE `$t`");
    echo "DROP TABLE IF EXISTS `$t`;\n".array_values($c)[1].";\n\n";
    $st = $pdo->query("SELECT * FROM `$t`");
    while ($r = $st->fetch(PDO::FETCH_NUM)) echo "INSERT INTO `$t` VALUES (".implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $r)).");\n";
    echo "\n";
}
echo "SET FOREIGN_KEY_CHECKS=1;\n";
q("INSERT INTO app_state(k,v) VALUES('backup_last',?) ON DUPLICATE KEY UPDATE v=VALUES(v)", [time()]);
