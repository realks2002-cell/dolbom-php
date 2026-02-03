<?php
/**
 * 비회원 예약 취소 및 환불 API
 * POST /api/bookings/cancel-guest
 * Body: { request_id, guest_phone }
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/api/cors.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input') ?: '{}';
$body = json_decode($raw, true) ?? [];
$requestId = trim((string) ($body['request_id'] ?? ''));
$guestPhone = trim((string) ($body['guest_phone'] ?? ''));

// 전화번호 정규화
$guestPhone = preg_replace('/[^0-9]/', '', $guestPhone);

if ($requestId === '' || $guestPhone === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '예약번호와 전화번호가 필요합니다.']);
    exit;
}

$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

try {
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    // 비회원 예약 확인
    $st = $pdo->prepare('SELECT id, status, estimated_price, guest_phone FROM service_requests WHERE id = ? AND guest_phone = ? AND customer_id IS NULL');
    $st->execute([$requestId, $guestPhone]);
    $request = $st->fetch();
    
    if (!$request) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => '예약을 찾을 수 없습니다.']);
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
            'cancelReason' => '비회원 고객 요청에 의한 취소'
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
            if (isset($refundResult['status']) && $refundResult['status'] === 'CANCELED') {
                $refundSuccess = true;
                
                // payments 테이블 업데이트
                try {
                    $st3 = $pdo->prepare('UPDATE payments SET status = ?, refund_amount = ?, refund_reason = ?, refunded_at = NOW() WHERE id = ?');
                    $st3->execute(['REFUNDED', $payment['amount'], '비회원 고객 요청에 의한 취소', $payment['id']]);
                } catch (PDOException $e) {
                    error_log("비회원 환불 - payments 테이블 업데이트 오류: " . $e->getMessage());
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
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("비회원 예약 취소 DB 오류: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => '취소 처리 실패']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("비회원 예약 취소 오류: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
