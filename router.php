<?php
/**
 * PHP 내장 서버용 라우터
 * 사용: php -S localhost:8000 router.php
 * ( Apache + .htaccess 대신 로컬 테스트용 )
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = trim($uri, '/');

// 정적 파일이면 그대로 전달
if ($uri !== '' && file_exists(__DIR__ . '/' . $uri) && !is_dir(__DIR__ . '/' . $uri)) {
    return false;
}

$_GET['route'] = $uri;
require __DIR__ . '/index.php';
