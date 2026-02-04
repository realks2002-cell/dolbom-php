<?php
/**
 * 취소/환불 요청 관리
 * URL: /admin/refund-info
 */
// 에러 표시 활성화 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once dirname(__DIR__, 2) . '/config/app.php';
    require_once dirname(__DIR__, 2) . '/includes/helpers.php';
    require_once dirname(__DIR__, 2) . '/includes/auth.php';

    require_admin();

    $base = rtrim(BASE_URL, '/');
    $pdo = require dirname(__DIR__, 2) . '/database/connect.php';

    // 필터
    $status = $_GET['status'] ?? 'pending'; // pending, completed, all
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 20;
    $offset = ($page - 1) * $perPage;

    // 환불 요청 상태별 조회
    $where = "1=1";
    $params = [];

    if ($status === 'pending') {
        // 취소되었지만 환불 미완료
        $where .= " AND sr.status = 'CANCELLED' AND (p.status = 'SUCCESS' OR p.status IS NULL)";
    } elseif ($status === 'completed') {
        // 환불 완료
        $where .= " AND p.status IN ('REFUNDED', 'PARTIAL_REFUNDED')";
    }

    $countStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT sr.id) 
        FROM service_requests sr
        LEFT JOIN payments p ON p.service_request_id = sr.id
        WHERE {$where}
    ");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $perPage);

    // 환불 요청 목록
    $stmt = $pdo->prepare("
        SELECT 
            sr.id as request_id,
            sr.service_type,
            sr.service_date,
            sr.start_time,
            sr.estimated_price,
            sr.status as request_status,
            sr.created_at,
            sr.updated_at,
            COALESCE(u.name, sr.guest_name, '비회원') as customer_name,
            u.phone as customer_phone,
            p.id as payment_id,
            p.payment_key,
            p.amount,
            p.payment_method,
            p.status as payment_status,
            p.paid_at,
            p.refund_amount,
            p.refund_reason,
            p.refunded_at
        FROM service_requests sr
        LEFT JOIN users u ON u.id = sr.customer_id
        LEFT JOIN payments p ON p.service_request_id = sr.id
        WHERE {$where}
        ORDER BY sr.updated_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
} catch (Exception $e) {
    die('오류 발생: ' . $e->getMessage() . '<br>파일: ' . $e->getFile() . '<br>라인: ' . $e->getLine());
}

$pageTitle = '취소/환불 요청 - ' . APP_NAME;
ob_start();
?>
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">취소/환불 요청</h1>
        <div class="text-sm text-gray-600">
            총 <?= number_format($total) ?>건
        </div>
    </div>
    
    <!-- 필터 탭 -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div class="flex gap-2 flex-wrap">
            <a href="<?= $base ?>/admin/refund-info?status=pending" 
               class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'pending' ? 'bg-primary text-white border-primary' : '' ?>">
                환불 대기중
            </a>
            <a href="<?= $base ?>/admin/refund-info?status=completed" 
               class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'completed' ? 'bg-primary text-white border-primary' : '' ?>">
                환불 완료
            </a>
            <a href="<?= $base ?>/admin/refund-info?status=all" 
               class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'all' ? 'bg-primary text-white border-primary' : '' ?>">
                전체
            </a>
        </div>
    </div>
    
    <!-- 환불 요청 목록 -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <?php if (count($requests) > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">취소일시</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">고객</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">서비스</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">결제정보</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">금액</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">환불상태</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">작업</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($requests as $req): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <?= htmlspecialchars(date('Y-m-d H:i', strtotime($req['updated_at']))) ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="font-medium text-gray-900"><?= htmlspecialchars($req['customer_name']) ?></div>
                            <?php if ($req['customer_phone']): ?>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($req['customer_phone']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="text-gray-900"><?= htmlspecialchars($req['service_type']) ?></div>
                            <div class="text-xs text-gray-500">
                                <?= htmlspecialchars($req['service_date']) ?> 
                                <?= htmlspecialchars(substr($req['start_time'], 0, 5)) ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?php if ($req['payment_id']): ?>
                            <div class="text-gray-900"><?= htmlspecialchars($req['payment_method']) ?></div>
                            <div class="text-xs text-gray-500">
                                <?= htmlspecialchars($req['paid_at'] ? date('Y-m-d H:i', strtotime($req['paid_at'])) : '-') ?>
                            </div>
                            <?php else: ?>
                            <span class="text-gray-400">결제 없음</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="font-medium text-gray-900">
                                <?= number_format($req['amount'] ?? $req['estimated_price']) ?>원
                            </div>
                            <?php if ($req['refund_amount']): ?>
                            <div class="text-xs text-green-600">
                                환불: <?= number_format($req['refund_amount']) ?>원
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?php if ($req['payment_status'] === 'REFUNDED'): ?>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                환불 완료
                            </span>
                            <?php if ($req['refunded_at']): ?>
                            <div class="text-xs text-gray-500 mt-1">
                                <?= htmlspecialchars(date('Y-m-d H:i', strtotime($req['refunded_at']))) ?>
                            </div>
                            <?php endif; ?>
                            <?php elseif ($req['payment_status'] === 'PARTIAL_REFUNDED'): ?>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                부분 환불
                            </span>
                            <?php elseif ($req['payment_id']): ?>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                환불 대기
                            </span>
                            <?php else: ?>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                결제 없음
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <?php if ($req['payment_id'] && $req['payment_status'] === 'SUCCESS'): ?>
                            <button type="button" 
                                    class="refund-btn min-h-[44px] px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium"
                                    data-payment-id="<?= htmlspecialchars($req['payment_id']) ?>"
                                    data-payment-key="<?= htmlspecialchars($req['payment_key']) ?>"
                                    data-amount="<?= htmlspecialchars($req['amount']) ?>"
                                    data-customer="<?= htmlspecialchars($req['customer_name']) ?>"
                                    data-service="<?= htmlspecialchars($req['service_type']) ?>">
                                수동 환불
                            </button>
                            <?php elseif ($req['payment_status'] === 'REFUNDED'): ?>
                            <span class="text-green-600 text-sm">완료</span>
                            <?php else: ?>
                            <span class="text-gray-400 text-sm">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 페이지네이션 -->
        <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-700">
                <?= number_format(($page - 1) * $perPage + 1) ?> - <?= number_format(min($page * $perPage, $total)) ?> / <?= number_format($total) ?>
            </div>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                <a href="?status=<?= $status ?>&page=<?= $page - 1 ?>" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">이전</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?status=<?= $status ?>&page=<?= $i ?>" 
                   class="px-3 py-1 border rounded <?= $i === $page ? 'bg-primary text-white border-primary' : 'border-gray-300 hover:bg-gray-50' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?status=<?= $status ?>&page=<?= $page + 1 ?>" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">다음</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="p-12 text-center text-gray-500">
            <?php if ($status === 'pending'): ?>
            환불 대기중인 요청이 없습니다.
            <?php elseif ($status === 'completed'): ?>
            환불 완료된 요청이 없습니다.
            <?php else: ?>
            취소/환불 요청이 없습니다.
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 환불 확인 모달 -->
<div id="refundModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold">수동 환불 처리</h2>
        </div>
        <div class="px-6 py-6">
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">고객: <span id="modal-customer" class="font-medium text-gray-900"></span></p>
                <p class="text-sm text-gray-600 mb-2">서비스: <span id="modal-service" class="font-medium text-gray-900"></span></p>
                <p class="text-sm text-gray-600 mb-4">환불 금액: <span id="modal-amount" class="font-medium text-red-600"></span></p>
            </div>
            
            <div class="mb-4">
                <label for="refund-reason" class="block text-sm font-medium text-gray-700 mb-2">환불 사유</label>
                <textarea id="refund-reason" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" 
                          rows="3" 
                          placeholder="환불 사유를 입력하세요">고객 요청에 의한 취소</textarea>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                <p class="text-sm text-yellow-800">
                    ⚠️ 토스페이먼츠 API를 통해 실제 환불이 처리됩니다.
                </p>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex gap-3">
            <button type="button" onclick="closeRefundModal()" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium">
                취소
            </button>
            <button type="button" onclick="processRefund()" class="flex-1 px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">
                환불 처리
            </button>
        </div>
    </div>
</div>

<script>
var apiBase = <?= json_encode($base) ?>;
var currentPaymentId = null;
var currentPaymentKey = null;
var currentAmount = null;

// 환불 버튼 클릭
document.querySelectorAll('.refund-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        currentPaymentId = this.dataset.paymentId;
        currentPaymentKey = this.dataset.paymentKey;
        currentAmount = this.dataset.amount;
        
        document.getElementById('modal-customer').textContent = this.dataset.customer;
        document.getElementById('modal-service').textContent = this.dataset.service;
        document.getElementById('modal-amount').textContent = parseInt(currentAmount).toLocaleString() + '원';
        
        document.getElementById('refundModal').classList.remove('hidden');
    });
});

function closeRefundModal() {
    document.getElementById('refundModal').classList.add('hidden');
    currentPaymentId = null;
    currentPaymentKey = null;
    currentAmount = null;
}

async function processRefund() {
    if (!currentPaymentId) return;
    
    var reason = document.getElementById('refund-reason').value.trim();
    if (!reason) {
        alert('환불 사유를 입력해주세요.');
        return;
    }
    
    if (!confirm('정말 환불 처리하시겠습니까?\n\n이 작업은 취소할 수 없습니다.')) {
        return;
    }
    
    try {
        var response = await fetch(apiBase + '/api/payments/refund.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                payment_id: currentPaymentId,
                refund_amount: parseInt(currentAmount),
                refund_reason: reason
            })
        });
        
        var result = await response.json();
        
        if (result.success) {
            alert('환불이 완료되었습니다.');
            location.reload();
        } else {
            alert('환불 처리 실패: ' + (result.error || '알 수 없는 오류'));
        }
    } catch (error) {
        console.error('환불 처리 오류:', error);
        alert('환불 처리 중 오류가 발생했습니다.');
    }
}

// 모달 외부 클릭 시 닫기
document.getElementById('refundModal').addEventListener('click', function(e) {
    if (e.target === this) closeRefundModal();
});
</script>
<?php
$layoutContent = ob_get_clean();
require_once dirname(__DIR__, 2) . '/components/admin-layout.php';
