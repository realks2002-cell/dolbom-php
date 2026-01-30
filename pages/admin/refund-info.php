<?php
/**
 * 환불정보 조회
 * URL: /admin/refund-info
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

$where = "p.status IN ('REFUNDED', 'PARTIAL_REFUNDED')";
$params = [];

if ($status === 'REFUNDED') {
    $where .= " AND p.status = 'REFUNDED'";
} elseif ($status === 'PARTIAL_REFUNDED') {
    $where .= " AND p.status = 'PARTIAL_REFUNDED'";
}

// 전체 개수 조회
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM payments p
    WHERE {$where}
");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// 환불 정보 조회
$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.amount,
        p.refund_amount,
        p.refund_reason,
        p.refunded_at,
        p.status,
        p.paid_at,
        p.created_at,
        u.name as customer_name,
        u.email as customer_email,
        sr.id as request_id,
        sr.service_type,
        sr.service_date,
        sr.address
    FROM payments p
    JOIN users u ON u.id = p.customer_id
    LEFT JOIN service_requests sr ON sr.id = p.service_request_id
    WHERE {$where}
    ORDER BY p.refunded_at DESC, p.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$refunds = $stmt->fetchAll();

$pageTitle = '환불정보 - ' . APP_NAME;
ob_start();
?>
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">환불정보</h1>
    </div>

    <!-- 필터 -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div class="flex gap-2 flex-wrap">
            <a href="<?= $base ?>/admin/refund-info" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= !$status ? 'bg-primary text-white border-primary' : '' ?>">전체</a>
            <a href="?status=REFUNDED" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'REFUNDED' ? 'bg-primary text-white border-primary' : '' ?>">전액환불</a>
            <a href="?status=PARTIAL_REFUNDED" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'PARTIAL_REFUNDED' ? 'bg-primary text-white border-primary' : '' ?>">부분환불</a>
        </div>
    </div>

    <!-- 환불 목록 -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">환불일시</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">고객</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">서비스</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">결제금액</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">환불금액</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">환불사유</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">상태</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($refunds) > 0): ?>
                    <?php foreach ($refunds as $refund): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <?= $refund['refunded_at'] ? htmlspecialchars($refund['refunded_at']) : htmlspecialchars($refund['created_at']) ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="font-medium text-gray-900"><?= htmlspecialchars($refund['customer_name']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($refund['customer_email']) ?></div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <div><?= htmlspecialchars($refund['service_type'] ?? '-') ?></div>
                            <?php if ($refund['service_date']): ?>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($refund['service_date']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= number_format($refund['amount']) ?>원</td>
                        <td class="px-4 py-3 text-sm font-medium text-red-600"><?= number_format($refund['refund_amount'] ?? 0) ?>원</td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <div class="max-w-xs truncate" title="<?= htmlspecialchars($refund['refund_reason'] ?? '') ?>">
                                <?= htmlspecialchars(mb_substr($refund['refund_reason'] ?? '-', 0, 30)) ?>
                                <?= mb_strlen($refund['refund_reason'] ?? '') > 30 ? '...' : '' ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                echo match($refund['status']) {
                                    'REFUNDED' => 'bg-gray-100 text-gray-800',
                                    'PARTIAL_REFUNDED' => 'bg-orange-100 text-orange-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <?php
                                    echo match($refund['status']) {
                                        'REFUNDED' => '전액환불',
                                        'PARTIAL_REFUNDED' => '부분환불',
                                        default => $refund['status']
                                    };
                                ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">환불 정보가 없습니다.</td>
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
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/admin-layout.php';
?>
