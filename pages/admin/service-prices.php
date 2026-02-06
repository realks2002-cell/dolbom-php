<?php
/**
 * 서비스 가격 관리
 * URL: /admin/service-prices
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/service_prices.php';

require_admin();

$base = rtrim(BASE_URL, '/');
$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

$message = '';
$error = '';

// 서비스 유형별 설명
$serviceDescriptions = [
    '병원 동행' => '진료 예약부터 귀가까지 함께합니다',
    '가사돌봄' => '가사 활동을 도와드립니다',
    '생활동행' => '일상 생활 동행을 도와드립니다',
    '노인 돌봄' => '어르신의 일상을 도와드립니다',
    '아이 돌봄' => '안전하게 아이를 돌봐드립니다',
    '기타' => '기타 동행 및 돌봄 서비스',
];

$allowedTypes = array_keys($serviceDescriptions);

// 가격 업데이트 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $serviceType = trim($_POST['service_type'] ?? '');
    $pricePerHour = (int) ($_POST['price_per_hour'] ?? 0);

    if (!in_array($serviceType, $allowedTypes, true)) {
        $error = '유효하지 않은 서비스 유형입니다.';
    } elseif ($pricePerHour < 1000 || $pricePerHour > 100000) {
        $error = '가격은 1,000원 이상 100,000원 이하로 설정해주세요.';
    } else {
        try {
            if (update_service_price($pdo, $serviceType, $pricePerHour)) {
                $message = $serviceType . ' 가격이 ' . number_format($pricePerHour) . '원으로 변경되었습니다.';
            } else {
                $error = '가격 업데이트에 실패했습니다.';
            }
        } catch (Exception $e) {
            error_log('Service price update error: ' . $e->getMessage());
            $error = '가격 업데이트 중 오류가 발생했습니다.';
        }
    }
}

// 현재 가격 조회
$prices = get_all_service_prices_detailed($pdo);
if ($prices === false) {
    $error = '서비스 가격 정보를 불러오는데 실패했습니다. 마이그레이션을 실행해주세요.';
    $prices = [];
}

// 가격 배열을 service_type 기준으로 인덱싱
$pricesByType = [];
foreach ($prices as $price) {
    $pricesByType[$price['service_type']] = $price;
}

$pageTitle = '서비스 가격 관리 - ' . APP_NAME;
ob_start();
?>
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">서비스 가격 관리</h1>
    </div>

    <!-- 메시지 표시 -->
    <?php if ($message): ?>
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- 안내 문구 -->
    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-sm text-blue-800">
            <strong>서비스별 시간당 가격을 설정합니다.</strong><br>
            설정된 가격은 서비스 신청 시 자동으로 적용되며, 결제 금액에 반영됩니다.
        </p>
    </div>

    <!-- 가격 설정 카드 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($serviceDescriptions as $type => $description): ?>
        <?php
            $currentPrice = $pricesByType[$type]['price_per_hour'] ?? 20000;
            $isActive = ($pricesByType[$type]['is_active'] ?? 1) == 1;
            $updatedAt = $pricesByType[$type]['updated_at'] ?? null;
        ?>
        <div class="bg-white rounded-lg border border-gray-200 p-6 <?= !$isActive ? 'opacity-50' : '' ?>">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($type) ?></h3>
                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($description) ?></p>
                </div>
                <?php if (!$isActive): ?>
                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">비활성</span>
                <?php endif; ?>
            </div>

            <form method="post" class="space-y-4">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="service_type" value="<?= htmlspecialchars($type) ?>">

                <div>
                    <label for="price_<?= md5($type) ?>" class="block text-sm font-medium text-gray-700 mb-2">
                        시간당 가격 (원)
                    </label>
                    <div class="relative">
                        <input type="number"
                               id="price_<?= md5($type) ?>"
                               name="price_per_hour"
                               value="<?= (int) $currentPrice ?>"
                               min="1000"
                               max="100000"
                               step="1000"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent pr-12">
                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">원</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        현재: <strong><?= number_format($currentPrice) ?>원</strong>/시간
                    </p>
                </div>

                <?php if ($updatedAt): ?>
                <p class="text-xs text-gray-400">
                    최종 수정: <?= htmlspecialchars($updatedAt) ?>
                </p>
                <?php endif; ?>

                <button type="submit"
                        class="min-h-[44px] w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                    가격 저장
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 가격 요약 테이블 -->
    <div class="mt-8 bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold">가격 요약</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">서비스 유형</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">시간당 가격</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">3시간 기준</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">5시간 기준</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($serviceDescriptions as $type => $description): ?>
                    <?php $price = $pricesByType[$type]['price_per_hour'] ?? 20000; ?>
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($type) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 text-right">
                            <?= number_format($price) ?>원
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 text-right">
                            <?= number_format($price * 3) ?>원
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 text-right">
                            <?= number_format($price * 5) ?>원
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/admin-layout.php';
?>
