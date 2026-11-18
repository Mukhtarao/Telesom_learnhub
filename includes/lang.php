<?php
// Languages: English (default), Somali, Arabic (right-to-left)
const LANGS = ['en' => ['English', 'ltr'], 'so' => ['Soomaali', 'ltr'], 'ar' => ['العربية', 'rtl']];

function cur_lang(){
    static $l = null; if ($l !== null) return $l;
    $c = $_COOKIE['lh_lang'] ?? '';
    if (!isset(LANGS[$c])) {
        $c = '';
        if (!empty($_SESSION['uid'])) { try { $r = one("SELECT lang FROM users WHERE id=?", [$_SESSION['uid']]); if ($r && isset(LANGS[$r['lang'] ?? ''])) $c = $r['lang']; } catch (Throwable $ex) {} }
    }
    return $l = ($c ?: 'en');
}
function lang_dir(){ return LANGS[cur_lang()][1]; }
/** Translate a UI string (the English text is the key). Unknown strings stay in English. */
function t($s){
    static $T = null;
    if ($T === null) { $l = cur_lang(); $f = __DIR__.'/../lang/'.$l.'.php'; $T = ($l !== 'en' && is_file($f)) ? (include $f) : []; }
    return $T[$s] ?? $s;
}
/** date() with localized day and month names */
function lang_date($fmt, $ts){
    $o = date($fmt, $ts); $l = cur_lang(); if ($l === 'en') return $o;
    static $M = null; if ($M === null) { $M = include __DIR__.'/../lang/dates.php'; }
    return preg_replace_callback('/\b(Mon|Tue|Wed|Thu|Fri|Sat|Sun|Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\b/', function ($m) use ($M, $l) { return $M[$l][$m[1]] ?? $m[1]; }, $o);
}
function lang_switcher($class = ''){
    $back = rawurlencode(basename($_SERVER['SCRIPT_NAME'] ?? 'index.php').(!empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : ''));
    $o = '<div class="langsw '.$class.'" role="group" aria-label="Language">';
    foreach (LANGS as $k => $v) $o .= '<a href="setlang.php?set='.$k.'&amp;back='.$back.'" class="'.(cur_lang() === $k ? 'on' : '').'" hreflang="'.$k.'" title="'.e($v[0]).'">'.strtoupper($k).'</a>';
    return $o.'</div>';
}
