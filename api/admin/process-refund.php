<?php
/**
 * 관리자 수동 환불 처리 API
 * POST /api/admin/process-refund
 * Body: { payment_id, payment_key, amount, reason }
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// 관리자 권한 확인
if (!$currentUser || $currentUser['role'] !== ROLE_ADMIN) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '관리자 권한이 필요합니다.']);
    exit;
}

$raw = file_get_contents('php://input') ?: '{}';
$body = json_decode($raw, true) ?? [];

$paymentId = trim((string) ($body['payment_id'] ?? ''));
$paymentKey = trim((string) ($body['payment_key'] ?? ''));
$amount = (int) ($body['amount'] ?? 0);
$reason = trim((string) ($body['reason'] ?? '관리자 수동 환불'));

if ($paymentId === '' || $paymentKey === '' || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '필수 정보가 누락되었습니다.']);
    exit;
}

$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

try {
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    // 결제 정보 확인
    $st = $pdo->prepare('SELECT id, payment_key, amount, status FROM payments WHERE id = ?');
    $st->execute([$paymentId]);
    $payment = $st->fetch();
    
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => '결제 정보를 찾을 수 없습니다.']);
        exit;
    }
    
    if ($payment['status'] !== 'SUCCESS') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '환불 가능한 상태가 아닙니다. (현재: ' . $payment['status'] . ')']);
        exit;
    }
    
    $refundSuccess = false;
    $refundError = null;
    
    // 토스페이먼츠 환불 API 호출
    $url = 'https://api.tosspayments.com/v1/payments/' . urlencode($paymentKey) . '/cancel';
    $data = [
        'cancelReason' => $reason,
        'cancelAmount' => $amount  // 전액 환불
    ];
    
    $credential = base64_encode(TOSS_SECRET_KEY . ':');
    
    error_log('관리자 수동 환불 시도: payment_id=' . $paymentId . ', payment_key=' . $paymentKey . ', amount=' . $amount);
    
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
    
    error_log('토스페이먼츠 환불 API 응답: HTTP ' . $httpCode . ', Response: ' . substr($response, 0, 200));
    
    if ($httpCode === 200) {
        $refundResult = json_decode($response, true);
        if (isset($refundResult['status']) && $refundResult['status'] === 'CANCELED') {
            $refundSuccess = true;
            error_log('환불 API 성공: payment_id=' . $paymentId);
        } else {
            $refundError = $refundResult['message'] ?? '환불 처리 실패';
            error_log('환불 API 실패: ' . $refundError);
        }
    } else {
        $refundError = '환불 API 호출 실패 (HTTP ' . $httpCode . ')';
        if ($curlError) {
            $refundError .= ': ' . $curlError;
        }
        error_log('환불 API 호출 실패: HTTP ' . $httpCode . ', curl 오류: ' . $curlError);
    }
    
    // 환불 성공 시 payments 테이블 업데이트
    if ($refundSuccess) {
        $st2 = $pdo->prepare('UPDATE payments SET status = ?, refund_amount = ?, refund_reason = ?, refunded_at = NOW() WHERE id = ?');
        $st2->execute(['REFUNDED', $amount, $reason, $paymentId]);
        
        error_log('payments 테이블 업데이트 완료: payment_id=' . $paymentId);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '환불이 완료되었습니다.',
            'refund_amount' => $amount
        ]);
    } else {
        // 환불 실패
        $pdo->rollBack();
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $refundError,
            'details' => [
                'http_code' => $httpCode,
                'curl_error' => $curlError,
                'response' => substr($response, 0, 500)
            ]
        ]);
    }
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('환불 처리 DB 오류: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB 오류: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('환불 처리 오류: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
