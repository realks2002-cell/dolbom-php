<?php
/**
 * 결제 성공 페이지
 * 토스페이먼츠에서 리다이렉트됨
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$base = rtrim(BASE_URL, '/');

// 결제 승인 요청
$paymentKey = $_GET['paymentKey'] ?? '';
$orderId = $_GET['orderId'] ?? '';
$amount = $_GET['amount'] ?? '';

// 디버그: 받은 파라미터 확인
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_log('결제 성공 페이지 접근: paymentKey=' . ($paymentKey ? '있음' : '없음') . ', orderId=' . ($orderId ?: '없음') . ', amount=' . ($amount ?: '없음'));
    error_log('GET 파라미터: ' . print_r($_GET, true));
}

if (!$paymentKey || !$orderId || !$amount) {
    // 파라미터가 없으면 에러 페이지로
    $errorMsg = '결제 정보가 없습니다.';
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $errorMsg .= ' paymentKey=' . ($paymentKey ?: '없음') . ', orderId=' . ($orderId ?: '없음') . ', amount=' . ($amount ?: '없음');
    }
    redirect('/payment/fail?message=' . urlencode($errorMsg));
}

// 토스페이먼츠 결제 승인 API 호출 (샘플 코드와 동일한 방식)
$url = 'https://api.tosspayments.com/v1/payments/confirm';
$data = [
    'paymentKey' => $paymentKey,
    'orderId' => $orderId,
    'amount' => (int) $amount  // 정수로 변환
];

// 시크릿 키 인증 (샘플 코드와 동일)
$credential = base64_encode(TOSS_SECRET_KEY . ':');

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $credential,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
    // 프록시 비활성화 (프록시 설정 문제 해결)
    CURLOPT_PROXY => '',
    CURLOPT_PROXYPORT => '',
    // SSL 검증 설정
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    // 타임아웃 설정
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$result = json_decode($response, true);

$pageTitle = '결제 완료 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50 py-8';
ob_start();

if ($httpCode === 200 && isset($result['status']) && $result['status'] === 'DONE'):
    // 결제 성공 - DB에 저장
    $paymentData = $result;
    
    // orderId는 service_requests.id와 동일
    $requestId = $orderId;
    $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
    
    try {
        // 서비스 요청이 존재하는지 확인
        $st = $pdo->prepare('SELECT id, customer_id, estimated_price, service_type, service_date, start_time FROM service_requests WHERE id = ?');
        $st->execute([$requestId]);
        $request = $st->fetch();
        
        if (!$request) {
            throw new Exception('서비스 요청을 찾을 수 없습니다.');
        }
        
        if ($request['customer_id'] !== $currentUser['id']) {
            throw new Exception('권한이 없습니다.');
        }
        
        // 서비스 요청 상태를 CONFIRMED로 업데이트
        $st2 = $pdo->prepare('UPDATE service_requests SET status = ? WHERE id = ?');
        $st2->execute(['CONFIRMED', $requestId]);
        
        // payments 테이블에 결제 정보 저장
        $paymentId = uuid4();
        $paymentMethod = $paymentData['method'] ?? 'UNKNOWN';
        $paymentSaved = false;
        $paymentError = null;
        
        try {
            // 테이블 구조: service_request_id와 booking_id 모두 NULL 허용
            $st3 = $pdo->prepare('INSERT INTO payments (id, service_request_id, booking_id, customer_id, amount, payment_method, payment_key, status, paid_at) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, NOW())');
            $st3->execute([
                $paymentId,
                $requestId,
                $currentUser['id'],
                (int) $amount,
                $paymentMethod,
                $paymentKey,
                'SUCCESS'
            ]);
            
            // 저장 성공 확인
            $checkSt = $pdo->prepare('SELECT id, service_request_id FROM payments WHERE id = ?');
            $checkSt->execute([$paymentId]);
            $saved = $checkSt->fetch();
            
            if ($saved && $saved['id'] === $paymentId) {
                $paymentSaved = true;
            } else {
                $paymentError = 'payments 저장 확인 실패: 저장된 레코드를 찾을 수 없습니다.';
            }
        } catch (PDOException $e) {
            $paymentError = $e->getMessage();
            $errorInfo = $e->errorInfo ?? [];
            error_log('payments 테이블 저장 실패: ' . $paymentError);
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log('SQL 에러 정보: ' . print_r($errorInfo, true));
            }
        } catch (Exception $e) {
            $paymentError = $e->getMessage();
            error_log('payments 저장 오류: ' . $paymentError);
        }
        
        // payments 저장 실패 시 details에 백업 저장
        if (!$paymentSaved) {
            $paymentInfo = [
                'payment_key' => $paymentKey,
                'payment_method' => $paymentMethod,
                'amount' => (int) $amount,
                'paid_at' => date('Y-m-d H:i:s'),
                'error' => $paymentError
            ];
            
            $st3 = $pdo->prepare('SELECT details FROM service_requests WHERE id = ?');
            $st3->execute([$requestId]);
            $existingDetails = $st3->fetchColumn();
            $detailsArray = $existingDetails ? json_decode($existingDetails, true) : [];
            if (!is_array($detailsArray)) $detailsArray = [];
            $detailsArray['payment'] = $paymentInfo;
            $updatedDetails = json_encode($detailsArray, JSON_UNESCAPED_UNICODE);
            
            $st4 = $pdo->prepare('UPDATE service_requests SET details = ? WHERE id = ?');
            $st4->execute([$updatedDetails, $requestId]);
        }
        
        // 세션에 저장
        init_session();
        $_SESSION['request_created'] = $requestId;
        
        // 매니저들에게 푸시 알림 전송
        try {
            require_once dirname(__DIR__, 2) . '/includes/fcm.php';
            $serviceType = $request['service_type'] ?? '서비스';
            $serviceDate = $request['service_date'] ?? '';
            $serviceTime = $request['start_time'] ?? '';
            
            $title = '새로운 서비스 요청이 등록되었습니다';
            $body = $serviceType . ' 서비스 요청이 결제 완료되었습니다.';
            $data = [
                'type' => 'new_service_request',
                'request_id' => $requestId,
                'service_type' => $serviceType,
                'service_date' => $serviceDate,
                'service_time' => $serviceTime
            ];
            
            $pushResult = send_push_to_managers($pdo, $title, $body, $data);
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log('푸시 알림 전송 결과: ' . json_encode($pushResult, JSON_UNESCAPED_UNICODE));
            }
        } catch (Exception $e) {
            // 푸시 알림 실패해도 결제는 완료되었으므로 계속 진행
            error_log('푸시 알림 전송 실패: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        // DB 저장 실패해도 결제는 완료되었으므로 계속 진행
        error_log('결제 성공 후 DB 저장 실패: ' . $e->getMessage());
    }
?>
<div class="mx-auto max-w-lg px-4">
    <div class="rounded-lg border bg-white p-6 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1 class="mt-4 text-2xl font-bold text-gray-900">결제가 완료되었습니다</h1>
        <p class="mt-2 text-gray-600">서비스 요청이 등록되었습니다.</p>
        <?php if (!$paymentSaved): ?>
        <div class="mt-3 rounded-lg border border-orange-200 bg-orange-50 p-3 text-left">
            <p class="text-sm font-semibold text-orange-800">⚠️ payments 테이블 저장 실패</p>
            <p class="mt-1 text-xs text-orange-700">결제 정보는 service_requests.details에 백업 저장되었습니다.</p>
            <?php if (defined('APP_DEBUG') && APP_DEBUG && $paymentError): ?>
            <p class="mt-1 text-xs text-orange-600">에러: <?= htmlspecialchars($paymentError) ?></p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p class="mt-2 text-xs text-green-600">✅ 결제 정보가 payments 테이블에 저장되었습니다.</p>
        <?php endif; ?>
        
        <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4 text-left">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-600">주문번호</dt>
                    <dd class="font-medium"><?= htmlspecialchars($orderId) ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">결제금액</dt>
                    <dd class="font-bold text-primary"><?= number_format((int) $amount) ?>원</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">결제수단</dt>
                    <dd class="font-medium"><?= htmlspecialchars($paymentData['method'] ?? '-') ?></dd>
                </div>
            </dl>
        </div>
        
        <div class="mt-6 flex flex-col gap-2">
            <a href="<?= $base ?>/bookings" class="rounded-lg bg-primary px-6 py-3 font-medium text-white hover:opacity-90">내 예약 확인</a>
            <a href="<?= $base ?>/" class="rounded-lg border border-gray-300 px-6 py-3 font-medium text-gray-700 hover:bg-gray-50">홈으로</a>
        </div>
    </div>
</div>
<?php
else:
    // 결제 승인 실패 - 상세 에러 정보 표시
    $errorMsg = $result['message'] ?? '결제 승인에 실패했습니다.';
    $errorCode = $result['code'] ?? '';
    
    // 디버그 정보 (APP_DEBUG일 때만)
    $debugInfo = '';
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $debugInfo = '<div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-left text-xs">';
        $debugInfo .= '<p><strong>HTTP 코드:</strong> ' . htmlspecialchars($httpCode) . '</p>';
        if ($curlError) {
            $debugInfo .= '<p><strong>cURL 오류:</strong> ' . htmlspecialchars($curlError) . '</p>';
        }
        $debugInfo .= '<p><strong>응답:</strong> <pre>' . htmlspecialchars($response) . '</pre></p>';
        $debugInfo .= '</div>';
    }
?>
<div class="mx-auto max-w-lg px-4">
    <div class="rounded-lg border border-red-200 bg-white p-6 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
            <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        <h1 class="mt-4 text-2xl font-bold text-gray-900">결제 승인 실패</h1>
        <p class="mt-2 text-red-600"><?= htmlspecialchars($errorMsg) ?></p>
        <?php if ($errorCode): ?>
        <p class="mt-1 text-sm text-gray-500">에러 코드: <?= htmlspecialchars($errorCode) ?></p>
        <?php endif; ?>
        <?= $debugInfo ?>
        
        <div class="mt-6">
            <a href="<?= $base ?>/requests/new" class="rounded-lg bg-gray-600 px-6 py-3 font-medium text-white hover:opacity-90">다시 시도</a>
        </div>
    </div>
</div>
<?php
endif;

$layoutContent = ob_get_clean();
require_once dirname(__DIR__, 2) . '/components/layout.php';
