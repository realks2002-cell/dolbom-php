<?php
/**
 * 암호화 키 설정
 * 프로덕션에서는 반드시 강력한 키로 변경!
 */

// 암호화 키 (환경 변수 우선)
// 프로덕션: hosting.php에서 강력한 키로 설정
if (!defined('ENCRYPTION_KEY')) {
    define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'CHANGE-THIS-TO-STRONG-32-CHAR-KEY-IN-PRODUCTION-ENV');
}

// 키 길이 확인
if (strlen(ENCRYPTION_KEY) < 32) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        trigger_error('ENCRYPTION_KEY는 최소 32자 이상이어야 합니다.', E_USER_WARNING);
    }
}
