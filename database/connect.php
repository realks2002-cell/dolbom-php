<?php
/**
 * DB 연결 (PDO)
 * config/app.php 로드 후 require
 */
if (!defined('DB_HOST')) {
    require_once dirname(__DIR__) . '/config/app.php';
}

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    DB_HOST,
    DB_NAME,
    DB_CHARSET
);
$opts = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
} catch (PDOException $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        throw $e;
    }
    http_response_code(500);
    exit('DB 연결에 실패했습니다.');
}

return $pdo;
