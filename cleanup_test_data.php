<?php
/**
 * 테스트 데이터 정리 스크립트
 */
require_once 'config/app.php';
require_once 'database/connect.php';

$pdo = require 'database/connect.php';

echo "<!DOCTYPE html>";
echo "<html lang='ko'>";
echo "<head><meta charset='UTF-8'><title>테스트 데이터 정리</title>";
echo "<style>
body { font-family: 'Noto Sans KR', sans-serif; padding: 20px; background: #f5f5f5; }
.container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
h1 { color: #ef4444; }
.result { padding: 10px; margin: 10px 0; border-left: 3px solid #10b981; background: #ecfdf5; }
</style></head>";
echo "<body><div class='container'>";

echo "<h1>🗑️ 테스트 데이터 정리</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // test_로 시작하는 이메일의 사용자 ID 조회
        $st = $pdo->query("SELECT id FROM users WHERE email LIKE 'test_%'");
        $testUserIds = $st->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($testUserIds) > 0) {
            $placeholders = implode(',', array_fill(0, count($testUserIds), '?'));
            
            // 1. reviews 삭제
            $st = $pdo->prepare("DELETE FROM reviews WHERE customer_id IN ($placeholders)");
            $deleted1 = $st->execute($testUserIds) ? $st->rowCount() : 0;
            echo "<div class='result'>리뷰 삭제: {$deleted1}개</div>";
            
            // 2. applications 삭제
            $st = $pdo->prepare("DELETE FROM applications WHERE manager_id IN ($placeholders)");
            $deleted2 = $st->execute($testUserIds) ? $st->rowCount() : 0;
            echo "<div class='result'>매니저 지원 삭제: {$deleted2}개</div>";
            
            // 3. bookings 삭제
            $st = $pdo->prepare("DELETE FROM bookings WHERE manager_id IN ($placeholders)");
            $deleted3 = $st->execute($testUserIds) ? $st->rowCount() : 0;
            echo "<div class='result'>매칭 삭제: {$deleted3}개</div>";
            
            // 4. payments 삭제
            $st = $pdo->prepare("DELETE FROM payments WHERE customer_id IN ($placeholders)");
            $deleted4 = $st->execute($testUserIds) ? $st->rowCount() : 0;
            echo "<div class='result'>결제 삭제: {$deleted4}개</div>";
            
            // 5. service_requests 삭제
            $st = $pdo->prepare("DELETE FROM service_requests WHERE customer_id IN ($placeholders)");
            $deleted5 = $st->execute($testUserIds) ? $st->rowCount() : 0;
            echo "<div class='result'>서비스 요청 삭제: {$deleted5}개</div>";
            
            // 6. managers 삭제
            $st = $pdo->prepare("DELETE FROM managers WHERE id IN ($placeholders)");
            $deleted6 = $st->execute($testUserIds) ? $st->rowCount() : 0;
            echo "<div class='result'>매니저 프로필 삭제: {$deleted6}개</div>";
            
            // 7. users 삭제
            $st = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
            $deleted7 = $st->execute($testUserIds) ? $st->rowCount() : 0;
            echo "<div class='result'>사용자 삭제: {$deleted7}개</div>";
            
            $pdo->commit();
            
            echo "<h2 style='color: #10b981;'>✅ 테스트 데이터 정리 완료</h2>";
            echo "<p>총 " . count($testUserIds) . "명의 테스트 사용자와 관련 데이터가 삭제되었습니다.</p>";
        } else {
            echo "<p>삭제할 테스트 데이터가 없습니다.</p>";
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "<div style='color: #ef4444;'>오류 발생: " . $e->getMessage() . "</div>";
    }
    
    echo "<br><a href='test_platform.php' style='display: inline-block; background: #2563eb; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none;'>다시 테스트하기</a>";
    
} else {
    echo "<p>POST 요청이 필요합니다.</p>";
    echo "<a href='test_platform.php'>테스트 페이지로 돌아가기</a>";
}

echo "</div></body></html>";
?>
