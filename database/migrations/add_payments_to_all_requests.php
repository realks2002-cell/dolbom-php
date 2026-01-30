<?php
/**
 * 등록된 모든 service_requests에 대해 payments 테이블에 10000원씩 결제 정보 추가
 * 브라우저에서 실행하거나 CLI에서 실행 가능
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/database/connect.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

// 브라우저 실행 여부 확인
$isBrowser = php_sapi_name() !== 'cli';

if ($isBrowser) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>결제 정보 추가</title>';
    echo '<style>body{font-family:"Noto Sans KR",sans-serif;padding:20px;background:#f5f5f5;}';
    echo '.container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;}';
    echo 'h1{color:#333;} .success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;}';
    echo 'pre{background:#f9fafb;padding:10px;border-radius:4px;overflow-x:auto;}</style></head><body><div class="container"><h1>결제 정보 추가</h1>';
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
    // 모든 service_requests 조회
    $st = $pdo->query("
        SELECT sr.id, sr.customer_id, sr.service_type, sr.service_date, sr.created_at
        FROM service_requests sr
        ORDER BY sr.created_at DESC
    ");
    $requests = $st->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($requests)) {
        output("❌ 등록된 서비스 요청이 없습니다.", true);
        if ($isBrowser) echo '</div></body></html>';
        exit(1);
    }
    
    output("✅ 발견된 서비스 요청: " . count($requests) . "건");
    if ($isBrowser) echo '<pre>';
    
    $pdo->beginTransaction();
    
    $inserted = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($requests as $req) {
        try {
            // 이미 payments가 있는지 확인 (service_request_id로)
            $checkSt = $pdo->prepare("
                SELECT id FROM payments 
                WHERE service_request_id = ?
            ");
            $checkSt->execute([$req['id']]);
            $existing = $checkSt->fetch();
            
            if ($existing) {
                $skipped++;
                $message = sprintf(
                    "[%d/%d] ⏭️  건너뜀: %s (이미 결제 정보 존재)",
                    $inserted + $skipped,
                    count($requests),
                    $req['id']
                );
                echo $message . ($isBrowser ? '<br>' : "\n");
                continue;
            }
            
            // payments 생성
            $paymentId = uuid4();
            $amount = 10000;
            
            $insertSt = $pdo->prepare("
                INSERT INTO payments (
                    id, service_request_id, booking_id, customer_id, 
                    amount, payment_method, payment_key, status, paid_at, created_at
                ) VALUES (
                    ?, ?, NULL, ?, 
                    ?, 'CARD', NULL, 'SUCCESS', NOW(), NOW()
                )
            ");
            
            $insertSt->execute([
                $paymentId,
                $req['id'],
                $req['customer_id'],
                $amount
            ]);
            
            $inserted++;
            $message = sprintf(
                "[%d/%d] ✅ 생성: %s - %s (%s) - %s원",
                $inserted + $skipped,
                count($requests),
                substr($req['id'], 0, 8) . '...',
                $req['service_type'],
                $req['service_date'],
                number_format($amount)
            );
            echo $message . ($isBrowser ? '<br>' : "\n");
            
        } catch (Exception $e) {
            $errors[] = [
                'request_id' => $req['id'],
                'error' => $e->getMessage()
            ];
            $message = sprintf(
                "[%d/%d] ❌ 오류: %s - %s",
                $inserted + $skipped + count($errors),
                count($requests),
                substr($req['id'], 0, 8) . '...',
                $e->getMessage()
            );
            echo $message . ($isBrowser ? '<br>' : "\n");
        }
    }
    
    $pdo->commit();
    
    if ($isBrowser) echo '</pre>';
    
    output("\n✅ 총 {$inserted}개의 결제 정보가 생성되었습니다.");
    if ($skipped > 0) {
        output("⏭️  {$skipped}건은 이미 결제 정보가 있어 건너뛰었습니다.", false, true);
    }
    if (count($errors) > 0) {
        output("❌ {$errors}건에서 오류가 발생했습니다.", true);
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
