<?php
/**
 * payments 테이블에서 결제 완료된 요청들의 상태를 CONFIRMED로 업데이트
 * 브라우저에서 실행하거나 CLI에서 실행 가능
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/database/connect.php';

$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

// 브라우저 실행 여부 확인
$isBrowser = php_sapi_name() !== 'cli';

if ($isBrowser) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>상태 업데이트</title>';
    echo '<style>body{font-family:"Noto Sans KR",sans-serif;padding:20px;background:#f5f5f5;}';
    echo '.container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;}';
    echo 'h1{color:#333;} .success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;}';
    echo 'pre{background:#f9fafb;padding:10px;border-radius:4px;overflow-x:auto;}</style></head><body><div class="container"><h1>상태 업데이트</h1>';
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
    // payments 테이블에서 결제 완료된 요청 조회
    $st = $pdo->query("
        SELECT DISTINCT p.service_request_id, p.amount, p.paid_at, sr.status as current_status
        FROM payments p
        JOIN service_requests sr ON sr.id = p.service_request_id
        WHERE p.status = 'SUCCESS'
        AND p.service_request_id IS NOT NULL
        ORDER BY p.paid_at DESC
    ");
    $payments = $st->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($payments)) {
        output("❌ 결제 완료된 요청이 없습니다.", true);
        if ($isBrowser) echo '</div></body></html>';
        exit(1);
    }
    
    output("✅ 발견된 결제 완료 요청: " . count($payments) . "건");
    if ($isBrowser) echo '<pre>';
    
    $pdo->beginTransaction();
    
    $updated = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($payments as $payment) {
        try {
            $requestId = $payment['service_request_id'];
            $currentStatus = $payment['current_status'];
            
            // 이미 CONFIRMED 상태이거나, COMPLETED/CANCELLED 상태는 건너뛰기
            if ($currentStatus === 'CONFIRMED') {
                $skipped++;
                $message = sprintf(
                    "[%d/%d] ⏭️  건너뜀: %s (이미 CONFIRMED 상태)",
                    $updated + $skipped,
                    count($payments),
                    substr($requestId, 0, 8) . '...'
                );
                echo $message . ($isBrowser ? '<br>' : "\n");
                continue;
            }
            
            // COMPLETED나 CANCELLED는 건너뛰기 (이미 완료되거나 취소된 건)
            if ($currentStatus === 'COMPLETED' || $currentStatus === 'CANCELLED') {
                $skipped++;
                $message = sprintf(
                    "[%d/%d] ⏭️  건너뜀: %s (%s 상태 유지)",
                    $updated + $skipped,
                    count($payments),
                    substr($requestId, 0, 8) . '...',
                    $currentStatus
                );
                echo $message . ($isBrowser ? '<br>' : "\n");
                continue;
            }
            
            // 상태를 CONFIRMED로 업데이트
            $updateSt = $pdo->prepare("
                UPDATE service_requests 
                SET status = 'CONFIRMED', updated_at = NOW()
                WHERE id = ?
            ");
            
            $updateSt->execute([$requestId]);
            
            $updated++;
            $message = sprintf(
                "[%d/%d] ✅ 업데이트: %s (%s → CONFIRMED) - %s원",
                $updated + $skipped,
                count($payments),
                substr($requestId, 0, 8) . '...',
                $currentStatus,
                number_format($payment['amount'])
            );
            echo $message . ($isBrowser ? '<br>' : "\n");
            
        } catch (Exception $e) {
            $errors[] = [
                'request_id' => $payment['service_request_id'],
                'error' => $e->getMessage()
            ];
            $message = sprintf(
                "[%d/%d] ❌ 오류: %s - %s",
                $updated + $skipped + count($errors),
                count($payments),
                substr($payment['service_request_id'], 0, 8) . '...',
                $e->getMessage()
            );
            echo $message . ($isBrowser ? '<br>' : "\n");
        }
    }
    
    $pdo->commit();
    
    if ($isBrowser) echo '</pre>';
    
    output("\n✅ 총 {$updated}건의 상태가 CONFIRMED로 업데이트되었습니다.");
    if ($skipped > 0) {
        output("⏭️  {$skipped}건은 건너뛰었습니다 (이미 CONFIRMED이거나 COMPLETED/CANCELLED 상태).", false, true);
    }
    if (count($errors) > 0) {
        output("❌ " . count($errors) . "건에서 오류가 발생했습니다.", true);
        if ($isBrowser) {
            echo '<pre>';
            foreach ($errors as $err) {
                echo htmlspecialchars($err['request_id'] . ': ' . $err['error']) . "\n";
            }
            echo '</pre>';
        }
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    if ($isBrowser) echo '</pre>';
    output("❌ 오류 발생: " . $e->getMessage(), true);
    output("파일: " . $e->getFile(), true);
    output("라인: " . $e->getLine(), true);
    if ($isBrowser) echo '</div></body></html>';
    exit(1);
}

if ($isBrowser) {
    echo '</div></body></html>';
}
