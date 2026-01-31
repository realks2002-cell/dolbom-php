<?php
/**
 * admin123 비밀번호 해시 생성 및 DB 업데이트
 * 브라우저에서 실행: http://localhost:8000/fix_admin_password.php
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';

$password = 'admin123';
$adminId = 'admin';

// 해시 생성
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h1>관리자 비밀번호 해시 생성</h1>";
echo "<p>비밀번호: <strong>{$password}</strong></p>";
echo "<p>생성된 해시: <code>{$hash}</code></p>";

// DB 업데이트
try {
    $pdo = require __DIR__ . '/database/connect.php';
    
    // 기존 계정 확인
    $st = $pdo->prepare('SELECT id, admin_id FROM admins WHERE admin_id = ?');
    $st->execute([$adminId]);
    $existing = $st->fetch();
    
    if ($existing) {
        // 업데이트
        $st = $pdo->prepare('UPDATE admins SET password_hash = ? WHERE admin_id = ?');
        $st->execute([$hash, $adminId]);
        echo "<p style='color: green;'>✓ 기존 계정 비밀번호가 업데이트되었습니다.</p>";
    } else {
        // 새로 생성
        $st = $pdo->prepare('INSERT INTO admins (admin_id, password_hash, created_at) VALUES (?, ?, NOW())');
        $st->execute([$adminId, $hash]);
        echo "<p style='color: green;'>✓ 새 관리자 계정이 생성되었습니다.</p>";
    }
    
    // 검증
    $st = $pdo->prepare('SELECT password_hash FROM admins WHERE admin_id = ?');
    $st->execute([$adminId]);
    $admin = $st->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        echo "<p style='color: green;'>✓ 비밀번호 검증 성공!</p>";
        echo "<p><a href='/admin.php'>관리자 로그인 페이지로 이동</a></p>";
    } else {
        echo "<p style='color: red;'>✗ 비밀번호 검증 실패</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>SQL 쿼리를 직접 실행하세요:</p>";
    echo "<pre>";
    echo "UPDATE `admins` SET `password_hash` = '{$hash}' WHERE `admin_id` = '{$adminId}';\n";
    echo "또는\n";
    echo "INSERT INTO `admins` (`admin_id`, `password_hash`, `created_at`) VALUES ('{$adminId}', '{$hash}', NOW());\n";
    echo "</pre>";
}
