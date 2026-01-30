<?php
/**
 * 앱 전역 설정
 * - base URL, 환경, DB 설정 등
 */

// 호스팅 환경 설정 파일이 있으면 먼저 로드
// 호스팅 서버에서는 hosting.php 파일을 생성하여 사용
// 예제 파일: hosting.php.example 참고
// 로컬에서는 hosting.php가 있으면 DB 정보가 호스팅용일 수 있으므로 확인 필요
$hostingConfig = __DIR__ . '/hosting.php';
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = in_array($httpHost, ['localhost', '127.0.0.1', 'localhost:8000', 'localhost:3000']) 
    || strpos($httpHost, 'localhost') === 0 
    || strpos($httpHost, '127.0.0.1') === 0;

// 로컬이 아니고 hosting.php가 있으면 로드
if (!$isLocal && file_exists($hostingConfig)) {
    require_once $hostingConfig;
} elseif (!$isLocal && !file_exists($hostingConfig)) {
    // 호스팅 환경인데 hosting.php가 없으면 경고 (디버그 모드에서만)
    if (getenv('APP_DEBUG') === 'true' || (isset($_GET['debug']) && $_GET['debug'] === '1')) {
        error_log('Warning: 호스팅 환경(' . $httpHost . ')에서 hosting.php 파일을 찾을 수 없습니다.');
    }
}

// 에러 리포트 (환경에 따라 자동 설정)
// 로컬: APP_DEBUG=true, 프로덕션: APP_DEBUG=false
// hosting.php에서 이미 정의했으면 건너뛰기
if (!defined('APP_DEBUG')) {
    $isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', 'localhost:8000', 'localhost:3000']);
    define('APP_DEBUG', getenv('APP_DEBUG') !== false ? (bool)getenv('APP_DEBUG') : $isLocal);
}

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// 기본 경로
define('ROOT_PATH', dirname(__DIR__));
define('APP_NAME', 'Hangbok77');

// Base URL 자동 감지
// 카페24 호스팅: 도메인 기반으로 자동 설정됨
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// 환경 변수로 BASE_URL이 설정되어 있으면 사용
if (getenv('BASE_URL')) {
    define('BASE_URL', rtrim(getenv('BASE_URL'), '/'));
    define('BASE_PATH', '');
} else {
    // 자동 감지
    $script = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    // index.php가 포함된 경우 제거
    $script = preg_replace('#/index\.php.*$#', '', $script);
    if ($script === '\\' || $script === '.' || $script === '/') {
        $script = '';
    }
    define('BASE_PATH', $script === '' ? '' : rtrim($script, '/'));
    define('BASE_URL', rtrim($protocol . '://' . $host . BASE_PATH, '/'));
}

// DB 설정 (MariaDB/MySQL) - 환경 변수 우선, 없으면 기본값 사용
// 카페24 호스팅: 호스팅 관리자에서 DB 정보 확인 후 hosting.php에 설정
// hosting.php에서 이미 정의했으면 건너뛰기
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'dolbom');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
}

// VWorld 주소 검색 API
define('VWORLD_API_KEY', '8C916578-A500-33BD-9DFA-E4F6F5C52E42');

// 역할 상수 (PRD users.role)
define('ROLE_CUSTOMER', 'CUSTOMER');
define('ROLE_MANAGER', 'MANAGER');
define('ROLE_ADMIN', 'ADMIN');

// API (매니저 앱 / Vercel용)
// 프로덕션에서는 반드시 강력한 시크릿 키 사용
define('API_JWT_SECRET', getenv('API_JWT_SECRET') ?: (APP_DEBUG ? 'dolbom-dev-secret-change-in-production' : 'CHANGE-THIS-TO-STRONG-SECRET-KEY-IN-PRODUCTION'));
// CORS: 프로덕션에서는 특정 도메인만 허용
define('API_CORS_ORIGINS', getenv('API_CORS_ORIGINS') ?: (APP_DEBUG ? '*' : BASE_URL));

// 토스페이먼츠 - 결제위젯용 키 (gck_, gsk_)
// API 개별 연동 키(ck_, sk_)는 결제위젯에서 사용 불가
define('TOSS_CLIENT_KEY', getenv('TOSS_CLIENT_KEY') ?: 'test_gck_docs_Ovk5rk1EwkEbP0W43n07xlzm');
define('TOSS_SECRET_KEY', getenv('TOSS_SECRET_KEY') ?: 'test_gsk_docs_OaPz8L5KdmQXkzRz3y47BMw6');

// VAPID 키 (Pure Web Push용)
// scripts/generate_correct_vapid.html에서 생성 (올바른 형식)
// 공개 키: 브라우저에서 사용 (87자), 비공개 키: 서버에서만 사용
define('VAPID_PUBLIC_KEY', getenv('VAPID_PUBLIC_KEY') ?: 'BLO2sHNDJK-dAjLssyELl1pjJJ9Q9cQm7cp8cpIo7ghbQHC-Y5kUfr9A4dQTiRoMg8JQ9mcxysNscnJdKi6uKpo');
define('VAPID_PRIVATE_KEY', getenv('VAPID_PRIVATE_KEY') ?: '-jGcSpEtexPOHtnOaxBTIKvkgkgZuD34JdpKhFg9y4Y');

// VAPID subject (mailto: 또는 https://)
define('VAPID_SUBJECT', 'mailto:admin@travel23.mycafe24.com');

// Vue.js 앱 URL (매니저 앱)
// 호스팅 환경에서는 hosting.php에서 설정
if (!defined('VITE_APP_URL')) {
    define('VITE_APP_URL', getenv('VITE_APP_URL') ?: (APP_DEBUG ? 'http://localhost:3000' : BASE_URL . '/manager-app'));
}
