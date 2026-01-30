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
    http_response_code(500);
    // 디버그 모드이거나 호스팅 환경에서도 기본 오류 정보 표시
    if (defined('APP_DEBUG') && APP_DEBUG) {
        throw $e;
    }
    // 호스팅 환경에서도 기본 오류 메시지 표시 (보안을 위해 제한적)
    $errorMsg = 'DB 연결에 실패했습니다.';
    // 호스팅 환경에서 hosting.php 파일 존재 여부 확인
    $hostingConfig = dirname(__DIR__) . '/config/hosting.php';
    if (!file_exists($hostingConfig)) {
        $errorMsg .= ' (hosting.php 파일이 없습니다. config/hosting.php.example을 참고하여 생성하세요.)';
    } else {
        // DB 설정값 확인 (보안을 위해 값은 표시하지 않음)
        $errorMsg .= ' (DB 설정을 확인하세요: 호스트=' . DB_HOST . ', DB명=' . DB_NAME . ', 사용자=' . DB_USER . ')';
    }
    exit($errorMsg);
}

return $pdo;
