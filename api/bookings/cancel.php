<?php
/**
 * 예약 취소 및 환불 API
 * POST /api/bookings/cancel
 * Body: { request_id }
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
$requestId = trim((string) ($body['request_id'] ?? ''));

if ($requestId === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '요청 ID가 필요합니다.']);
    exit;
}

$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

try {
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    // 서비스 요청 확인
    $st = $pdo->prepare('SELECT id, customer_id, guest_phone, status, estimated_price FROM service_requests WHERE id = ?');
    $st->execute([$requestId]);
    $request = $st->fetch();
    
    if (!$request) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => '예약을 찾을 수 없습니다.']);
        exit;
    }
    
    // 권한 체크: 회원은 customer_id로, 비회원은 guest_phone으로 확인
    // 현재는 회원 전용 API이지만, 비회원 케이스도 체크
    $isGuest = ($request['customer_id'] === null);
    if ($isGuest) {
        // 비회원인 경우 이 API 사용 불가 (비회원은 cancel-guest.php 사용)
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => '비회원 예약은 비회원 취소 페이지를 사용해주세요.']);
        exit;
    }
    
    // 회원 권한 체크
    if ($request['customer_id'] !== $currentUser['id']) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => '권한이 없습니다.']);
        exit;
    }
    
    // 취소 가능한 상태인지 확인
    if (!in_array($request['status'], ['CONFIRMED', 'MATCHING'], true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => '취소할 수 없는 상태입니다.']);
        exit;
    }
    
    // 결제 정보 조회
    $st2 = $pdo->prepare('SELECT id, payment_key, amount, status FROM payments WHERE service_request_id = ? AND status = ? ORDER BY created_at DESC LIMIT 1');
    $st2->execute([$requestId, 'SUCCESS']);
    $payment = $st2->fetch();
    
    $refundSuccess = false;
    $refundError = null;
    
    // 결제가 있으면 환불 처리
    if ($payment && $payment['payment_key']) {
        // 토스페이먼츠 환불 API 호출
        $url = 'https://api.tosspayments.com/v1/payments/' . urlencode($payment['payment_key']) . '/cancel';
        $data = [
            'cancelReason' => '고객 요청에 의한 취소'
        ];
        
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
            CURLOPT_PROXY => '',
            CURLOPT_PROXYPORT => '',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $refundResult = json_decode($response, true);
            if (isset($refundResult['status']) && $refundResult['status'] === 'CANCELLED') {
                $refundSuccess = true;
                
                // payments 테이블 업데이트 (refunded_at 필드가 있을 수도 있고 없을 수도 있음)
                try {
                    // refunded_at 필드 존재 여부 확인
                    $checkColumn = $pdo->query("SHOW COLUMNS FROM payments LIKE 'refunded_at'");
                    $hasRefundedAt = $checkColumn->rowCount() > 0;
                    
                    if ($hasRefundedAt) {
                        $st3 = $pdo->prepare('UPDATE payments SET status = ?, refund_amount = ?, refund_reason = ?, refunded_at = NOW() WHERE id = ?');
                    } else {
                        $st3 = $pdo->prepare('UPDATE payments SET status = ?, refund_amount = ?, refund_reason = ? WHERE id = ?');
                    }
                    
                    $params = [
                        'REFUNDED',
                        $payment['amount'],
                        '고객 요청에 의한 취소',
                        $payment['id']
                    ];
                    
                    if ($hasRefundedAt) {
                        // refunded_at는 NOW()로 처리되므로 파라미터 추가 안 함
                    }
                    
                    $st3->execute($params);
                    
                    // 업데이트 성공 확인
                    $affectedRows = $st3->rowCount();
                    if ($affectedRows === 0) {
                        error_log("경고: payments 테이블 업데이트 실패. payment_id={$payment['id']}");
                        $refundError = '환불 정보 저장에 실패했습니다.';
                        $refundSuccess = false;
                    } else {
                        error_log("환불 정보 저장 성공: payment_id={$payment['id']}, refund_amount={$payment['amount']}");
                    }
                } catch (PDOException $e) {
                    error_log("payments 테이블 업데이트 오류: " . $e->getMessage());
                    $refundError = '환불 정보 저장 중 오류가 발생했습니다: ' . $e->getMessage();
                    $refundSuccess = false;
                }
            } else {
                $refundError = $refundResult['message'] ?? '환불 처리 실패';
            }
        } else {
            $refundError = '환불 API 호출 실패 (HTTP ' . $httpCode . ')';
            if ($curlError) {
                $refundError .= ': ' . $curlError;
            }
        }
    }
    
    // 서비스 요청 상태를 CANCELLED로 변경
    $st4 = $pdo->prepare('UPDATE service_requests SET status = ? WHERE id = ?');
    $st4->execute(['CANCELLED', $requestId]);
    
    // 트랜잭션 커밋
    $pdo->commit();
    
    if ($payment && !$refundSuccess) {
        // 환불 실패했지만 취소는 진행
        echo json_encode([
            'ok' => true,
            'cancelled' => true,
            'refund_warning' => '예약은 취소되었지만 환불 처리에 실패했습니다. 고객센터로 문의해주세요.',
            'refund_error' => $refundError
        ]);
    } else {
        echo json_encode([
            'ok' => true,
            'cancelled' => true,
            'refunded' => $refundSuccess
        ]);
    }
} catch (PDOException $e) {
    // 트랜잭션 롤백
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("예약 취소 DB 오류: " . $e->getMessage());
    http_response_code(500);
    $errorMsg = '취소 처리 실패';
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $errorMsg .= ': ' . $e->getMessage();
    }
    echo json_encode(['ok' => false, 'error' => $errorMsg]);
} catch (Exception $e) {
    // 트랜잭션 롤백
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("예약 취소 오류: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
