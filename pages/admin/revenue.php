<?php
/**
 * 일/월 매출 집계
 * URL: /admin/revenue
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

require_admin();

$base = rtrim(BASE_URL, '/');
$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

// 기간 선택
$period = $_GET['period'] ?? 'month'; // 'day' or 'month'
$year = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? date('m'));

if ($period === 'day') {
    // 일별 매출
    $stmt = $pdo->prepare("
        SELECT DATE(paid_at) as date, COUNT(*) as count, SUM(amount) as total
        FROM payments
        WHERE status = 'SUCCESS' 
        AND YEAR(paid_at) = ? 
        AND MONTH(paid_at) = ?
        GROUP BY DATE(paid_at)
        ORDER BY date DESC
    ");
    $stmt->execute([$year, $month]);
    $revenueData = $stmt->fetchAll();
    $title = "{$year}년 {$month}월 일별 매출";
} else {
    // 월별 매출
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, COUNT(*) as count, SUM(amount) as total
        FROM payments
        WHERE status = 'SUCCESS' 
        AND YEAR(paid_at) = ?
        GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
        ORDER BY month DESC
    ");
    $stmt->execute([$year]);
    $revenueData = $stmt->fetchAll();
    $title = "{$year}년 월별 매출";
}

// 전체 통계
$totalStats = $pdo->query("
    SELECT 
        COUNT(*) as total_count,
        SUM(amount) as total_revenue,
        SUM(CASE WHEN status = 'SUCCESS' THEN amount ELSE 0 END) as success_revenue,
        SUM(CASE WHEN status = 'REFUNDED' THEN refund_amount ELSE 0 END) as refunded_amount
    FROM payments
")->fetch();

$pageTitle = '일/월 매출 집계 - ' . APP_NAME;
ob_start();
?>
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">일/월 매출 집계</h1>
    </div>
    
    <!-- 통계 카드 -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">전체 결제 건수</div>
            <div class="text-2xl font-bold text-gray-900"><?= number_format($totalStats['total_count']) ?>건</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">성공 결제 금액</div>
            <div class="text-2xl font-bold text-green-600"><?= number_format($totalStats['success_revenue']) ?>원</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">환불 금액</div>
            <div class="text-2xl font-bold text-red-600"><?= number_format($totalStats['refunded_amount']) ?>원</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">순매출</div>
            <div class="text-2xl font-bold text-primary"><?= number_format($totalStats['success_revenue'] - $totalStats['refunded_amount']) ?>원</div>
        </div>
    </div>
    
    <!-- 기간 선택 -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <form method="get" class="flex gap-4 flex-wrap items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">보기 방식</label>
                <select name="period" class="min-h-[44px] px-4 border border-gray-300 rounded-lg">
                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>월별</option>
                    <option value="day" <?= $period === 'day' ? 'selected' : '' ?>>일별</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">년도</label>
                <select name="year" class="min-h-[44px] px-4 border border-gray-300 rounded-lg">
                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?>년</option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php if ($period === 'day'): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">월</label>
                <select name="month" class="min-h-[44px] px-4 border border-gray-300 rounded-lg">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $month === $m ? 'selected' : '' ?>><?= $m ?>월</option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php endif; ?>
            <button type="submit" class="min-h-[44px] px-6 bg-primary text-white rounded-lg hover:opacity-90">조회</button>
        </form>
    </div>
    
    <!-- 매출 목록 -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold"><?= htmlspecialchars($title) ?></h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= $period === 'day' ? '날짜' : '월' ?></th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">결제 건수</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">매출 금액</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($revenueData) > 0): ?>
                    <?php 
                    $totalCount = 0;
                    $totalAmount = 0;
                    foreach ($revenueData as $row): 
                        $totalCount += $row['count'];
                        $totalAmount += $row['total'];
                    ?>
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= htmlspecialchars($row[$period === 'day' ? 'date' : 'month']) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900"><?= number_format($row['count']) ?>건</td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-900"><?= number_format($row['total']) ?>원</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="bg-gray-50 font-semibold">
                        <td class="px-4 py-3 text-sm text-gray-900">합계</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900"><?= number_format($totalCount) ?>건</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900"><?= number_format($totalAmount) ?>원</td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">매출 데이터가 없습니다.</td>
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
