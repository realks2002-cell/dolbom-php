<?php
/**
 * 결제 성공 페이지
 * 토스페이먼츠에서 리다이렉트됨
 */

// 에러 출력 완전 차단 (화면에 표시되지 않도록)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 출력 버퍼링 시작
ob_start();

// 전역 에러 핸들러 설정 (화면에 표시되지 않도록)
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // vendor/composer 관련 에러는 완전히 무시
    if (strpos($errstr, 'vendor') !== false || 
        strpos($errstr, 'composer') !== false || 
        strpos($errstr, 'symfony') !== false ||
        strpos($errfile, 'vendor') !== false) {
        return true; // 에러 무시
    }
    // 다른 에러는 로그만 남기고 화면에 표시하지 않음
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    return true; // 기본 에러 표시 방지
}, E_ALL | E_STRICT);

// Fatal error 핸들러
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        // vendor 관련 Fatal error는 무시
        if (strpos($error['message'], 'vendor') === false && 
            strpos($error['message'], 'composer') === false) {
            error_log('Fatal Error: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']);
        }
        // 화면에 표시하지 않음
    }
});

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
        // 서비스 요청이 존재하는지 확인 (guest 정보도 함께 조회)
        $st = $pdo->prepare('SELECT id, customer_id, guest_name, guest_phone, estimated_price, service_type, service_date, start_time FROM service_requests WHERE id = ?');
        $st->execute([$requestId]);
        $request = $st->fetch();
        
        if (!$request) {
            throw new Exception('서비스 요청을 찾을 수 없습니다.');
        }
        
        // 비회원 결제 처리: customer_id가 null이면 비회원
        $isGuest = ($request['customer_id'] === null);
        
        // customer_id가 NULL인데 로그인되어 있으면 회원으로 처리 (데이터 불일치 복구)
        if ($isGuest && $currentUser && $currentUser['role'] === ROLE_CUSTOMER) {
            error_log('경고: service_requests.customer_id가 NULL이지만 로그인된 회원입니다. customer_id를 업데이트합니다.');
            // customer_id 업데이트
            $updateSt = $pdo->prepare('UPDATE service_requests SET customer_id = ? WHERE id = ?');
            $updateSt->execute([$currentUser['id'], $requestId]);
            $request['customer_id'] = $currentUser['id'];
            $isGuest = false;
        }
        
        // customer_id가 NULL이고 guest_name도 없고 로그인도 안 되어 있으면 오류
        if ($isGuest && empty($request['guest_name']) && !$currentUser) {
            error_log('경고: customer_id가 NULL이고 guest_name도 없고 로그인도 안 되어 있습니다.');
        }
        
        // 회원인 경우에만 권한 확인
        if (!$isGuest && $currentUser && $request['customer_id'] !== $currentUser['id']) {
            throw new Exception('권한이 없습니다.');
        }
        
        // 비회원인데 로그인한 경우도 확인
        if ($isGuest && $currentUser) {
            // 비회원 요청이므로 customer_id는 null이어야 함
            // 이미 null이므로 통과
        }
        
        // 서비스 요청 상태를 CONFIRMED로 업데이트
        $st2 = $pdo->prepare('UPDATE service_requests SET status = ? WHERE id = ?');
        $st2->execute(['CONFIRMED', $requestId]);
        
        // payments 테이블에 결제 정보 저장 (회원/비회원 모두)
        $paymentId = uuid4();
        $paymentMethod = $paymentData['method'] ?? 'UNKNOWN';
        $paymentSaved = false;
        $paymentError = '알 수 없는 오류가 발생했습니다.';
        
        try {
            // customer_id: 회원은 service_requests의 customer_id 사용, 비회원은 NULL
            // 회원인 경우 $request['customer_id']를 직접 사용 (세션 문제 방지)
            $customerIdForPayment = $isGuest ? null : ($request['customer_id'] ?? null);
            
            // customer_id가 NULL인 경우 처리
            if (!$customerIdForPayment) {
                // 1. 로그인된 회원이면 $currentUser 사용
                if ($currentUser && $currentUser['role'] === ROLE_CUSTOMER) {
                    $customerIdForPayment = $currentUser['id'];
                    error_log('경고: service_requests.customer_id가 NULL이므로 $currentUser를 사용합니다. customerId=' . $customerIdForPayment);
                    
                    // service_requests 테이블도 업데이트
                    try {
                        $updateSt = $pdo->prepare('UPDATE service_requests SET customer_id = ? WHERE id = ?');
                        $updateSt->execute([$customerIdForPayment, $requestId]);
                        error_log('service_requests.customer_id 업데이트 완료');
                    } catch (Exception $e) {
                        error_log('service_requests.customer_id 업데이트 실패: ' . $e->getMessage());
                    }
                } 
                // 2. guest_name이 있으면 비회원으로 처리
                else if (!empty($request['guest_name'])) {
                    $customerIdForPayment = null; // 비회원
                    $isGuest = true;
                    error_log('비회원 결제로 처리합니다. guest_name=' . $request['guest_name']);
                }
                // 3. 둘 다 없으면 오류
                else {
                    error_log('오류: customer_id가 NULL이고 currentUser도 없고 guest_name도 없습니다.');
                    throw new Exception('회원 정보를 찾을 수 없습니다. (customer_id: NULL, currentUser: ' . ($currentUser ? '있음' : '없음') . ', guest_name: ' . ($request['guest_name'] ?? '없음') . ')');
                }
            }
            
            // 디버깅: INSERT 전 값 확인
            error_log('payments INSERT 시도: paymentId=' . $paymentId . ', requestId=' . $requestId . ', customerIdForPayment=' . ($customerIdForPayment ?? 'NULL') . ', amount=' . $amount . ', paymentMethod=' . $paymentMethod . ', isGuest=' . ($isGuest ? 'true' : 'false'));
            
            // DB 스키마가 customer_id NULL을 허용하지 않으면 자동으로 수정 시도
            try {
                $st3 = $pdo->prepare('INSERT INTO payments (id, service_request_id, booking_id, customer_id, amount, payment_method, payment_key, status, paid_at) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, NOW())');
                $st3->execute([
                    $paymentId,
                    $requestId,
                    $customerIdForPayment,
                    (int) $amount,
                    $paymentMethod,
                    $paymentKey,
                    'SUCCESS'
                ]);
                
                error_log('payments INSERT 성공: paymentId=' . $paymentId);
                
                $paymentSaved = true;
            } catch (PDOException $e) {
                $errorMsg = $e->getMessage();
                $errorCode = $e->getCode();
                error_log('payments INSERT 첫 시도 실패: ' . $errorMsg . ' (코드: ' . $errorCode . ')');
                
                // customer_id NOT NULL 제약조건 오류인 경우 스키마 수정 시도
                if (strpos($errorMsg, 'cannot be null') !== false || 
                    strpos($errorMsg, '1048') !== false || 
                    strpos($errorMsg, 'Column \'customer_id\' cannot be null') !== false ||
                    strpos($errorMsg, 'Integrity constraint violation') !== false) {
                    try {
                        error_log('customer_id NULL 제약조건 오류 감지, 스키마 수정 시도');
                        
                        // FOREIGN KEY 제약조건 이름 확인
                        $fkCheck = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND CONSTRAINT_NAME = 'fk_payments_customer'")->fetch();
                        
                        if ($fkCheck) {
                            // FOREIGN KEY 제약조건 제거
                            $pdo->exec('ALTER TABLE payments DROP FOREIGN KEY fk_payments_customer');
                            error_log('FOREIGN KEY 제약조건 제거 완료');
                        }
                        
                        // customer_id를 NULL 허용으로 변경
                        $pdo->exec('ALTER TABLE payments MODIFY customer_id CHAR(36) NULL');
                        error_log('customer_id를 NULL 허용으로 변경 완료');
                        
                        // FOREIGN KEY 제약조건 재추가 (ON DELETE SET NULL로)
                        $pdo->exec('ALTER TABLE payments ADD CONSTRAINT fk_payments_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL');
                        error_log('FOREIGN KEY 제약조건 재추가 완료');
                        
                        // 다시 INSERT 시도
                        $st3 = $pdo->prepare('INSERT INTO payments (id, service_request_id, booking_id, customer_id, amount, payment_method, payment_key, status, paid_at) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, NOW())');
                        $st3->execute([
                            $paymentId,
                            $requestId,
                            $customerIdForPayment, // 회원인 경우 $request['customer_id'] 사용
                            (int) $amount,
                            $paymentMethod,
                            $paymentKey,
                            'SUCCESS'
                        ]);
                        
                        $paymentSaved = true;
                        error_log('payments 테이블 스키마 수정 후 INSERT 성공');
                    } catch (PDOException $e2) {
                        $paymentError = 'DB 스키마 수정 실패: ' . $e2->getMessage() . ' (원본 에러: ' . $errorMsg . ')';
                        error_log('payments 테이블 스키마 수정 실패: ' . $paymentError);
                        // 스키마 수정 실패해도 계속 진행 (details에 백업 저장)
                    }
                } else {
                    // 다른 오류는 그대로 전달
                    $paymentError = $errorMsg . ' (SQL 에러 코드: ' . $errorCode . ')';
                    error_log('payments INSERT 실패 (다른 오류): ' . $paymentError);
                    throw $e; // 상위 catch로 전달
                }
            }
            
            // 저장 성공 확인
            if ($paymentSaved) {
                $checkSt = $pdo->prepare('SELECT id, service_request_id FROM payments WHERE id = ?');
                $checkSt->execute([$paymentId]);
                $saved = $checkSt->fetch();
                
                if (!$saved || $saved['id'] !== $paymentId) {
                    $paymentSaved = false;
                    $paymentError = 'payments 저장 확인 실패: 저장된 레코드를 찾을 수 없습니다.';
                }
            }
        } catch (PDOException $e) {
            $paymentError = $e->getMessage();
            $errorInfo = $e->errorInfo ?? [];
            error_log('payments 테이블 저장 실패 (외부 catch): ' . $paymentError);
            error_log('SQL 에러 코드: ' . ($errorInfo[0] ?? 'N/A'));
            error_log('SQL 에러 메시지: ' . ($errorInfo[2] ?? 'N/A'));
            error_log('SQL 에러 정보 전체: ' . print_r($errorInfo, true));
            error_log('INSERT 시도 값들: customerIdForPayment=' . ($customerIdForPayment ?? 'NULL') . ', requestId=' . $requestId . ', amount=' . $amount . ', isGuest=' . ($isGuest ? 'true' : 'false'));
            
            // 에러 메시지에 상세 정보 추가
            if (!empty($errorInfo[2])) {
                $paymentError .= ' (SQL: ' . $errorInfo[2] . ')';
            }
        } catch (Exception $e) {
            $paymentError = $e->getMessage();
            error_log('payments 저장 오류 (Exception): ' . $paymentError);
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
        
        // 매니저들에게 푸시 알림 전송 (선택사항 - 실패해도 결제는 완료)
        // vendor 폴더가 없으면 건너뛰기
        $vendorPath = dirname(__DIR__, 2) . '/vendor';
        if (is_dir($vendorPath)) {
            try {
                $fcmFile = dirname(__DIR__, 2) . '/includes/fcm.php';
                if (file_exists($fcmFile)) {
                    // 에러 핸들러 임시 설정 (require 중 발생하는 에러 무시)
                    set_error_handler(function($errno, $errstr, $errfile, $errline) {
                        // vendor 관련 에러는 무시
                        if (strpos($errstr, 'vendor') !== false || strpos($errstr, 'composer') !== false) {
                            return true; // 에러 무시
                        }
                        return false; // 다른 에러는 기본 핸들러로
                    }, E_ALL);
                    
                    @require_once $fcmFile; // @로 에러 억제
                    
                    // 에러 핸들러 복원
                    restore_error_handler();
                    
                    // 함수가 존재하는지 확인
                    if (function_exists('send_push_to_managers')) {
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
                    }
                }
            } catch (Exception $e) {
                // 푸시 알림 실패해도 결제는 완료되었으므로 계속 진행
                error_log('푸시 알림 전송 실패: ' . $e->getMessage());
            } catch (Error $e) {
                // Fatal error도 캐치
                error_log('푸시 알림 Fatal Error: ' . $e->getMessage());
            } finally {
                // 에러 핸들러 복원 (혹시 모를 경우)
                restore_error_handler();
            }
        }
    } catch (Exception $e) {
        // DB 저장 실패해도 결제는 완료되었으므로 계속 진행
        error_log('결제 성공 후 DB 저장 실패: ' . $e->getMessage());
    }
    
    // 출력 버퍼 정리 (에러 메시지 제거)
    ob_end_clean();
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
            <p class="mt-2 text-xs font-semibold text-orange-800">에러 상세 정보:</p>
            <div class="mt-1 rounded bg-white p-2 text-xs text-orange-900 font-mono break-all">
                <?php if (!empty($paymentError)): ?>
                    <?= htmlspecialchars($paymentError) ?>
                <?php else: ?>
                    에러 메시지를 가져올 수 없습니다. (requestId: <?= htmlspecialchars($requestId ?? 'N/A') ?>, customerId: <?= htmlspecialchars($customerIdForPayment ?? 'NULL') ?>)
                <?php endif; ?>
            </div>
            <p class="mt-2 text-xs text-gray-600">서버 로그를 확인하시거나 관리자에게 문의해주세요.</p>
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
