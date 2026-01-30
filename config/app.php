<?php
/**
 * 앱 전역 설정
 * - base URL, 환경, DB 설정 등
 */

// 에러 리포트 (개발 시에만 표시)
define('APP_DEBUG', true);
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// 기본 경로
define('ROOT_PATH', dirname(__DIR__));
define('APP_NAME', 'Hangbok77');

// Base URL (XAMPP: /dolbom_php, PHP 내장 서버: 빈 문자열)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
// index.php가 포함된 경우 제거
$script = preg_replace('#/index\.php.*$#', '', $script);
if ($script === '\\' || $script === '.' || $script === '/') {
    $script = '';
}
define('BASE_PATH', $script === '' ? '' : rtrim($script, '/'));
define('BASE_URL', rtrim($protocol . '://' . $host . BASE_PATH, '/'));

// DB 설정 (MariaDB/MySQL) - 추후 연동
define('DB_HOST', 'localhost');
define('DB_NAME', 'dolbom');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// VWorld 주소 검색 API
define('VWORLD_API_KEY', '8C916578-A500-33BD-9DFA-E4F6F5C52E42');

// 역할 상수 (PRD users.role)
define('ROLE_CUSTOMER', 'CUSTOMER');
define('ROLE_MANAGER', 'MANAGER');
define('ROLE_ADMIN', 'ADMIN');

// API (매니저 앱 / Vercel용)
define('API_JWT_SECRET', getenv('API_JWT_SECRET') ?: 'dolbom-dev-secret-change-in-production');
define('API_CORS_ORIGINS', getenv('API_CORS_ORIGINS') ?: '*'); // 예: https://manager-xxx.vercel.app,https://localhost:5173

// 토스페이먼츠 - 결제위젯용 키 (gck_, gsk_)
// API 개별 연동 키(ck_, sk_)는 결제위젯에서 사용 불가
define('TOSS_CLIENT_KEY', getenv('TOSS_CLIENT_KEY') ?: 'test_gck_docs_Ovk5rk1EwkEbP0W43n07xlzm');
define('TOSS_SECRET_KEY', getenv('TOSS_SECRET_KEY') ?: 'test_gsk_docs_OaPz8L5KdmQXkzRz3y47BMw6');

// FCM (Firebase Cloud Messaging) 서버 키
// Firebase Console > 프로젝트 설정 > 클라우드 메시징 > 서버 키
define('FCM_SERVER_KEY', getenv('FCM_SERVER_KEY') ?: 'BA3xHSY_CkXPMz0bhixEwIEFrQINtv_Zos3SsBwXa8e2wr4-vZ8hwzUaWwPI-wq1uQ7gspzvS9CqUlE_LRZsczY');
