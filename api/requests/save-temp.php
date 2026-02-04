<?php
/**
 * 서비스 요청 임시 저장 API (결제 전)
 * POST /api/requests/save-temp
 * Body: { service_type, service_date, start_time, duration_hours, address, ... }
 * 응답: { ok, request_id }
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/api/cors.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input') ?: '{}';
$body = json_decode($raw, true) ?? [];

// 비회원 정보 확인
$guestName = trim((string)($body['guest_name'] ?? ''));
$guestPhone = trim((string)($body['guest_phone'] ?? ''));
$guestAddress = trim((string)($body['guest_address'] ?? ''));
$guestAddressDetail = trim((string)($body['guest_address_detail'] ?? ''));

// 회원이 아니면 비회원 정보 필수
if (!$currentUser && $guestName === '') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => '로그인이 필요하거나 신청자 정보를 입력해야 합니다.']);
    exit;
}

// 회원인 경우 customer_id 사용, 비회원은 null
$customerId = null;
if ($currentUser) {
    if (isset($currentUser['id']) && !empty($currentUser['id'])) {
        $customerId = $currentUser['id'];
    } else {
        error_log('경고: $currentUser는 있지만 id가 없음. currentUser=' . json_encode($currentUser));
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => '회원 정보를 찾을 수 없습니다. 다시 로그인해주세요.']);
        exit;
    }
}

// 디버깅: customer_id 확인
error_log('save-temp.php: currentUser=' . ($currentUser ? '있음(id: ' . ($currentUser['id'] ?? '없음') . ')' : '없음') . ', customerId=' . ($customerId ?? 'NULL') . ', guestName=' . ($guestName ?: '없음'));

$serviceType = trim((string) ($body['service_type'] ?? ''));
$serviceDate = trim((string) ($body['service_date'] ?? ''));
$startTime = trim((string) ($body['start_time'] ?? ''));
$duration = (int) ($body['duration_hours'] ?? 0);

// 주소: 비회원은 guest_address 사용, 회원은 address 사용
$address = trim((string) ($body['address'] ?? ''));
if ($address === '' && $guestAddress !== '') {
    $address = $guestAddress; // 비회원 주소 사용
}

// 상세 주소: 비회원은 guest_address_detail 사용
$addressDetail = trim((string) ($body['address_detail'] ?? ''));
if ($addressDetail === '' && $guestAddressDetail !== '') {
    $addressDetail = $guestAddressDetail; // 비회원 상세 주소 사용
}

// 전화번호: 비회원은 guest_phone 사용
$phone = trim((string) ($body['phone'] ?? ''));
if ($phone === '' && $guestPhone !== '') {
    $phone = $guestPhone; // 비회원 전화번호 사용
}

$details = trim((string) ($body['details'] ?? ''));
$lat = isset($body['lat']) ? (float) $body['lat'] : 0.0;
$lng = isset($body['lng']) ? (float) $body['lng'] : 0.0;

$allowedTypes = ['병원 동행', '가사돌봄', '생활동행', '노인 돌봄', '아이 돌봄', '기타'];
if (!in_array($serviceType, $allowedTypes, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '서비스를 선택해주세요.']);
    exit;
}

if (!$serviceDate || !$startTime || $duration < 1 || $duration > 12) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '일시와 예상 시간을 확인해주세요.']);
    exit;
}

if ($address === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '주소를 입력해주세요.']);
    exit;
}

if ($phone === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '전화번호를 입력해주세요.']);
    exit;
}

if (!preg_match('/^[0-9-]+$/', $phone)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '올바른 전화번호 형식이 아닙니다.']);
    exit;
}

$durationMin = $duration * 60;
$RATE_PER_HOUR = 20000;
$estimatedPrice = $duration * $RATE_PER_HOUR;
$requestId = uuid4();

$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

try {
    // 디버깅: 저장 전 값 확인
    error_log('save-temp.php: customerId=' . ($customerId ?? 'NULL') . ', currentUser=' . ($currentUser ? '있음' : '없음') . ', guestName=' . ($guestName ?: '없음'));
    
    // guest 컬럼 포함하여 Insert
    $st = $pdo->prepare('INSERT INTO service_requests (id, customer_id, guest_name, guest_phone, guest_address, guest_address_detail, service_type, service_date, start_time, duration_minutes, address, address_detail, phone, lat, lng, details, status, estimated_price) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $st->execute([
        $requestId,
        $customerId, // 회원인 경우 반드시 값이 있어야 함
        $guestName === '' ? null : $guestName,
        $guestPhone === '' ? null : $guestPhone,
        $guestAddress === '' ? null : $guestAddress,
        $guestAddressDetail === '' ? null : $guestAddressDetail,
        $serviceType,
        $serviceDate,
        $startTime,
        $durationMin,
        $address,
        $addressDetail === '' ? null : $addressDetail,
        $phone === '' ? null : $phone,
        $lat,
        $lng,
        $details === '' ? null : $details,
        'PENDING', // 결제 전이므로 PENDING
        $estimatedPrice
    ]);
    
    // 저장 성공 확인 및 customer_id 검증
    $checkSt = $pdo->prepare('SELECT id, customer_id FROM service_requests WHERE id = ?');
    $checkSt->execute([$requestId]);
    $saved = $checkSt->fetch();
    
    if (!$saved) {
        throw new Exception('저장 확인 실패');
    }
    
    // 회원인데 customer_id가 NULL이면 오류
    if ($currentUser && $saved['customer_id'] === null) {
        error_log('경고: 회원인데 customer_id가 NULL로 저장됨. requestId=' . $requestId . ', currentUser=' . ($currentUser ? json_encode($currentUser) : 'NULL'));
        // customer_id 업데이트 시도
        $updateSt = $pdo->prepare('UPDATE service_requests SET customer_id = ? WHERE id = ?');
        $updateSt->execute([$currentUser['id'], $requestId]);
        error_log('customer_id 업데이트 완료: ' . $currentUser['id']);
    }
    
    echo json_encode(['ok' => true, 'request_id' => $requestId, 'customer_id' => $saved['customer_id']]);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    $errorMsg = 'DB 저장 실패';
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $errorMsg .= ': ' . $e->getMessage();
    }
    echo json_encode(['ok' => false, 'error' => $errorMsg, 'debug' => defined('APP_DEBUG') && APP_DEBUG ? $e->getTraceAsString() : null]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
}
