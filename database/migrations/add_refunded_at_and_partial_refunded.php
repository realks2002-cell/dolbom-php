<?php
/**
 * payments 테이블에 refunded_at 필드 추가 및 PARTIAL_REFUNDED 상태 추가
 * 브라우저에서 실행하거나 CLI에서 실행 가능
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/database/connect.php';

$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

// 브라우저 실행 여부 확인
$isBrowser = php_sapi_name() !== 'cli';

if ($isBrowser) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>환불 필드 추가</title>';
    echo '<style>body{font-family:"Noto Sans KR",sans-serif;padding:20px;background:#f5f5f5;}';
    echo '.container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;}';
    echo 'h1{color:#333;} .success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;}';
    echo 'pre{background:#f9fafb;padding:10px;border-radius:4px;overflow-x:auto;}</style></head><body><div class="container"><h1>환불 필드 추가</h1>';
}

function output($message, $isError = false, $isInfo = false) {
    global $isBrowser;
    if ($isBrowser) {
        $class = $isError ? 'error' : ($isInfo ? 'info' : 'success');
        echo "<div class='{$class}'>" . htmlspecialchars($message) . "</div>";
    } else {
        echo $message . "\n";
    }
}

try {
    // refunded_at 필드가 이미 있는지 확인
    $checkColumn = $pdo->query("SHOW COLUMNS FROM payments LIKE 'refunded_at'");
    $columnExists = $checkColumn->rowCount() > 0;
    
    if ($columnExists) {
        output("ℹ️  refunded_at 필드가 이미 존재합니다.", false, true);
    } else {
        output("✅ refunded_at 필드 추가 중...");
        
        // refunded_at 필드 추가
        $pdo->exec("
            ALTER TABLE `payments` 
            ADD COLUMN `refunded_at` TIMESTAMP NULL DEFAULT NULL AFTER `refund_reason`
        ");
        
        output("✅ refunded_at 필드가 추가되었습니다.");
    }
    
    // PARTIAL_REFUNDED 상태가 이미 있는지 확인
    $checkEnum = $pdo->query("SHOW COLUMNS FROM payments WHERE Field = 'status'");
    $enumRow = $checkEnum->fetch();
    $enumValues = $enumRow['Type'] ?? '';
    
    if (strpos($enumValues, 'PARTIAL_REFUNDED') !== false) {
        output("ℹ️  PARTIAL_REFUNDED 상태가 이미 존재합니다.", false, true);
    } else {
        output("✅ PARTIAL_REFUNDED 상태 추가 중...");
        
        // status ENUM에 PARTIAL_REFUNDED 추가
        $pdo->exec("
            ALTER TABLE `payments` 
            MODIFY COLUMN `status` ENUM('PENDING','SUCCESS','FAILED','CANCELLED','REFUNDED','PARTIAL_REFUNDED') NOT NULL DEFAULT 'PENDING'
        ");
        
        output("✅ PARTIAL_REFUNDED 상태가 추가되었습니다.");
    }
    
    // refunded_at 인덱스가 이미 있는지 확인
    $checkIndex = $pdo->query("SHOW INDEX FROM payments WHERE Key_name = 'idx_payments_refunded_at'");
    $indexExists = $checkIndex->rowCount() > 0;
    
    if ($indexExists) {
        output("ℹ️  refunded_at 인덱스가 이미 존재합니다.", false, true);
    } else {
        output("✅ refunded_at 인덱스 추가 중...");
        
        // refunded_at 인덱스 추가
        $pdo->exec("
            ALTER TABLE `payments`
            ADD KEY `idx_payments_refunded_at` (`refunded_at`)
        ");
        
        output("✅ refunded_at 인덱스가 추가되었습니다.");
    }
    
    output("\n✅ 마이그레이션이 완료되었습니다!");
    output("이제 환불 정보가 refunded_at 필드에 저장됩니다.");
    
} catch (Exception $e) {
    output("❌ 오류 발생: " . $e->getMessage(), true);
    output("파일: " . $e->getFile(), true);
    output("라인: " . $e->getLine(), true);
    if ($isBrowser) echo '</div></body></html>';
    exit(1);
}

if ($isBrowser) {
    echo '</div></body></html>';
}
