<?php
/**
 * 공통 헬퍼
 */

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config/app.php';
}

/**
 * 세션 초기화 (저장 경로: storage/sessions — xampp/tmp 권한 이슈 회피)
 */
function init_session(): void {
    $dir = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (session_status() === PHP_SESSION_NONE) {
        session_save_path($dir);
        session_start();
    }
}

/**
 * UUID v4 생성 (users.id 등)
 */
function uuid4(): string {
    $b = random_bytes(16);
    $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
    $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

/**
 * 경로로 리다이렉트 (BASE_PATH 반영)
 */
function redirect(string $path): never {
    $base = rtrim(BASE_PATH ?? '', '/');
    $url = $base === '' ? $path : $base . $path;
    if (!str_starts_with($url, '/')) {
        $url = '/' . $url;
    }
    header('Location: ' . $url, true, 302);
    exit;
}

/**
 * 관리자 권한 체크 (admins 테이블 또는 users 테이블의 ADMIN 역할 허용)
 */
function require_admin(): void {
    if (!defined('ROOT_PATH')) {
        require_once dirname(__DIR__) . '/config/app.php';
    }
    // auth.php는 호출하는 곳에서 이미 로드되어야 함 (순환 참조 방지)
    
    // admins 테이블에서 세션 확인
    if (!empty($_SESSION['admin_db_id'])) {
        $pdo = require dirname(__DIR__) . '/database/connect.php';
        $st = $pdo->prepare('SELECT id, admin_id FROM admins WHERE id = ?');
        $st->execute([$_SESSION['admin_db_id']]);
        $admin = $st->fetch();
        if ($admin) {
            return; // admins 테이블 인증 성공
        }
    }
    
    // users 테이블의 ADMIN 역할 확인
    global $currentUser, $userRole;
    if ($currentUser && $userRole === ROLE_ADMIN) {
        return; // users 테이블 ADMIN 인증 성공
    }
    
    // 둘 다 실패하면 관리자 로그인 페이지로 리다이렉트
    redirect('/admin.php');
}
