
<?php
// ---- Telesom LearnHub configuration ----
// Supports Railway / managed MySQL and local XAMPP.

// ---- Environment variable helper ----
$env = static function ($key, $default = '') {
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
};

// ---- Database connection ----
// Prefer Railway connection URLs, then fall back to individual variables.
$databaseUrl = $env('DATABASE_URL',
    $env('MYSQL_PUBLIC_URL',
        $env('MYSQL_URL', '')
    )
);

$dbUrl = $databaseUrl !== '' ? parse_url($databaseUrl) : false;

// Extract database connection details from the URL.
$urlHost = is_array($dbUrl) ? ($dbUrl['host'] ?? '') : '';
$urlName = is_array($dbUrl) ? ltrim($dbUrl['path'] ?? '', '/') : '';
$urlUser = is_array($dbUrl) ? rawurldecode($dbUrl['user'] ?? '') : '';
$urlPass = is_array($dbUrl) ? rawurldecode($dbUrl['pass'] ?? '') : '';
$urlPort = is_array($dbUrl) ? ($dbUrl['port'] ?? 3306) : 3306;

// Define database constants.
define('DB_HOST', $urlHost ?: $env('MYSQLHOST', $env('DB_HOST', 'localhost')));

define('DB_NAME', $urlName ?: $env(
    'MYSQLDATABASE',
    $env('MYSQL_DATABASE', $env('DB_NAME', 'telesom_learnhub'))
));

define('DB_USER', $urlUser ?: $env('MYSQLUSER', $env('DB_USER', 'root')));

define('DB_PASS', $urlPass !== ''
    ? $urlPass
    : $env('MYSQLPASSWORD', $env('MYSQL_ROOT_PASSWORD', $env('DB_PASS', '')))
);

define('DB_PORT', (int)($urlHost
    ? $urlPort
    : $env('MYSQLPORT', $env('DB_PORT', '3306'))
));

// ---- Application settings ----
define('APP_TZ', $env('APP_TZ', 'Africa/Mogadishu'));
define('JITSI_BASE', $env('JITSI_BASE', 'https://meet.jit.si'));

// ---- Security & hosting ----
define('APP_URL', rtrim($env('APP_URL', ''), '/'));

$httpsDefault = (stripos(APP_URL, 'https://') === 0);

define('FORCE_HTTPS', filter_var(
    $env('FORCE_HTTPS', $httpsDefault ? 'true' : 'false'),
    FILTER_VALIDATE_BOOLEAN
));

define('CRON_KEY', $env('CRON_KEY', ''));
define('WEBHOOK_SECRET', $env('WEBHOOK_SECRET', ''));

// ---- E-mail ----
define('SMTP_HOST', $env('SMTP_HOST', ''));
define('SMTP_PORT', (int)$env('SMTP_PORT', '587'));
define('SMTP_SECURE', $env('SMTP_SECURE', 'tls'));
define('SMTP_USER', $env('SMTP_USER', ''));
define('SMTP_PASS', $env('SMTP_PASS', ''));
define('MAIL_FROM', $env('MAIL_FROM', 'no-reply@telesom.com'));
define('MAIL_FROM_NAME', $env('MAIL_FROM_NAME', 'Telesom LearnHub'));

// ---- SMS (optional) ----
define('SMS_API_URL', $env('SMS_API_URL', ''));
define('SMS_API_KEY', $env('SMS_API_KEY', ''));
define('SMS_AUTH_HEADER', $env('SMS_AUTH_HEADER', 'Authorization: Bearer '));
define('SMS_SENDER', $env('SMS_SENDER', 'TELESOM'));
define('SMS_FIELD_TO', $env('SMS_FIELD_TO', 'to'));
define('SMS_FIELD_TEXT', $env('SMS_FIELD_TEXT', 'message'));

// ---- Payment settings ----
define('MERCHANT_ZAAD', $env('MERCHANT_ZAAD', '063-4693916'));
define('MERCHANT_EDAHAB', $env('MERCHANT_EDAHAB', '065-7888839'));
define('MERCHANT_BANK', $env(
    'MERCHANT_BANK',
    'Bank: ______  ·  Account name: Telesom LearnHub  ·  Account no: ______'
));

// ---- PHP error handling ----
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ---- HTTPS detection (Railway proxy support) ----
$https = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (
        isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'
    )
);

if (PHP_SAPI !== 'cli') {
    if (FORCE_HTTPS && !$https) {
        header(
            'Location: https://' .
            ($_SERVER['HTTP_HOST'] ?? '') .
            ($_SERVER['REQUEST_URI'] ?? '/'),
            true,
            301
        );
        exit;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    if (FORCE_HTTPS) {
        header('Strict-Transport-Security: max-age=15552000');
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

session_start();

// ---- Connect to MySQL ----
try {
    $dsn = 'mysql:host=' . DB_HOST .
        ';port=' . DB_PORT .
        ';dbname=' . DB_NAME .
        ';charset=utf8mb4';

    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10
        ]
    );

} catch (PDOException $ex) {
    // Log the real error in Railway without exposing credentials.
    error_log('LearnHub database error: ' . $ex->getMessage());

    http_response_code(500);

    die(
        '<h3>Database connection failed.</h3>' .
        '<p>Please check the application logs.</p>'
    );
}

// ---- Time zone ----
date_default_timezone_set(APP_TZ);

try {
    $pdo->exec("SET time_zone='" . date('P') . "'");
} catch (Throwable $ex) {
    error_log('Database timezone warning: ' . $ex->getMessage());
}

// ---- Auto-upgrade: schema V4 ----
if (empty($_SESSION['schema_v4'])) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");

        if (!$pdo->query("SHOW COLUMNS FROM users LIKE 'signatory'")->fetch()) {
            $pdo->exec("
                ALTER TABLE users
                ADD COLUMN signatory VARCHAR(120) NULL,
                ADD COLUMN signatory_title VARCHAR(120) NULL
            ");
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS live_classes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                title VARCHAR(160) NOT NULL,
                description TEXT,
                start_at DATETIME NOT NULL,
                duration_min INT DEFAULT 60,
                meeting_url VARCHAR(500) NOT NULL,
                provider ENUM('jitsi','zoom','meet','teams','other') DEFAULT 'jitsi',
                status ENUM('scheduled','live','ended','cancelled') DEFAULT 'scheduled',
                recording_url VARCHAR(500) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS live_attendance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                live_id INT NOT NULL,
                user_id INT NOT NULL,
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(live_id, user_id),
                FOREIGN KEY (live_id) REFERENCES live_classes(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");

        $_SESSION['schema_v4'] = 1;

    } catch (Throwable $ex) {
        error_log('LearnHub schema V4 warning: ' . $ex->getMessage());
    }
}

// ---- Auto-upgrade: schema V5 ----
if (empty($_SESSION['schema_v5'])) {
    try {
        if (!$pdo->query("SHOW COLUMNS FROM users LIKE 'org_status'")->fetch()) {
            $pdo->exec("
                ALTER TABLE users
                ADD COLUMN org_status ENUM('pending','approved','rejected')
                NOT NULL DEFAULT 'approved'
            ");
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS plans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(80) NOT NULL,
                billing ENUM('monthly','yearly') NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                max_learners INT NOT NULL DEFAULT 25,
                max_courses INT NOT NULL DEFAULT 5,
                features VARCHAR(255) NULL,
                active TINYINT NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0
            ) ENGINE=InnoDB
        ");

        $newSubs = !$pdo->query("SHOW TABLES LIKE 'org_subscriptions'")->fetch();

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS org_subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                org_id INT NOT NULL,
                plan_id INT NULL,
                plan_name VARCHAR(80),
                billing ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
                amount DECIMAL(10,2) NOT NULL DEFAULT 0,
                max_learners INT NOT NULL DEFAULT 25,
                max_courses INT NOT NULL DEFAULT 5,
                status ENUM('pending','active','rejected','cancelled') NOT NULL DEFAULT 'pending',
                sponsored TINYINT NOT NULL DEFAULT 0,
                method ENUM('zaad','edahab','bank','none') DEFAULT 'none',
                phone VARCHAR(30),
                reference VARCHAR(60),
                invoice VARCHAR(30),
                start_at DATE NULL,
                end_at DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                confirmed_at DATETIME NULL,
                FOREIGN KEY (org_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");

        if ($newSubs) {
            $pdo->exec("
                INSERT INTO org_subscriptions
                (org_id,plan_name,billing,amount,max_learners,max_courses,status,
                 sponsored,method,invoice,start_at,end_at,confirmed_at)
                SELECT id,'Founding member (free)','yearly',0,100,0,'active',1,
                'none',CONCAT('SUB-FM-',LPAD(id,4,'0')),CURDATE(),
                DATE_ADD(CURDATE(), INTERVAL 1 YEAR),NOW()
                FROM users WHERE role='organization'
            ");
        }

        if (!$pdo->query("SELECT 1 FROM plans LIMIT 1")->fetch()) {
            $pdo->exec("
                INSERT INTO plans
                (name,billing,price,max_learners,max_courses,features,sort_order)
                VALUES
                ('Starter','monthly',49,25,5,'Assignments, quizzes & exams|Certificates with QR verification|Live classes & reports',1),
                ('Business','monthly',129,100,20,'Everything in Starter|Custom certificate signatory|Priority support',2),
                ('Enterprise','monthly',299,500,0,'Everything in Business|Dedicated onboarding|Custom contract & invoicing',3),
                ('Starter','yearly',490,25,5,'Assignments, quizzes & exams|Certificates with QR verification|Live classes & reports',4),
                ('Business','yearly',1290,100,20,'Everything in Starter|Custom certificate signatory|Priority support',5),
                ('Enterprise','yearly',2990,500,0,'Everything in Business|Dedicated onboarding|Custom contract & invoicing',6)
            ");
        }

        $_SESSION['schema_v5'] = 1;

    } catch (Throwable $ex) {
        error_log('LearnHub schema V5 warning: ' . $ex->getMessage());
    }
}

// ---- Auto-upgrade: schema V6 ----
if (empty($_SESSION['schema_v6'])) {
    try {
        foreach ([
            'terms_at DATETIME NULL',
            'notify_email TINYINT NOT NULL DEFAULT 1',
            'notify_sms TINYINT NOT NULL DEFAULT 0',
            'lang VARCHAR(5) NULL'
        ] as $def) {
            $col = explode(' ', $def)[0];

            if (!$pdo->query("SHOW COLUMNS FROM users LIKE '$col'")->fetch()) {
                $pdo->exec("ALTER TABLE users ADD COLUMN $def");
            }
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip VARCHAR(45) NOT NULL,
                email VARCHAR(150) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(email),
                INDEX(ip)
            ) ENGINE=InnoDB
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reminders_sent (
                id INT AUTO_INCREMENT PRIMARY KEY,
                kind VARCHAR(30) NOT NULL,
                ref_id INT NOT NULL,
                user_id INT NOT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(kind, ref_id, user_id)
            ) ENGINE=InnoDB
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS app_state (
                k VARCHAR(40) PRIMARY KEY,
                v VARCHAR(255)
            ) ENGINE=InnoDB
        ");

        $_SESSION['schema_v6'] = 1;

    } catch (Throwable $ex) {
        error_log('LearnHub schema V6 warning: ' . $ex->getMessage());
    }
}

// ---- General helper functions ----
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function q($sql, $p = []) {
    global $pdo;
    $s = $pdo->prepare($sql);
    $s->execute($p);
    return $s;
}

function one($sql, $p = []) {
    return q($sql, $p)->fetch();
}

function all($sql, $p = []) {
    return q($sql, $p)->fetchAll();
}

function redirect($p) {
    header("Location: $p");
    exit;
}

function flash($m, $t = 'success') {
    $_SESSION['flash'][] = [$m, $t];
}

function me() {
    static $u = null;

    if (!$u && isset($_SESSION['uid'])) {
        $u = one(
            "SELECT * FROM users WHERE id=? AND status='active'",
            [$_SESSION['uid']]
        );
    }

    return $u;
}

function require_login($roles = null) {
    $u = me();

    if (!$u) {
        redirect('login.php');
    }

    if ($roles && !in_array($u['role'], (array)$roles)) {
        flash('Access denied.', 'error');
        redirect('dashboard.php');
    }

    if (
        $u['role'] === 'organization'
        && !in_array(basename($_SERVER['SCRIPT_NAME']), [
            'subscription.php',
            'subscribe.php',
            'org_invoice.php',
            'profile.php',
            'notifications.php',
            'messages.php'
        ])
        && org_state($u) !== 'active'
    ) {
        redirect('subscription.php');
    }

    return $u;
}

function csrf() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . csrf() . '">';
}

if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
    && !defined('NO_CSRF')
    && (
        !isset($_POST['csrf'])
        || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])
    )
) {
    die('Invalid or expired form token. Go back and refresh the page.');
}

// ---- Notifications ----
function notify($uid, $msg, $link = '') {
    q(
        "INSERT INTO notifications(user_id,message,link) VALUES(?,?,?)",
        [$uid, $msg, $link]
    );
}

function initials($n) {
    $p = preg_split('/\s+/', trim($n));
    return strtoupper(substr($p[0], 0, 1) . (isset($p[1]) ? substr($p[1], 0, 1) : ''));
}

function stars($n) {
    $n = round($n);
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}

function fdate($s, $t = false) {
    return $s
        ? lang_date($t ? 'd M Y, H:i' : 'd M Y', strtotime($s))
        : '—';
}

function upload($f, $allowed = [
    'pdf','doc','docx','ppt','pptx','xls','xlsx',
    'mp4','webm','mp3','wav','jpg','jpeg','png',
    'gif','txt','zip'
]) {
    if (empty($_FILES[$f]['name']) || $_FILES[$f]['error'] !== 0) {
        return null;
    }

    $ext = strtolower(pathinfo($_FILES[$f]['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return null;
    }

    $dir = __DIR__ . '/../uploads';

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $n = bin2hex(random_bytes(8)) . '.' . $ext;

    return move_uploaded_file($_FILES[$f]['tmp_name'], "$dir/$n")
        ? "uploads/$n"
        : null;
}

// ---- Course progress ----
function progress($uid, $cid) {
    $l = (int)one(
        "SELECT COUNT(*) c FROM lessons WHERE course_id=?",
        [$cid]
    )['c'];

    $ld = (int)one(
        "SELECT COUNT(*) c FROM progress p
         JOIN lessons l ON l.id=p.lesson_id
         WHERE p.user_id=? AND l.course_id=?",
        [$uid, $cid]
    )['c'];

    $qz = (int)one(
        "SELECT COUNT(*) c FROM quizzes WHERE course_id=?",
        [$cid]
    )['c'];

    $qd = (int)one(
        "SELECT COUNT(DISTINCT a.quiz_id) c
         FROM attempts a
         JOIN quizzes z ON z.id=a.quiz_id
         WHERE a.user_id=? AND z.course_id=? AND a.passed=1",
        [$uid, $cid]
    )['c'];

    $t = $l + $qz;

    return $t ? (int)round(($ld + $qd) / $t * 100) : 0;
}

function check_completion($uid, $cid) {
    if (progress($uid, $cid) < 100) {
        return;
    }

    q(
        "UPDATE enrollments SET completed_at=IFNULL(completed_at,NOW())
         WHERE user_id=? AND course_id=?",
        [$uid, $cid]
    );

    if (!one(
        "SELECT id FROM certificates WHERE user_id=? AND course_id=?",
        [$uid, $cid]
    )) {
        $r = one(
            "SELECT ROUND(AVG(b)) s FROM (
                SELECT MAX(a.score*100/a.total) b
                FROM attempts a
                JOIN quizzes z ON z.id=a.quiz_id
                WHERE a.user_id=? AND z.course_id=?
                GROUP BY a.quiz_id
            ) t",
            [$uid, $cid]
        );

        q(
            "INSERT INTO certificates(user_id,course_id,code,score) VALUES(?,?,?,?)",
            [
                $uid,
                $cid,
                'TLH-' . strtoupper(bin2hex(random_bytes(4))),
                $r['s'] ?? 100
            ]
        );

        notify(
            $uid,
            '🎓 Congratulations! Your certificate is ready.',
            'certificates.php'
        );
    }
}

function can_manage($c, $u) {
    return $u['role'] === 'admin'
        || $c['instructor_id'] == $u['id']
        || ($u['role'] === 'organization' && $c['org_id'] == $u['id']);
}

function org_of($u) {
    return $u['role'] === 'organization' ? $u['id'] : $u['org_id'];
}

function logo($c = 'var(--g)') {
    $f = $c === '#fff' ? 'var(--g)' : '#fff';

    return '<svg width="34" height="34" viewBox="0 0 40 40">
        <rect width="40" height="40" rx="11" fill="' . $c . '"/>
        <path d="M20 9 6 16l14 7 11-5.5V25h2.5V16L20 9z" fill="' . $f . '"/>
        <path d="M12 21v5c0 2.2 3.6 4 8 4s8-1.8 8-4v-5l-8 4-8-4z" fill="' . $f . '" opacity=".85"/>
    </svg>';
}

function money($n) {
    return '$' . number_format((float)$n, 2);
}

function grant_paid_access($uid, $cid) {
    q(
        "INSERT INTO enrollments(user_id,course_id,status,paid)
         VALUES(?,?, 'active',1)
         ON DUPLICATE KEY UPDATE status='active', paid=1",
        [$uid, $cid]
    );

    $c = one("SELECT title, instructor_id FROM courses WHERE id=?", [$cid]);
    $l = one("SELECT name FROM users WHERE id=?", [$uid]);

    notify(
        $uid,
        '✅ Payment confirmed — you can now start "' . $c['title'] . '".',
        'learn.php?course=' . $cid
    );

    notify(
        $c['instructor_id'],
        $l['name'] . ' enrolled in ' . $c['title'],
        'course_edit.php?id=' . $cid . '&tab=learners'
    );
}

// ---- Video and media helpers ----
function embed_url($u) {
    if (preg_match(
        '~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([\w-]{11})~',
        $u,
        $m
    )) {
        return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0';
    }

    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $u, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }

    return null;
}

function render_media($l, $track = false) {
    $src = $l['file'] ?: $l['url'];
    $t = $l['type'];

    if (!$src) {
        return '';
    }

    $open = '<p class="muted" style="margin-top:8px;font-size:13px">
        Having trouble?
        <a href="' . e($src) . '" target="_blank" rel="noopener">Open in a new tab</a>
    </p>';

    $wrap = function($inner, $prov) use ($l, $track) {
        return $track
            ? '<div id="lessonPlayer" data-provider="' . $prov .
              '" data-lesson="' . (int)$l['id'] .
              '" data-csrf="' . csrf() . '">' . $inner . '</div>'
            : $inner;
    };

    if ($t === 'video') {
        $em = $l['file'] ? null : embed_url($src);

        if ($em) {
            $prov = strpos($em, 'vimeo') !== false ? 'vimeo' : 'youtube';

            if ($track && $prov === 'youtube') {
                $o = (
                    !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
                    ? 'https'
                    : 'http'
                ) . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

                $em .= '&enablejsapi=1&origin=' . rawurlencode($o);
            }

            $f = '<div class="vwrap">
                <iframe id="lessonFrame" src="' . e($em) . '"
                title="Lesson video"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                allowfullscreen
                referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>';

            return $wrap($f, $prov) . $open;
        }

        $ext = strtolower(pathinfo(parse_url($src, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        $mime = [
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'mov' => 'video/mp4'
        ][$ext] ?? 'video/mp4';

        return $wrap(
            '<div class="vwrap">
                <video id="lessonMedia" controls playsinline preload="metadata" controlsList="nodownload">
                    <source src="' . e($src) . '" type="' . $mime . '">
                    Your browser cannot play this video.
                </video>
            </div>',
            'html5'
        ) . $open;
    }

    if ($t === 'audio') {
        return $wrap(
            '<audio id="lessonMedia" controls style="width:100%;margin-top:10px">
                <source src="' . e($src) . '">
            </audio>',
            'html5'
        );
    }

    if ($t === 'image') {
        return '<img src="' . e($src) . '" alt="" style="border-radius:12px;margin-top:10px">';
    }

    if ($t === 'pdf') {
        return '<div class="vwrap pdf">
            <iframe src="' . e($src) . '" title="PDF"></iframe>
        </div>' . $open;
    }

    return '<p style="margin-top:12px">
        <a class="btn" target="_blank" rel="noopener" href="' . e($src) . '">' .
        ($t === 'link' ? '🔗 Open link' : '⬇ Download file') .
        '</a>
    </p>';
}

function fsize($rel) {
    $p = __DIR__ . '/../' . $rel;

    if (!$rel || !is_file($p)) {
        return '';
    }

    $b = filesize($p);

    return $b >= 1048576
        ? round($b / 1048576, 1) . ' MB'
        : max(1, round($b / 1024)) . ' KB';
}

function flash_html() {
    $o = '';

    foreach ($_SESSION['flash'] ?? [] as $f) {
        $o .= '<div class="flash toast ' . $f[1] . '">' .
            e(t($f[0])) .
            '<button type="button" onclick="this.parentNode.remove()">×</button>' .
            '</div>';
    }

    unset($_SESSION['flash']);

    return $o;
}

// ---- Live classes ----
function live_state($L) {
    if ($L['status'] === 'cancelled') return 'cancelled';
    if ($L['status'] === 'ended') return 'ended';

    $s = strtotime($L['start_at']);
    $e = $s + max(15, (int)$L['duration_min']) * 60;
    $n = time();

    if ($L['status'] === 'live') {
        return $n > $e + 7200 ? 'ended' : 'live';
    }

    if ($n < $s) return 'upcoming';

    return $n <= $e ? 'live' : 'ended';
}

function live_can_join($L) {
    $st = live_state($L);

    return $st === 'live'
        || ($st === 'upcoming' && strtotime($L['start_at']) - time() <= 900);
}

function live_scope($u) {
    if ($u['role'] === 'admin') {
        return ['1=1', []];
    }

    if ($u['role'] === 'instructor') {
        return ['c.instructor_id=?', [$u['id']]];
    }

    if ($u['role'] === 'organization') {
        return ['c.org_id=?', [$u['id']]];
    }

    return [
        "l.course_id IN (
            SELECT course_id FROM enrollments
            WHERE user_id=? AND status='active'
        )",
        [$u['id']]
    ];
}

function live_upcoming($u, $n = 3) {
    [$cond, $p] = live_scope($u);
    $o = [];

    foreach (all(
        "SELECT l.*, c.title ct
         FROM live_classes l
         JOIN courses c ON c.id=l.course_id
         WHERE $cond AND l.status IN ('scheduled','live')
         ORDER BY l.start_at LIMIT 40",
        $p
    ) as $r) {
        if (in_array(live_state($r), ['live', 'upcoming'])) {
            $o[] = $r;
        }

        if (count($o) >= $n) {
            break;
        }
    }

    return $o;
}

// ---- Organization subscriptions ----
function org_sub($oid) {
    return one(
        "SELECT * FROM org_subscriptions
         WHERE org_id=? AND status='active'
         AND start_at<=CURDATE() AND end_at>=CURDATE()
         ORDER BY end_at DESC LIMIT 1",
        [$oid]
    );
}

function org_state($o) {
    $s = $o['org_status'] ?? 'approved';

    if ($s === 'pending' || $s === 'rejected') {
        return $s;
    }

    if (org_sub($o['id'])) {
        return 'active';
    }

    if (one(
        "SELECT id FROM org_subscriptions WHERE org_id=? AND status='pending'",
        [$o['id']]
    )) {
        return 'payment_pending';
    }

    return one(
        "SELECT id FROM org_subscriptions WHERE org_id=? AND status='active'",
        [$o['id']]
    ) ? 'expired' : 'need_plan';
}

function org_usage($oid) {
    return [
        'learners' => (int)one(
            "SELECT COUNT(*) c FROM users WHERE org_id=? AND role='learner'",
            [$oid]
        )['c'],

        'courses' => (int)one(
            "SELECT COUNT(*) c FROM courses WHERE org_id=?",
            [$oid]
        )['c']
    ];
}

function org_can_add_learner($oid) {
    $s = org_sub($oid);

    return $s && (
        !$s['max_learners']
        || org_usage($oid)['learners'] < $s['max_learners']
    );
}

function org_can_add_course($oid) {
    $s = org_sub($oid);

    return $s && (
        !$s['max_courses']
        || org_usage($oid)['courses'] < $s['max_courses']
    );
}

function org_course_blocked($c) {
    return !empty($c['org_id']) && !org_sub($c['org_id']);
}

function sub_set_invoice($sid) {
    q(
        "UPDATE org_subscriptions SET invoice=? WHERE id=?",
        ['SUB-' . date('Ymd') . '-' . str_pad($sid, 4, '0', STR_PAD_LEFT), $sid]
    );
}

function org_period($oid, $billing) {
    $last = one(
        "SELECT MAX(end_at) d FROM org_subscriptions
         WHERE org_id=? AND status='active' AND end_at>=CURDATE()",
        [$oid]
    )['d'];

    $start = $last
        ? date('Y-m-d', strtotime($last . ' +1 day'))
        : date('Y-m-d');

    return [
        $start,
        date(
            'Y-m-d',
            strtotime($start . ($billing === 'yearly' ? ' +1 year' : ' +1 month') . ' -1 day')
        )
    ];
}

function org_activate($sid) {
    $s = one("SELECT * FROM org_subscriptions WHERE id=?", [$sid]);

    if (!$s) {
        return;
    }

    [$st, $en] = org_period($s['org_id'], $s['billing']);

    q(
        "UPDATE org_subscriptions
         SET status='active', start_at=?, end_at=?, confirmed_at=NOW()
         WHERE id=?",
        [$st, $en, $sid]
    );

    notify(
        $s['org_id'],
        '✅ Subscription active: ' . $s['plan_name'] .
        ' (' . $s['billing'] . ') until ' . fdate($en),
        'subscription.php'
    );
}

// ---- Tiny SVG charts ----
function svg_bars($labels, $values, $h = 190) {
    $n = count($values);

    if (!$n) {
        return '<p class="muted">No data yet.</p>';
    }

    $max = max(1, max($values));
    $w = 560;
    $pl = 10;
    $pb = 30;
    $pt = 24;
    $bw = ($w - $pl * 2) / $n;

    $o = '<svg class="svgchart" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="Bar chart">';

    foreach ([0, .5, 1] as $g) {
        $y = round($pt + ($h - $pt - $pb) * (1 - $g), 1);
        $o .= '<line x1="' . $pl . '" x2="' . ($w - $pl) .
            '" y1="' . $y . '" y2="' . $y . '" class="gl"/>';
    }

    foreach ($values as $i => $v) {
        $bh = ($h - $pt - $pb) * $v / $max;
        $x = $pl + $i * $bw + $bw * .2;
        $y = $h - $pb - $bh;
        $cx = round($x + $bw * .3, 1);

        $o .= '<rect x="' . round($x, 1) .
            '" y="' . round($y, 1) .
            '" width="' . round($bw * .6, 1) .
            '" height="' . max(3, round($bh, 1)) .
            '" rx="7" class="cbar"/>';

        $o .= '<text x="' . $cx . '" y="' . round($y - 7, 1) .
            '" text-anchor="middle" class="cval">' . (int)$v . '</text>';

        $o .= '<text x="' . $cx . '" y="' . ($h - 9) .
            '" text-anchor="middle" class="clab">' . e($labels[$i]) . '</text>';
    }

    return $o . '</svg>';
}

function svg_donut($parts) {
    $tot = array_sum(array_column($parts, 1));

    if (!$tot) {
        return '<p class="muted">No data yet.</p>';
    }

    $o = '<svg class="svgdonut" viewBox="0 0 42 42" role="img" aria-label="Donut chart">
        <circle cx="21" cy="21" r="15.9155" fill="none" class="ringbg" stroke-width="5"/>';

    $off = 25;

    foreach ($parts as $p) {
        $pc = $p[1] * 100 / $tot;

        $o .= '<circle cx="21" cy="21" r="15.9155" fill="none"
            stroke="' . $p[2] . '" stroke-width="5"
            stroke-dasharray="' . round($pc, 2) . ' ' . round(100 - $pc, 2) . '"
            stroke-dashoffset="' . round($off, 2) . '"/>';

        $off -= $pc;
    }

    return $o . '<text x="21" y="23.2" text-anchor="middle" class="dtot">' .
        (int)$tot . '</text></svg>';
}

// ---- URLs, rate limiting and login lockout ----
function base_url() {
    if (APP_URL !== '') {
        return rtrim(APP_URL, '/');
    }

    $https = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (
            isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'
        )
    );

    return ($https ? 'https' : 'http') .
        '://' .
        ($_SERVER['HTTP_HOST'] ?? 'localhost') .
        rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
}

function client_ip() {
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function rate_hit($key) {
    try {
        q(
            "INSERT INTO login_attempts(ip,email) VALUES(?,?)",
            [client_ip(), substr($key, 0, 150)]
        );
    } catch (Throwable $ex) {
        error_log('rate_hit: ' . $ex->getMessage());
    }
}

function rate_count($key, $mins) {
    try {
        return (int)one(
            "SELECT COUNT(*) c FROM login_attempts
             WHERE email=? AND ip=?
             AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [substr($key, 0, 150), client_ip(), (int)$mins]
        )['c'];

    } catch (Throwable $ex) {
        return 0;
    }
}

/** Minutes left if this e-mail (5 failures) or this address (20 failures) is locked out, else 0. */
function login_locked($email) {
    try {
        $e = one(
            "SELECT COUNT(*) c, MIN(created_at) f
             FROM login_attempts
             WHERE email=?
             AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [$email]
        );

        $i = one(
            "SELECT COUNT(*) c, MIN(created_at) f
             FROM login_attempts
             WHERE ip=?
             AND email NOT IN ('reset','register')
             AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [client_ip()]
        );

        $first = $e['c'] >= 5
            ? $e['f']
            : ($i['c'] >= 20 ? $i['f'] : null);

        return $first
            ? max(1, 15 - (int)floor((time() - strtotime($first)) / 60))
            : 0;

    } catch (Throwable $ex) {
        return 0;
    }
}

function login_failed($email) {
    rate_hit($email);

    try {
        q("DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    } catch (Throwable $ex) {
    }
}

function login_ok($email) {
    try {
        q("DELETE FROM login_attempts WHERE email=?", [$email]);
    } catch (Throwable $ex) {
    }
}

// ---- Load application modules ----
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/reminders.php';
require_once __DIR__ . '/lang.php';
