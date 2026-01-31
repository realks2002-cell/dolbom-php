<?php
/**
 * 결제 환불 처리 API (토스페이먼츠 연동)
 * POST /api/payments/refund
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

header('Content-Type: application/json');

// 관리자 권한 확인 (admins 테이블 또는 users 테이블 ADMIN)
init_session();
$isAdmin = !empty($_SESSION['admin_id']) || !empty($_SESSION['admin_db_id']);

if (!$isAdmin) {
    // users 테이블의 ADMIN 역할도 확인
    if (!empty($_SESSION['user_id'])) {
        $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
        $st = $pdo->prepare('SELECT role FROM users WHERE id = ?');
        $st->execute([$_SESSION['user_id']]);
        $user = $st->fetch();
        if ($user && $user['role'] === ROLE_ADMIN) {
            $isAdmin = true;
        }
    }
}

if (!$isAdmin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Admin authentication required']);
    exit;
}

try {
    // JSON 파싱
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    $paymentId = $input['payment_id'] ?? null;
    $refundAmount = (int)($input['refund_amount'] ?? 0);
    $refundReason = trim($input['refund_reason'] ?? '');

    if (!$paymentId || !$refundAmount || !$refundReason) {
        throw new Exception('Missing required fields: payment_id, refund_amount, refund_reason');
    }

    $pdo = require dirname(__DIR__, 2) . '/database/connect.php';

    // 결제 정보 조회
    $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = ?');
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        throw new Exception('Payment not found');
    }

    // 이미 전액 환불된 경우 체크
    if ($payment['status'] === 'REFUNDED') {
        throw new Exception('Payment already fully refunded');
    }

    // 상태가 SUCCESS 또는 PARTIAL_REFUNDED가 아닌 경우 체크
    if (!in_array($payment['status'], ['SUCCESS', 'PARTIAL_REFUNDED'])) {
        throw new Exception('Payment status does not allow refund: ' . $payment['status']);
    }

    // 환불 금액이 결제 금액을 초과하는 경우 체크
    if ($refundAmount > $payment['amount']) {
        throw new Exception('Refund amount exceeds payment amount');
    }

    // 이미 일부 환불된 경우, 남은 금액보다 많이 환불하려고 하는 경우 체크
    $alreadyRefunded = (int)($payment['refund_amount'] ?? 0);
    $remainingAmount = $payment['amount'] - $alreadyRefunded;
    if ($refundAmount > $remainingAmount) {
        throw new Exception('Refund amount exceeds remaining amount: ' . number_format($remainingAmount) . '원');
    }

    // payment_key가 있으면 토스페이먼츠 API 호출
    if (!empty($payment['payment_key'])) {
        $url = 'https://api.tosspayments.com/v1/payments/' . urlencode($payment['payment_key']) . '/cancel';
        $credential = base64_encode(TOSS_SECRET_KEY . ':');

        $cancelData = [
            'cancelReason' => $refundReason
        ];

        // 부분 환불인 경우 금액 지정
        if ($refundAmount < $remainingAmount) {
            $cancelData['cancelAmount'] = $refundAmount;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . $credential,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($cancelData),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_PROXY => '',
            CURLOPT_NOPROXY => '*',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('토스페이먼츠 연결 오류: ' . $curlError);
        }

        $result = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMsg = $result['message'] ?? '알 수 없는 오류';
            throw new Exception('토스페이먼츠 환불 실패: ' . $errorMsg);
        }

        // 토스페이먼츠 응답에서 취소 상태 확인
        if (!isset($result['cancels']) || empty($result['cancels'])) {
            throw new Exception('토스페이먼츠 환불 응답 오류');
        }
    }

    // DB 업데이트
    $newRefundAmount = $alreadyRefunded + $refundAmount;
    $newStatus = ($newRefundAmount >= $payment['amount']) ? 'REFUNDED' : 'PARTIAL_REFUNDED';

    // 환불 사유 누적 (이력 형태로 저장)
    $timestamp = date('Y-m-d H:i');
    $newReasonEntry = "[{$timestamp}] " . number_format($refundAmount) . "원: {$refundReason}";
    $existingReason = $payment['refund_reason'] ?? '';
    $combinedReason = $existingReason ? $existingReason . "\n" . $newReasonEntry : $newReasonEntry;

    $updateStmt = $pdo->prepare('
        UPDATE payments
        SET
            status = ?,
            refund_amount = ?,
            refund_reason = ?,
            refunded_at = NOW()
        WHERE id = ?
    ');

    $updateStmt->execute([
        $newStatus,
        $newRefundAmount,
        $combinedReason,
        $paymentId
    ]);

    // 전액 환불인 경우 서비스 요청 상태도 취소로 변경
    if ($newStatus === 'REFUNDED' && !empty($payment['service_request_id'])) {
        $pdo->prepare('UPDATE service_requests SET status = ? WHERE id = ?')
            ->execute(['CANCELLED', $payment['service_request_id']]);
    }

    echo json_encode([
        'success' => true,
        'message' => $newStatus === 'REFUNDED' ? '전액 환불이 처리되었습니다.' : '부분 환불이 처리되었습니다.',
        'payment_id' => $paymentId,
        'refund_amount' => $refundAmount,
        'total_refund_amount' => $newRefundAmount,
        'status' => $newStatus
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

