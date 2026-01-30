<?php
/**
 * 매니저 로그아웃
 * URL: /manager/logout
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

init_session();

// 세션 변수 제거
unset($_SESSION['manager_id']);
unset($_SESSION['manager_name']);
unset($_SESSION['manager_phone']);

// 세션 데이터 완전 초기화
$_SESSION = [];

// 세션 쿠키 삭제
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 세션 저장 및 닫기
session_write_close();

// 세션 완전 삭제
session_destroy();

// 리다이렉트
$base = rtrim(BASE_PATH ?? '', '/');
$url = $base === '' ? '/manager/login' : $base . '/manager/login';
if (!str_starts_with($url, '/')) {
    $url = '/' . $url;
}

header('Location: ' . $url, true, 302);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
exit;
