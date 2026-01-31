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

if (!$currentUser || $currentUser['role'] !== ROLE_CUSTOMER) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => '권한이 없습니다.']);
    exit;
}

$raw = file_get_contents('php://input') ?: '{}';
$body = json_decode($raw, true) ?? [];

$serviceType = trim((string) ($body['service_type'] ?? ''));
$serviceDate = trim((string) ($body['service_date'] ?? ''));
$startTime = trim((string) ($body['start_time'] ?? ''));
$duration = (int) ($body['duration_hours'] ?? 0);
$address = trim((string) ($body['address'] ?? ''));
$addressDetail = trim((string) ($body['address_detail'] ?? ''));
$phone = trim((string) ($body['phone'] ?? ''));
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
    $st = $pdo->prepare('INSERT INTO service_requests (id, customer_id, service_type, service_date, start_time, duration_minutes, address, address_detail, phone, lat, lng, details, status, estimated_price) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $st->execute([
        $requestId,
        $currentUser['id'],
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
    
    // 저장 성공 확인
    $checkSt = $pdo->prepare('SELECT id FROM service_requests WHERE id = ?');
    $checkSt->execute([$requestId]);
    $saved = $checkSt->fetch();
    
    if (!$saved) {
        throw new Exception('저장 확인 실패');
    }
    
    echo json_encode(['ok' => true, 'request_id' => $requestId]);
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
