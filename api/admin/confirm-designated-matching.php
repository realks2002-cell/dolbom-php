<?php
/**
 * 지정 도우미 매칭 확정 API
 * POST /api/admin/confirm-designated-matching
 * 
 * 요청: { "request_id": "uuid" }
 * 응답: { "ok": true }
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/api/cors.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

// 관리자 권한 확인
require_admin();

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'POST 요청만 허용됩니다.']);
    exit;
}

// JSON 입력 파싱
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => '잘못된 요청 형식입니다.']);
    exit;
}

$requestId = trim($input['request_id'] ?? '');

if ($requestId === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => '요청 ID가 필요합니다.']);
    exit;
}

try {
    $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
    
    // service_requests 조회
    $stmt = $pdo->prepare("
        SELECT id, designated_manager_id, status, estimated_price 
        FROM service_requests 
        WHERE id = ?
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    
    if (!$request) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => '요청을 찾을 수 없습니다.']);
        exit;
    }
    
    // designated_manager_id 확인
    if (empty($request['designated_manager_id'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => '지정 도우미가 없는 요청입니다.']);
        exit;
    }
    
    // 상태 확인 (CONFIRMED만 매칭 가능)
    if ($request['status'] !== 'CONFIRMED') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => '이미 처리된 요청입니다. (현재 상태: ' . $request['status'] . ')']);
        exit;
    }
    
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    try {
        // bookings 테이블에 레코드 생성
        $bookingId = uuid4();
        $bookingStmt = $pdo->prepare("
            INSERT INTO bookings (id, request_id, manager_id, final_price, payment_status, created_at)
            VALUES (?, ?, ?, ?, 'PAID', NOW())
        ");
        $bookingStmt->execute([
            $bookingId,
            $requestId,
            $request['designated_manager_id'],
            $request['estimated_price']
        ]);
        
        // service_requests 상태를 MATCHING으로 변경
        $updateStmt = $pdo->prepare("
            UPDATE service_requests 
            SET status = 'MATCHING', updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$requestId]);
        
        // 다른 매니저의 대기중인 지원을 모두 거절 처리
        $rejectOthersStmt = $pdo->prepare("
            UPDATE applications 
            SET status = 'REJECTED', updated_at = NOW() 
            WHERE request_id = ? 
            AND manager_id != ? 
            AND status = 'PENDING'
        ");
        $rejectOthersStmt->execute([$requestId, $request['designated_manager_id']]);
        
        // 트랜잭션 커밋
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'booking_id' => $bookingId,
            'message' => '매칭이 확정되었습니다.'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => '매칭 확정 중 오류가 발생했습니다.',
        'error' => APP_DEBUG ? $e->getMessage() : null
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage()
    ]);
}
