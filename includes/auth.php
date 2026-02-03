<?php
/**
 * 인증 공통 로직
 * - 세션 시작, 현재 로그인 사용자 조회
 * - $currentUser, $userRole 설정 (layout/header에서 사용)
 */
if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config/app.php';
}
require_once __DIR__ . '/helpers.php';

$currentUser = null;
$userRole = null;

init_session();

if (!empty($_SESSION['user_id'])) {
    try {
        $pdo = require dirname(__DIR__) . '/database/connect.php';
        $st = $pdo->prepare('SELECT id, email, name, role, phone, address, address_detail FROM users WHERE id = ? AND is_active = 1');
        $st->execute([$_SESSION['user_id']]);
        $row = $st->fetch();
        if ($row) {
            $currentUser = $row;
            $userRole = $row['role'];
        } else {
            // 사용자가 DB에 없으면 세션 초기화
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
        }
    } catch (Exception $e) {
        // DB 연결 오류 시 로그만 남기고 계속 진행 (비로그인 사용자는 정상 접근 가능)
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('인증 DB 조회 오류: ' . $e->getMessage());
        }
        // 세션 초기화
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

/**
 * API 관리자 권한 확인
 */
function require_admin_api() {
    if (empty($_SESSION['admin_id'])) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Admin authentication required']);
        exit;
    }
}
