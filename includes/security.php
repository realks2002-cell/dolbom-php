<?php
/**
 * 보안 관련 함수
 */

/**
 * 주민번호 암호화
 */
function encrypt_ssn($ssn) {
    if (!$ssn) return null;
    
    $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default-encryption-key-change-this';
    $iv = substr(hash('sha256', $key), 0, 16);
    
    return base64_encode(openssl_encrypt($ssn, 'AES-256-CBC', $key, 0, $iv));
}

/**
 * 주민번호 복호화
 */
function decrypt_ssn($encrypted) {
    if (!$encrypted) return null;
    
    $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default-encryption-key-change-this';
    $iv = substr(hash('sha256', $key), 0, 16);
    
    return openssl_decrypt(base64_decode($encrypted), 'AES-256-CBC', $key, 0, $iv);
}

/**
 * 주민번호 마스킹 (표시용)
 */
function mask_ssn($ssn) {
    if (!$ssn) return '';
    
    // 복호화된 주민번호를 마스킹
    if (strlen($ssn) === 13 || strlen($ssn) === 14) {
        return substr($ssn, 0, 6) . '-' . substr($ssn, 6, 1) . '******';
    }
    
    return $ssn;
}

/**
 * CSRF 토큰 생성
 */
function csrf_token() {
    init_session();
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * CSRF 토큰 검증
 */
function verify_csrf_token($token) {
    init_session();
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * CSRF 토큰 HTML 필드
 */
function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * CSRF 토큰 검증 (POST 요청)
 */
function require_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            die('CSRF 토큰이 유효하지 않습니다. 페이지를 새로고침하고 다시 시도해주세요.');
        }
    }
}

/**
 * XSS 방어 (HTML 출력용)
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * SQL Injection 방어 (동적 쿼리용)
 * 주의: Prepared Statement 사용이 더 안전함
 */
function sanitize_sql_identifier($identifier) {
    // 영문, 숫자, 언더스코어만 허용
    return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
}

/**
 * 전화번호 정규화
 */
function normalize_phone($phone) {
    return preg_replace('/[^0-9]/', '', $phone);
}

/**
 * 이메일 검증
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 비밀번호 강도 검증
 */
function is_strong_password($password, $minLength = 8) {
    if (strlen($password) < $minLength) {
        return false;
    }
    
    // 숫자, 영문 포함 권장 (현재는 길이만 체크)
    return true;
}

/**
 * 세션 고정 공격 방지
 */
function regenerate_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}
