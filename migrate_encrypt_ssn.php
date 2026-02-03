<?php
/**
 * 기존 주민번호 암호화 마이그레이션
 * 1회만 실행하고 삭제하세요
 */
require_once 'config/app.php';
require_once 'config/encryption.php';
require_once 'includes/security.php';

echo "<h1>주민번호 암호화 마이그레이션</h1>";
echo "<pre>";

$pdo = require 'database/connect.php';

try {
    // 평문 주민번호 조회 (암호화된 값은 base64이므로 길이가 50자 이상)
    $st = $pdo->query("SELECT id, ssn, name FROM managers WHERE ssn IS NOT NULL AND LENGTH(ssn) < 50");
    $managers = $st->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== 암호화 대상 매니저: " . count($managers) . "명 ===\n\n";
    
    if (count($managers) === 0) {
        echo "암호화할 주민번호가 없습니다.\n";
        echo "모든 주민번호가 이미 암호화되어 있습니다.\n";
    } else {
        $updateSt = $pdo->prepare("UPDATE managers SET ssn = ? WHERE id = ?");
        $success = 0;
        $failed = 0;
        
        foreach ($managers as $manager) {
            try {
                $encrypted = encrypt_ssn($manager['ssn']);
                $updateSt->execute([$encrypted, $manager['id']]);
                
                echo "✅ " . $manager['name'] . " (ID: " . substr($manager['id'], 0, 8) . "...)\n";
                echo "   원본: " . mask_ssn($manager['ssn']) . "\n";
                echo "   암호화: " . substr($encrypted, 0, 30) . "...\n\n";
                
                $success++;
            } catch (Exception $e) {
                echo "❌ " . $manager['name'] . " 실패: " . $e->getMessage() . "\n\n";
                $failed++;
            }
        }
        
        echo "\n=== 마이그레이션 완료 ===\n";
        echo "성공: {$success}명\n";
        echo "실패: {$failed}명\n";
        
        if ($success > 0) {
            echo "\n✅ 주민번호 암호화가 완료되었습니다!\n";
            echo "⚠️  이 파일(migrate_encrypt_ssn.php)을 삭제하세요.\n";
        }
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
