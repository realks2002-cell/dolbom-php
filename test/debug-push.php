<?php
/**
 * 푸시 알림 디버깅 페이지
 */
require_once dirname(__DIR__) . '/config/app.php';

header('Content-Type: application/json; charset=utf-8');

$checks = [];

// 1. VAPID 키 확인
$checks['vapid_public_key_exists'] = defined('VAPID_PUBLIC_KEY') && !empty(VAPID_PUBLIC_KEY);
$checks['vapid_private_key_exists'] = defined('VAPID_PRIVATE_KEY') && !empty(VAPID_PRIVATE_KEY);
$checks['vapid_public_key_length'] = defined('VAPID_PUBLIC_KEY') ? strlen(VAPID_PUBLIC_KEY) : 0;
$checks['vapid_private_key_length'] = defined('VAPID_PRIVATE_KEY') ? strlen(VAPID_PRIVATE_KEY) : 0;

// 2. PHP 확장 확인
$checks['php_version'] = PHP_VERSION;
$checks['openssl_loaded'] = extension_loaded('openssl');
$checks['curl_loaded'] = extension_loaded('curl');
$checks['mbstring_loaded'] = extension_loaded('mbstring');
$checks['gmp_loaded'] = extension_loaded('gmp');

// 3. Composer/라이브러리 확인
$vendorPath = dirname(__DIR__) . '/vendor/autoload.php';
$checks['vendor_exists'] = file_exists($vendorPath);
$checks['webpush_lib_exists'] = file_exists(dirname(__DIR__) . '/includes/webpush_lib.php');

if ($checks['vendor_exists']) {
    require_once $vendorPath;
    $checks['webpush_class_exists'] = class_exists('Minishlink\WebPush\WebPush');
} else {
    $checks['webpush_class_exists'] = false;
}

// 4. DB 연결 및 구독 확인
try {
    $pdo = require dirname(__DIR__) . '/database/connect.php';
    $checks['db_connected'] = true;

    // 구독 수 확인
    $stmt = $pdo->query("SELECT COUNT(*) FROM manager_device_tokens WHERE is_active = 1");
    $checks['active_subscriptions'] = (int)$stmt->fetchColumn();

    // device_token 컬럼 타입 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM manager_device_tokens LIKE 'device_token'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    $checks['device_token_type'] = $column['Type'] ?? 'unknown';

    // 최근 구독 샘플
    $stmt = $pdo->query("SELECT device_token, created_at FROM manager_device_tokens WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($latest) {
        $tokenData = json_decode($latest['device_token'], true);
        $checks['latest_subscription'] = [
            'created_at' => $latest['created_at'],
            'has_endpoint' => isset($tokenData['endpoint']),
            'has_keys' => isset($tokenData['keys']),
            'endpoint_preview' => isset($tokenData['endpoint']) ? substr($tokenData['endpoint'], 0, 60) . '...' : 'N/A'
        ];
    } else {
        $checks['latest_subscription'] = null;
    }

} catch (Exception $e) {
    $checks['db_connected'] = false;
    $checks['db_error'] = $e->getMessage();
}

// 5. 권장 사항
$recommendations = [];

if (!$checks['vendor_exists']) {
    $recommendations[] = 'composer install 실행 후 vendor 폴더를 업로드하세요';
}

if (!$checks['webpush_class_exists'] && $checks['vendor_exists']) {
    $recommendations[] = 'minishlink/web-push 라이브러리가 설치되지 않았습니다';
}

if (!$checks['gmp_loaded']) {
    $recommendations[] = 'GMP 확장이 없으면 성능이 느릴 수 있습니다 (필수는 아님)';
}

if ($checks['active_subscriptions'] === 0) {
    $recommendations[] = '매니저가 대시보드에 접속하여 알림 권한을 허용해야 합니다';
}

if (strpos($checks['device_token_type'] ?? '', 'varchar') !== false) {
    $recommendations[] = 'device_token 컬럼을 TEXT로 변경하세요 (alter_device_token_to_text.sql 실행)';
}

if (!$checks['vapid_public_key_exists'] || !$checks['vapid_private_key_exists']) {
    $recommendations[] = 'VAPID 키를 config/app.php에 설정하세요';
}

// 6. 상태 요약
$status = 'error';
if ($checks['vendor_exists'] && $checks['webpush_class_exists'] && $checks['vapid_public_key_exists']) {
    if ($checks['active_subscriptions'] > 0) {
        $status = 'ready';
    } else {
        $status = 'no_subscriptions';
    }
} elseif ($checks['vapid_public_key_exists']) {
    $status = 'library_missing';
}

echo json_encode([
    'status' => $status,
    'status_message' => match($status) {
        'ready' => '푸시 알림 전송 준비 완료',
        'no_subscriptions' => '라이브러리 설치됨, 구독자 없음',
        'library_missing' => 'vendor 폴더 업로드 필요',
        default => '설정 필요'
    },
    'checks' => $checks,
    'recommendations' => $recommendations,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
