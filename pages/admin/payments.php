<?php
/**
 * 결제 내역 조회
 * URL: /admin/payments
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

require_admin();

$base = rtrim(BASE_URL, '/');
$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

// 필터
$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = "1=1";
$params = [];

if ($status) {
    $where .= " AND p.status = ?";
    $params[] = $status;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM payments p WHERE {$where}");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("
    SELECT 
        p.*, 
        COALESCE(u.name, sr.guest_name, '비회원') as customer_name,
        sr.service_type, 
        sr.service_date
    FROM payments p
    LEFT JOIN users u ON u.id = p.customer_id
    LEFT JOIN service_requests sr ON sr.id = p.service_request_id
    WHERE {$where}
    ORDER BY p.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

$pageTitle = '결제 내역 조회 - ' . APP_NAME;
ob_start();
?>
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">결제 내역 조회</h1>
    </div>
    
    <!-- 필터 -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div class="flex gap-2 flex-wrap">
            <a href="<?= $base ?>/admin/payments" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= !$status ? 'bg-primary text-white border-primary' : '' ?>">전체</a>
            <a href="?status=SUCCESS" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'SUCCESS' ? 'bg-primary text-white border-primary' : '' ?>">성공</a>
            <a href="?status=PENDING" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'PENDING' ? 'bg-primary text-white border-primary' : '' ?>">대기중</a>
            <a href="?status=FAILED" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'FAILED' ? 'bg-primary text-white border-primary' : '' ?>">실패</a>
            <a href="?status=PARTIAL_REFUNDED" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'PARTIAL_REFUNDED' ? 'bg-primary text-white border-primary' : '' ?>">부분환불</a>
            <a href="?status=REFUNDED" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'REFUNDED' ? 'bg-primary text-white border-primary' : '' ?>">전액환불</a>
        </div>
    </div>
    
    <!-- 결제 목록 -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">결제일시</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">고객</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">서비스</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">결제수단</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">금액</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">상태</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">환불금액</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">환불일시</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">작업</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($payments) > 0): ?>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($payment['paid_at'] ?? $payment['created_at']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($payment['customer_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($payment['service_type'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($payment['payment_method']) ?></td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= number_format($payment['amount']) ?>원</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                echo match($payment['status']) {
                                    'SUCCESS' => 'bg-green-100 text-green-800',
                                    'PENDING' => 'bg-yellow-100 text-yellow-800',
                                    'FAILED' => 'bg-red-100 text-red-800',
                                    'PARTIAL_REFUNDED' => 'bg-orange-100 text-orange-800',
                                    'REFUNDED' => 'bg-gray-100 text-gray-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <?php
                                    echo match($payment['status']) {
                                        'SUCCESS' => '결제완료',
                                        'PENDING' => '대기중',
                                        'FAILED' => '실패',
                                        'PARTIAL_REFUNDED' => '부분환불',
                                        'REFUNDED' => '전액환불',
                                        default => $payment['status']
                                    };
                                ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= $payment['refund_amount'] ? number_format($payment['refund_amount']) . '원' : '-' ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= $payment['refunded_at'] ? htmlspecialchars($payment['refunded_at']) : '-' ?></td>
                        <td class="px-4 py-3 text-sm text-center">
                            <?php
                            $canRefund = in_array($payment['status'], ['SUCCESS', 'PARTIAL_REFUNDED']);
                            $refunded = (int)($payment['refund_amount'] ?? 0);
                            $remaining = $payment['amount'] - $refunded;
                            ?>
                            <?php if ($canRefund && $remaining > 0): ?>
                            <button type="button" class="px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 refund-btn"
                                data-payment-id="<?= htmlspecialchars($payment['id']) ?>"
                                data-amount="<?= $payment['amount'] ?>"
                                data-refunded="<?= $refunded ?>"
                                data-remaining="<?= $remaining ?>">
                                <?= $refunded > 0 ? '추가환불' : '환불' ?>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">결제 내역이 없습니다.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 페이지네이션 -->
        <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-700">
                총 <?= number_format($total) ?>건 중 <?= number_format($offset + 1) ?>-<?= number_format(min($offset + $perPage, $total)) ?>건 표시
            </div>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= $status ? '&status=' . urlencode($status) : '' ?>" class="min-h-[44px] px-4 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center">이전</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?><?= $status ? '&status=' . urlencode($status) : '' ?>" class="min-h-[44px] px-4 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center">다음</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 환불 모달 -->
<div id="refundModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold">환불 처리</h2>
        </div>
        <div class="px-6 py-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">환불 방식</label>
                <div class="space-y-2">
                    <label class="inline-flex items-center">
                        <input type="radio" name="refundType" value="full" checked class="h-4 w-4 text-blue-600">
                        <span class="ml-2 text-sm">전체 환불 (<span id="fullAmount">0</span>원)</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="refundType" value="partial" class="h-4 w-4 text-blue-600">
                        <span class="ml-2 text-sm">부분 환불</span>
                    </label>
                </div>
            </div>
            <div id="partialAmountDiv" class="hidden">
                <label for="partialAmount" class="block text-sm font-medium text-gray-700 mb-1">환불 금액 (원)</label>
                <input type="number" id="partialAmount" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" min="0" max="0">
                <p class="text-xs text-gray-500 mt-1">최대 환불 금액: <span id="maxAmount">0</span>원</p>
            </div>
            <div>
                <label for="refundReason" class="block text-sm font-medium text-gray-700 mb-1">환불 사유 <span class="text-red-500">*</span></label>
                <textarea id="refundReason" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none" rows="3" placeholder="환불 사유를 입력해주세요."></textarea>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex gap-2 justify-end">
            <button type="button" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50" onclick="document.getElementById('refundModal').classList.add('hidden')">취소</button>
            <button type="button" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600" onclick="submitRefund()">환불 처리</button>
        </div>
    </div>
</div>

<script>
let currentRefundPaymentId = null;
let currentRefundAmount = 0;
let currentRefundedAmount = 0;
let currentRemainingAmount = 0;

// 환불 버튼 클릭
document.querySelectorAll('.refund-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        currentRefundPaymentId = this.dataset.paymentId;
        currentRefundAmount = parseInt(this.dataset.amount);
        currentRefundedAmount = parseInt(this.dataset.refunded) || 0;
        currentRemainingAmount = parseInt(this.dataset.remaining);

        document.getElementById('fullAmount').textContent = currentRemainingAmount.toLocaleString();
        document.getElementById('maxAmount').textContent = currentRemainingAmount.toLocaleString();
        document.getElementById('partialAmount').max = currentRemainingAmount;
        document.getElementById('partialAmount').value = '';
        document.getElementById('refundReason').value = '';
        document.querySelector('input[name="refundType"][value="full"]').checked = true;
        document.getElementById('partialAmountDiv').classList.add('hidden');

        // 이미 부분 환불된 경우 안내 표시
        const modalTitle = document.querySelector('#refundModal h2');
        if (currentRefundedAmount > 0) {
            modalTitle.textContent = '추가 환불 처리 (기환불: ' + currentRefundedAmount.toLocaleString() + '원)';
        } else {
            modalTitle.textContent = '환불 처리';
        }

        document.getElementById('refundModal').classList.remove('hidden');
    });
});

// 환불 방식 변경
document.querySelectorAll('input[name="refundType"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const partialDiv = document.getElementById('partialAmountDiv');
        if (this.value === 'partial') {
            partialDiv.classList.remove('hidden');
        } else {
            partialDiv.classList.add('hidden');
        }
    });
});

// 환불 처리
function submitRefund() {
    const refundType = document.querySelector('input[name="refundType"]:checked').value;
    const refundReason = document.getElementById('refundReason').value.trim();
    let refundAmount = currentRemainingAmount; // 남은 금액 전체

    if (!refundReason) {
        alert('환불 사유를 입력해주세요.');
        return;
    }

    if (refundType === 'partial') {
        refundAmount = parseInt(document.getElementById('partialAmount').value);
        if (!refundAmount || refundAmount <= 0 || refundAmount > currentRemainingAmount) {
            alert('올바른 환불 금액을 입력해주세요. (최대: ' + currentRemainingAmount.toLocaleString() + '원)');
            return;
        }
    }
    
    // API 호출
    fetch('<?= $base ?>/api/payments/refund', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            payment_id: currentRefundPaymentId,
            refund_amount: refundAmount,
            refund_reason: refundReason
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('환불이 처리되었습니다.');
            location.reload();
        } else {
            alert('환불 처리에 실패했습니다: ' + (data.error || '알 수 없는 오류'));
        }
    })
    .catch(e => {
        console.error(e);
        alert('환불 처리 중 오류가 발생했습니다.');
    });
}
</script>

<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/admin-layout.php';
?>
