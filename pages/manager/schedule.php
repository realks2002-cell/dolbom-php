<?php
/**
 * 내 근무기록 - 매니저
 * URL: /manager/schedule
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

// 완료된 서비스 조회 (bookings 테이블에서)
$stmt = $pdo->prepare("
    SELECT b.*, sr.service_type, sr.service_date, sr.start_time, sr.duration_minutes,
           sr.address, COALESCE(u.name, sr.guest_name, '비회원') as customer_name
    FROM bookings b
    JOIN service_requests sr ON sr.id = b.request_id
    LEFT JOIN users u ON u.id = sr.customer_id
    WHERE b.manager_id = ?
    ORDER BY sr.service_date DESC, sr.start_time DESC
    LIMIT 50
");
$stmt->execute([$managerId]);
$bookings = $stmt->fetchAll();

$pageTitle = '내 근무기록 - ' . APP_NAME;
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
                <a href="<?= $base ?>/manager/schedule" class="px-6 py-4 text-sm font-medium text-blue-600 border-b-2 border-blue-600">내 근무기록</a>
            </div>
        </div>
    </nav>

    <!-- 메인 콘텐츠 -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold">내 근무기록</h2>
            <p class="text-gray-600 mt-1">완료한 서비스 내역입니다.</p>
        </div>

        <?php if (count($bookings) > 0): ?>
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">날짜</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">서비스</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">고객</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">시간</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">금액</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">상태</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <?= htmlspecialchars($booking['service_date']) ?>
                            <br><span class="text-gray-500"><?= substr($booking['start_time'], 0, 5) ?></span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($booking['service_type']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($booking['customer_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= $booking['duration_minutes'] ?>분</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= number_format($booking['final_price']) ?>원</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                echo match($booking['payment_status']) {
                                    'PAID' => 'bg-green-100 text-green-800',
                                    'PENDING' => 'bg-yellow-100 text-yellow-800',
                                    'REFUNDED' => 'bg-gray-100 text-gray-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <?php
                                    echo match($booking['payment_status']) {
                                        'PAID' => '결제완료',
                                        'PENDING' => '결제대기',
                                        'REFUNDED' => '환불됨',
                                        default => $booking['payment_status']
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
            <p class="text-gray-500">근무기록이 없습니다.</p>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
