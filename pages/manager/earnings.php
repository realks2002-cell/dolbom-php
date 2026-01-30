<?php
/**
 * 입금현황 - 매니저
 * URL: /manager/earnings
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

init_session();

$base = rtrim(BASE_URL, '/');

// 매니저 로그인 체크
if (empty($_SESSION['manager_id'])) {
    redirect('/manager/login');
}

$pdo = require dirname(__DIR__, 2) . '/database/connect.php';
$managerId = $_SESSION['manager_id'];

// 정산 내역 조회 (settlements 테이블)
$stmt = $pdo->prepare("
    SELECT s.*, sr.service_type, sr.service_date, u.name as customer_name
    FROM settlements s
    JOIN bookings b ON b.id = s.booking_id
    JOIN service_requests sr ON sr.id = b.request_id
    JOIN users u ON u.id = sr.customer_id
    WHERE s.manager_id = ?
    ORDER BY s.created_at DESC
    LIMIT 50
");
$stmt->execute([$managerId]);
$settlements = $stmt->fetchAll();

// 통계
$statsStmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN status = 'COMPLETED' THEN net_amount ELSE 0 END) as total_earned,
        SUM(CASE WHEN status = 'PENDING' THEN net_amount ELSE 0 END) as pending_amount,
        COUNT(CASE WHEN status = 'COMPLETED' THEN 1 END) as completed_count
    FROM settlements
    WHERE manager_id = ?
");
$statsStmt->execute([$managerId]);
$stats = $statsStmt->fetch();

$pageTitle = '입금현황 - ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/tailwind.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/custom.css">
</head>
<body class="bg-gray-50">
    <!-- 헤더 -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <h1 class="text-xl font-bold text-blue-600"><?= APP_NAME ?> 매니저</h1>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600"><?= htmlspecialchars($_SESSION['manager_name'] ?? '') ?>님</span>
                <a href="<?= $base ?>/manager/logout" class="min-h-[44px] px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center">로그아웃</a>
            </div>
        </div>
    </header>

    <!-- 메뉴 탭 -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-1">
                <a href="<?= $base ?>/manager/dashboard" class="px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700">홈</a>
                <a href="<?= $base ?>/manager/dashboard?tab=matching" class="px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700">내 매칭현황</a>
                <a href="<?= $base ?>/manager/schedule" class="px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700">내 근무기록</a>
            </div>
        </div>
    </nav>

    <!-- 메인 콘텐츠 -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold">입금현황</h2>
            <p class="text-gray-600 mt-1">정산 및 입금 내역입니다.</p>
        </div>

        <!-- 통계 카드 -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <p class="text-sm text-gray-500 mb-1">총 수익</p>
                <p class="text-2xl font-bold text-blue-600"><?= number_format($stats['total_earned'] ?? 0) ?>원</p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <p class="text-sm text-gray-500 mb-1">정산 대기</p>
                <p class="text-2xl font-bold text-yellow-600"><?= number_format($stats['pending_amount'] ?? 0) ?>원</p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <p class="text-sm text-gray-500 mb-1">완료 건수</p>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['completed_count'] ?? 0) ?>건</p>
            </div>
        </div>

        <!-- 정산 내역 -->
        <?php if (count($settlements) > 0): ?>
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="font-semibold">정산 내역</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">날짜</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">서비스</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">고객</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">총액</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">수수료</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">정산금</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">상태</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($settlements as $settlement): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($settlement['service_date']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($settlement['service_type']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($settlement['customer_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= number_format($settlement['gross_amount']) ?>원</td>
                        <td class="px-4 py-3 text-sm text-red-600">-<?= number_format($settlement['platform_fee']) ?>원</td>
                        <td class="px-4 py-3 text-sm font-medium text-blue-600"><?= number_format($settlement['net_amount']) ?>원</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                echo match($settlement['status']) {
                                    'COMPLETED' => 'bg-green-100 text-green-800',
                                    'PENDING' => 'bg-yellow-100 text-yellow-800',
                                    'CANCELLED' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <?php
                                    echo match($settlement['status']) {
                                        'COMPLETED' => '입금완료',
                                        'PENDING' => '정산대기',
                                        'CANCELLED' => '취소됨',
                                        default => $settlement['status']
                                    };
                                ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
            <p class="text-gray-500">정산 내역이 없습니다.</p>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
