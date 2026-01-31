<?php
/**
 * 랜덤 고객 요청 20개 생성 스크립트
 * 브라우저에서 실행하거나 CLI에서 실행 가능
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/database/connect.php';

$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

// 브라우저 실행 여부 확인
$isBrowser = php_sapi_name() !== 'cli';

if ($isBrowser) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>랜덤 고객 요청 생성</title>';
    echo '<style>body{font-family:"Noto Sans KR",sans-serif;padding:20px;background:#f5f5f5;}';
    echo '.container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;}';
    echo 'h1{color:#333;} .success{color:#10b981;} .error{color:#ef4444;}';
    echo 'pre{background:#f9fafb;padding:10px;border-radius:4px;overflow-x:auto;}</style></head><body><div class="container"><h1>랜덤 고객 요청 생성</h1>';
}

function output($message, $isError = false) {
    global $isBrowser;
    if ($isBrowser) {
        $class = $isError ? 'error' : 'success';
        echo "<div class='{$class}'>" . htmlspecialchars($message) . "</div>";
    } else {
        echo $message . "\n";
    }
}

try {
    // 기존 고객 사용자 확인
    $st = $pdo->query("SELECT id, name, email FROM users WHERE role = 'CUSTOMER' LIMIT 10");
    $customers = $st->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($customers)) {
        output("❌ 고객 사용자가 없습니다. 먼저 고객 사용자를 생성해주세요.", true);
        if ($isBrowser) echo '</div></body></html>';
        exit(1);
    }
    
    output("✅ 발견된 고객 사용자: " . count($customers) . "명");
    if ($isBrowser) echo '<pre>';
    
    // 서비스 유형
    $serviceTypes = ['병원 동행', '가사돌봄', '생활동행', '노인 돌봄', '아이 돌봄', '기타'];
    
    // 상태
    $statuses = ['PENDING', 'MATCHING', 'CONFIRMED', 'COMPLETED', 'CANCELLED'];
    
    // 주소 목록
    $addresses = [
        ['서울특별시 강남구 테헤란로', 37.5010, 127.0394],
        ['서울특별시 서초구 서초대로', 37.4837, 127.0324],
        ['서울특별시 송파구 올림픽로', 37.5145, 127.1058],
        ['경기도 성남시 분당구 정자로', 37.3599, 127.1118],
        ['경기도 용인시 기흥구 구갈로', 37.2744, 127.1150],
        ['경기도 수원시 영통구 월드컵로', 37.2596, 127.0466],
        ['인천광역시 연수구 송도과학로', 37.3841, 126.6566],
        ['부산광역시 해운대구 해운대해변로', 35.1631, 129.1636],
    ];
    
    // 특기사항 목록
    $detailsList = [
        '친절하게 부탁드립니다',
        '시간 엄수 부탁드립니다',
        '안전하게 진행 부탁드립니다',
        '상세한 설명 부탁드립니다',
        '편안하게 진행 부탁드립니다',
        null, // NULL도 포함
    ];
    
    $pdo->beginTransaction();
    
    $inserted = 0;
    for ($i = 0; $i < 20; $i++) {
        // 랜덤 고객 선택
        $customer = $customers[array_rand($customers)];
        
        // UUID 생성
        $id = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        // 랜덤 값 생성
        $serviceType = $serviceTypes[array_rand($serviceTypes)];
        $serviceDate = date('Y-m-d', strtotime('+' . mt_rand(1, 30) . ' days'));
        $startHour = mt_rand(8, 19);
        $startMinute = mt_rand(0, 59);
        $startTime = sprintf('%02d:%02d:00', $startHour, $startMinute);
        $durationHours = mt_rand(1, 5);
        $durationMinutes = $durationHours * 60;
        
        $addressData = $addresses[array_rand($addresses)];
        $address = $addressData[0] . ' ' . mt_rand(100, 999);
        $addressDetail = mt_rand(0, 1) ? mt_rand(100, 999) . '호' : null;
        $lat = $addressData[1] + (mt_rand(-500, 500) / 10000);
        $lng = $addressData[2] + (mt_rand(-500, 500) / 10000);
        
        $details = mt_rand(0, 100) > 40 ? $detailsList[array_rand($detailsList)] : null;
        $status = $statuses[array_rand($statuses)];
        $estimatedPrice = $durationHours * 20000;
        
        $createdAt = date('Y-m-d H:i:s', strtotime('-' . mt_rand(0, 30) . ' days'));
        
        // INSERT 실행
        $st = $pdo->prepare("
            INSERT INTO service_requests (
                id, customer_id, service_type, service_date, start_time,
                duration_minutes, address, address_detail, lat, lng,
                details, status, estimated_price, created_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?
            )
        ");
        
        $st->execute([
            $id,
            $customer['id'],
            $serviceType,
            $serviceDate,
            $startTime,
            $durationMinutes,
            $address,
            $addressDetail,
            $lat,
            $lng,
            $details,
            $status,
            $estimatedPrice,
            $createdAt
        ]);
        
        $inserted++;
        $message = sprintf(
            "[%d/%d] ✅ 생성: %s - %s (%s %s) - %s원",
            $inserted,
            20,
            $customer['name'],
            $serviceType,
            $serviceDate,
            $startTime,
            number_format($estimatedPrice)
        );
        echo $message . ($isBrowser ? '<br>' : "\n");
    }
    
    $pdo->commit();
    
    if ($isBrowser) echo '</pre>';
    output("\n✅ 총 {$inserted}개의 랜덤 고객 요청이 생성되었습니다.");
    
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
