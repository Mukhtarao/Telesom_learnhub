<?php
require 'includes/config.php';
$l = $_GET['set'] ?? 'en'; if (!isset(LANGS[$l])) $l = 'en';
setcookie('lh_lang', $l, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
if (!empty($_SESSION['uid'])) q("UPDATE users SET lang=? WHERE id=?", [$l, $_SESSION['uid']]);
$back = $_GET['back'] ?? 'index.php';
if (!preg_match('~^[A-Za-z0-9_./?=&%#-]+$~', $back) || strpos($back, '//') !== false || $back[0] === '/' || $back[0] === '.') $back = 'index.php';
redirect($back);
