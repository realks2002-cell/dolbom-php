<?php
/**
 * 서버 환경 확인 스크립트
 */
require_once 'config/app.php';

echo "<h1>서버 환경 진단</h1>";
echo "<pre>";

echo "=== PHP 정보 ===\n";
echo "PHP 버전: " . phpversion() . "\n";
echo "서버: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "호스트: " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "\n\n";

echo "=== 필수 확장 모듈 ===\n";
$extensions = ['curl', 'pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo str_pad($ext, 15) . ": " . ($loaded ? "✅ 사용 가능" : "❌ 사용 불가") . "\n";
}

echo "\n=== curl 상세 정보 ===\n";
if (function_exists('curl_version')) {
    $curlInfo = curl_version();
    echo "curl 버전: " . $curlInfo['version'] . "\n";
    echo "SSL 버전: " . $curlInfo['ssl_version'] . "\n";
    echo "지원 프로토콜: " . implode(', ', $curlInfo['protocols']) . "\n";
} else {
    echo "❌ curl 사용 불가\n";
}

echo "\n=== 토스페이먼츠 설정 ===\n";
echo "TOSS_CLIENT_KEY: ";
if (defined('TOSS_CLIENT_KEY')) {
    $key = TOSS_CLIENT_KEY;
    echo substr($key, 0, 15) . "... ";
    if (strpos($key, 'test_') === 0) {
        echo "(⚠️  테스트 키)\n";
    } else if (strpos($key, 'live_') === 0) {
        echo "(✅ 라이브 키)\n";
    } else {
        echo "(❓ 알 수 없음)\n";
    }
} else {
    echo "❌ 설정 안 됨\n";
}

echo "TOSS_SECRET_KEY: ";
if (defined('TOSS_SECRET_KEY')) {
    $key = TOSS_SECRET_KEY;
    echo substr($key, 0, 15) . "... ";
    if (strpos($key, 'test_') === 0) {
        echo "(⚠️  테스트 키)\n";
    } else if (strpos($key, 'live_') === 0) {
        echo "(✅ 라이브 키)\n";
    } else {
        echo "(❓ 알 수 없음)\n";
    }
} else {
    echo "❌ 설정 안 됨\n";
}

echo "\n=== 외부 API 접근 테스트 ===\n";
if (function_exists('curl_init')) {
    // 1. 토스페이먼츠 API
    echo "1. 토스페이먼츠 API: ";
    $ch = curl_init('https://api.tosspayments.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode > 0) {
        echo "✅ 접근 가능 (HTTP $httpCode)\n";
    } else {
        echo "❌ 접근 불가 ($error)\n";
    }
    
    // 2. VWorld API
    echo "2. VWorld API: ";
    $ch = curl_init('http://api.vworld.kr');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode > 0) {
        echo "✅ 접근 가능 (HTTP $httpCode)\n";
    } else {
        echo "❌ 접근 불가 ($error)\n";
    }
} else {
    echo "❌ curl을 사용할 수 없어 테스트 불가\n";
}

echo "\n=== 데이터베이스 연결 ===\n";
try {
    $pdo = require 'database/connect.php';
    echo "✅ DB 연결 성공\n";
    
    // 테이블 확인
    $tables = ['users', 'service_requests', 'payments', 'bookings'];
    echo "\n테이블 존재 여부:\n";
    foreach ($tables as $table) {
        $st = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $st->rowCount() > 0;
        echo "  " . str_pad($table, 20) . ": " . ($exists ? "✅" : "❌") . "\n";
    }
} catch (Exception $e) {
    echo "❌ DB 연결 실패: " . $e->getMessage() . "\n";
}

echo "\n=== 진단 완료 ===\n";
echo "</pre>";

echo "<h2>권장 사항</h2>";
echo "<ul>";
if (strpos(TOSS_SECRET_KEY, 'test_') === 0) {
    echo "<li><strong>⚠️  토스페이먼츠 라이브 키로 변경 필요!</strong></li>";
}
if (!function_exists('curl_init')) {
    echo "<li><strong>❌ curl 확장 모듈 활성화 필요!</strong></li>";
}
echo "<li>다음 단계: <a href='test_refund_api.php'>환불 API 테스트</a></li>";
echo "</ul>";
?>
