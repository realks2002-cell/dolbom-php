<?php
/**
 * 관리자 대시보드
 * URL: /admin
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

require_admin();

$base = rtrim(BASE_URL, '/');
$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

// 통계 조회
$stats = [
    'total_users' => $pdo->query('SELECT COUNT(*) FROM users WHERE role = "CUSTOMER"')->fetchColumn(),
    'total_managers' => $pdo->query('SELECT COUNT(*) FROM users WHERE role = "MANAGER"')->fetchColumn(),
    'pending_requests' => $pdo->query('SELECT COUNT(*) FROM service_requests WHERE status = "PENDING"')->fetchColumn(),
    'total_revenue' => $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = "SUCCESS"')->fetchColumn(),
];

$pageTitle = '관리자 대시보드 - ' . APP_NAME;
ob_start();
?>
<div class="max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">대시보드</h1>
    
    <!-- 통계 카드 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">전체 회원</div>
            <div class="text-3xl font-bold text-gray-900"><?= number_format($stats['total_users']) ?></div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">전체 매니저</div>
            <div class="text-3xl font-bold text-gray-900"><?= number_format($stats['total_managers']) ?></div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">대기 중인 요청</div>
            <div class="text-3xl font-bold text-primary"><?= number_format($stats['pending_requests']) ?></div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">총 매출</div>
            <div class="text-3xl font-bold text-green-600"><?= number_format($stats['total_revenue']) ?>원</div>
        </div>
    </div>
    
    <!-- 최근 요청 -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold mb-4">최근 서비스 요청</h2>
        <?php
        $recentRequests = $pdo->query("
            SELECT sr.*, u.name as customer_name
            FROM service_requests sr
            JOIN users u ON u.id = sr.customer_id
            ORDER BY sr.created_at DESC
            LIMIT 10
        ")->fetchAll();
        ?>
        <?php if (count($recentRequests) > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">요청일시</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">고객</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">서비스</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">일시</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">상태</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">금액</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recentRequests as $req): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($req['created_at']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($req['customer_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($req['service_type']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($req['service_date']) ?> <?= htmlspecialchars(substr($req['start_time'], 0, 5)) ?></td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                echo match($req['status']) {
                                    'PENDING' => 'bg-yellow-100 text-yellow-800',
                                    'CONFIRMED' => 'bg-blue-100 text-blue-800',
                                    'COMPLETED' => 'bg-green-100 text-green-800',
                                    'CANCELLED' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <?= htmlspecialchars($req['status']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= number_format($req['estimated_price']) ?>원</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-gray-500 text-center py-8">최근 요청이 없습니다.</p>
        <?php endif; ?>
    </div>
</div>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/admin-layout.php';
?>
