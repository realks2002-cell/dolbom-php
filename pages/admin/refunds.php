<?php
/**
 * 결제 취소
 * URL: /admin/refunds
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

require_admin();

$base = rtrim(BASE_URL, '/');
$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

// 환불 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'])) {
    $paymentId = $_POST['payment_id'];
    $reason = $_POST['reason'] ?? '관리자 요청에 의한 취소';
    
    $stmt = $pdo->prepare('SELECT id, payment_key, amount, status FROM payments WHERE id = ?');
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();
    
    if ($payment && $payment['status'] === 'SUCCESS' && $payment['payment_key']) {
        // 토스페이먼츠 환불 API 호출
        $url = 'https://api.tosspayments.com/v1/payments/' . urlencode($payment['payment_key']) . '/cancel';
        $data = ['cancelReason' => $reason];
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
        curl_close($ch);
        
        if ($httpCode === 200) {
            $refundResult = json_decode($response, true);
            if (isset($refundResult['status']) && $refundResult['status'] === 'CANCELLED') {
                $pdo->prepare('UPDATE payments SET status = ?, refund_amount = ?, refund_reason = ?, refunded_at = NOW() WHERE id = ?')
                    ->execute(['REFUNDED', $payment['amount'], $reason, $paymentId]);
                
                // 서비스 요청 상태도 취소로 변경
                $pdo->prepare('UPDATE service_requests SET status = ? WHERE id = (SELECT service_request_id FROM payments WHERE id = ?)')
                    ->execute(['CANCELLED', $paymentId]);
                
                $success = '환불이 완료되었습니다.';
            } else {
                $error = '환불 처리 실패: ' . ($refundResult['message'] ?? '알 수 없는 오류');
            }
        } else {
            $error = '환불 API 호출 실패 (HTTP ' . $httpCode . ')';
        }
    } else {
        $error = '환불할 수 없는 결제입니다.';
    }
}

// 환불 가능한 결제 목록
$stmt = $pdo->query("
    SELECT p.*, u.name as customer_name, sr.service_type, sr.service_date
    FROM payments p
    JOIN users u ON u.id = p.customer_id
    LEFT JOIN service_requests sr ON sr.id = p.service_request_id
    WHERE p.status = 'SUCCESS'
    ORDER BY p.paid_at DESC
    LIMIT 50
");
$refundablePayments = $stmt->fetchAll();

$pageTitle = '결제 취소 - ' . APP_NAME;
ob_start();
?>
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">결제 취소</h1>
    </div>
    
    <?php if (isset($success)): ?>
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <!-- 환불 가능한 결제 목록 -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold">환불 가능한 결제</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">결제일시</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">고객</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">서비스</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">금액</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">작업</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($refundablePayments) > 0): ?>
                    <?php foreach ($refundablePayments as $payment): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($payment['paid_at'] ?? $payment['created_at']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($payment['customer_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($payment['service_type'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= number_format($payment['amount']) ?>원</td>
                        <td class="px-4 py-3 text-sm">
                            <form method="post" onsubmit="return confirm('정말 환불하시겠습니까?');" class="inline">
                                <input type="hidden" name="payment_id" value="<?= htmlspecialchars($payment['id']) ?>">
                                <input type="hidden" name="reason" value="관리자 요청에 의한 취소">
                                <button type="submit" class="min-h-[44px] px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">환불</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">환불 가능한 결제가 없습니다.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/admin-layout.php';
?>
