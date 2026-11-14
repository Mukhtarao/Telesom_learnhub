
<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $databaseUrl = getenv('DATABASE_URL')
        ?: getenv('MYSQL_PUBLIC_URL')
        ?: getenv('MYSQL_URL');

    if (!$databaseUrl) {
        throw new RuntimeException('Database URL is missing.');
    }

    $db = parse_url($databaseUrl);

    if (!$db || empty($db['host']) || empty($db['user']) || !isset($db['pass'])) {
        throw new RuntimeException('Invalid database URL.');
    }

    $host = $db['host'];
    $username = rawurldecode($db['user']);
    $password = rawurldecode($db['pass']);
    $database = ltrim($db['path'] ?? '', '/');
    $port = $db['port'] ?? 3306;

    if ($database === '') {
        throw new RuntimeException('Database name is missing.');
    }

    $conn = new mysqli(
        $host,
        $username,
        $password,
        $database,
        $port
    );

    $conn->set_charset('utf8mb4');

} catch (Throwable $e) {
    error_log('Database connection error: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed.');
}
