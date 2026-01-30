<?php
/**
 * 매니저 계정 확인 페이지 (디버깅용)
 * URL: /manager/check-manager?phone=01034061921&password=test123
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

$phone = $_GET['phone'] ?? '';
$password = $_GET['password'] ?? '';

if ($phone) {
    $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
    
    // 전화번호 정규화
    $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
    
    $st = $pdo->prepare('SELECT id, name, phone, password_hash FROM managers');
    $st->execute();
    $managers = $st->fetchAll();
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>매니저 계정 확인</title>";
    echo "<style>body{font-family:sans-serif;padding:20px;max-width:800px;margin:0 auto;} .success{color:green;} .error{color:red;} .info{background:#f0f0f0;padding:10px;margin:10px 0;border-radius:5px;}</style>";
    echo "</head><body>";
    
    echo "<h1>매니저 계정 확인</h1>";
    echo "<p><strong>검색 전화번호:</strong> {$phone} (정규화: {$normalizedPhone})</p>";
    echo "<hr>";
    
    $found = false;
    foreach ($managers as $m) {
        $dbPhone = preg_replace('/[^0-9]/', '', $m['phone']);
        if ($dbPhone === $normalizedPhone) {
            $found = true;
            echo "<div class='info'>";
            echo "<h2 class='success'>✓ 매니저 찾음</h2>";
            echo "<p><strong>ID:</strong> {$m['id']}</p>";
            echo "<p><strong>이름:</strong> {$m['name']}</p>";
            echo "<p><strong>전화번호:</strong> {$m['phone']}</p>";
            echo "<p><strong>비밀번호 해시:</strong> " . (empty($m['password_hash']) ? '<span class="error">없음</span>' : '<span class="success">있음</span>') . "</p>";
            
            // 비밀번호 테스트
            if ($password && !empty($m['password_hash'])) {
                echo "<hr>";
                echo "<h3>비밀번호 검증 테스트</h3>";
                echo "<p><strong>입력한 비밀번호:</strong> " . htmlspecialchars($password) . "</p>";
                
                $verifyResult = password_verify($password, $m['password_hash']);
                if ($verifyResult) {
                    echo "<p class='success'><strong>✓ 비밀번호 일치!</strong></p>";
                } else {
                    echo "<p class='error'><strong>✗ 비밀번호 불일치</strong></p>";
                    echo "<p><small>비밀번호 해시: " . htmlspecialchars(substr($m['password_hash'], 0, 30)) . "...</small></p>";
                }
            } elseif ($password) {
                echo "<p class='error'>비밀번호 해시가 없어서 검증할 수 없습니다.</p>";
            } else {
                echo "<p><small>비밀번호 테스트: URL에 &password=비밀번호 추가</small></p>";
            }
            
            echo "</div>";
            break;
        }
    }
    
    if (!$found) {
        echo "<h2 class='error'>✗ 매니저를 찾을 수 없습니다</h2>";
        echo "<h3>등록된 모든 매니저:</h3>";
        echo "<ul>";
        foreach ($managers as $m) {
            echo "<li>{$m['phone']} - {$m['name']} (ID: {$m['id']})</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<p><a href='/manager/login'>로그인 페이지로 돌아가기</a></p>";
    echo "</body></html>";
} else {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>매니저 계정 확인</title></head><body>";
    echo "<h1>매니저 계정 확인</h1>";
    echo "<p>URL에 phone 파라미터를 추가하세요:</p>";
    echo "<ul>";
    echo "<li><code>?phone=01034061921</code> - 계정 확인</li>";
    echo "<li><code>?phone=01034061921&password=비밀번호</code> - 비밀번호 테스트</li>";
    echo "</ul>";
    echo "</body></html>";
}
