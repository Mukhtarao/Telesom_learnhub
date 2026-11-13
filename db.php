
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $databaseUrl = getenv("DATABASE_URL");

    if (!$databaseUrl) {
        throw new Exception("DATABASE_URL is not configured.");
    }

    $db = parse_url($databaseUrl);

    $host = $db["host"];
    $username = urldecode($db["user"]);
    $password = urldecode($db["pass"]);
    $database = ltrim($db["path"], "/");
    $port = $db["port"] ?? 3306;

    $conn = new mysqli(
        $host,
        $username,
        $password,
        $database,
        $port
    );

    $conn->set_charset("utf8mb4");

} catch (Throwable $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed.");
}
?>
