<?php
// config.php
function getBaseUrl() {
    if (empty($_SERVER['HTTP_HOST'])) {
        return 'http://localhost/visitor';
    }

    $scheme = 'http';
    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ) {
        $scheme = 'https';
    }

    $appRoot = realpath(__DIR__ . '/..');
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $basePath = '';

    if ($appRoot && $documentRoot && strpos($appRoot, $documentRoot) === 0) {
        $basePath = str_replace('\\', '/', substr($appRoot, strlen($documentRoot)));
    }

    return rtrim($scheme . '://' . $_SERVER['HTTP_HOST'] . $basePath, '/');
}

$base_url = getBaseUrl();

$host = 'localhost';
$dbname = 'visitor_detail';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database connection failed: ' . $e->getMessage());
    echo "Connection failed.";
    exit;
}
?>
