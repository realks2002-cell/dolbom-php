<?php
/**
 * 예약요청 및 매칭 현황
 * URL: /admin/requests
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
    $where .= " AND sr.status = ?";
    $params[] = $status;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM service_requests sr WHERE {$where}");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// 서비스 요청 조회 (매니저 정보 포함)
$stmt = $pdo->prepare("
    SELECT sr.*, COALESCE(u.name, sr.guest_name, '비회원') as customer_name, b.manager_id as assigned_manager_id
    FROM service_requests sr
    LEFT JOIN users u ON u.id = sr.customer_id
    LEFT JOIN bookings b ON b.request_id = sr.id
    WHERE {$where}
    ORDER BY sr.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$requests = $stmt->fetchAll();

// 각 요청에 대해 지원한 매니저 목록 조회
$requestIds = array_column($requests, 'id');
$applicationsMap = [];
if (!empty($requestIds)) {
    $placeholders = implode(',', array_fill(0, count($requestIds), '?'));
    $appStmt = $pdo->prepare("
        SELECT a.*, m.name as manager_name, m.phone as manager_phone,
               m.specialty, m.photo, m.gender, m.address1
        FROM applications a
        JOIN managers m ON m.id = a.manager_id
        WHERE a.request_id IN ({$placeholders})
        ORDER BY a.created_at ASC
    ");
    $appStmt->execute($requestIds);
    $applications = $appStmt->fetchAll();

    foreach ($applications as $app) {
        $applicationsMap[$app['request_id']][] = $app;
    }
}

$pageTitle = '예약요청 및 매칭 현황 - ' . APP_NAME;
ob_start();
?>
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">예약요청 및 매칭 현황</h1>
    </div>

    <!-- 필터 -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div class="flex gap-2 flex-wrap">
            <a href="<?= $base ?>/admin/requests" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= !$status ? 'bg-primary text-white border-primary' : '' ?>">전체</a>
            <a href="?status=MATCHING" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'MATCHING' ? 'bg-primary text-white border-primary' : '' ?>">매칭완료</a>
            <a href="?status=CONFIRMED" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'CONFIRMED' ? 'bg-primary text-white border-primary' : '' ?>">매칭대기(결제완료)</a>
            <a href="?status=COMPLETED" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'COMPLETED' ? 'bg-primary text-white border-primary' : '' ?>">서비스 완료</a>
            <a href="?status=CANCELLED" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'CANCELLED' ? 'bg-primary text-white border-primary' : '' ?>">취소</a>
        </div>
    </div>

    <!-- 요청 목록 -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">요청일시</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">근무일시</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">고객</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">서비스</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">일시</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">위치</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">지원 매니저</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">지원일시</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">상태</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">금액</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($requests) > 0): ?>
                    <?php foreach ($requests as $req):
                        $apps = $applicationsMap[$req['id']] ?? [];
                    ?>
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($req['created_at']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <?php
                            // 근무일시: service_date + start_time 조합
                            $workDateTime = $req['service_date'] . ' ' . substr($req['start_time'], 0, 5);
                            echo htmlspecialchars($workDateTime);
                            ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($req['customer_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($req['service_type']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($req['service_date']) ?> <?= htmlspecialchars(substr($req['start_time'], 0, 5)) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars(mb_substr($req['address'], 0, 20)) ?>...</td>
                        <td class="px-4 py-3 text-sm">
                            <?php if (count($apps) > 0): ?>
                                <div class="flex flex-wrap gap-1">
                                <?php foreach ($apps as $app): ?>
                                    <button type="button"
                                        class="manager-btn px-2 py-1 text-xs rounded-full cursor-pointer hover:opacity-80 <?= $app['status'] === 'ACCEPTED' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>"
                                        data-manager-id="<?= htmlspecialchars($app['manager_id']) ?>"
                                        data-manager-name="<?= htmlspecialchars($app['manager_name']) ?>"
                                        data-manager-phone="<?= htmlspecialchars($app['manager_phone']) ?>"
                                        data-manager-specialty="<?= htmlspecialchars($app['specialty'] ?? '') ?>"
                                        data-manager-photo="<?= htmlspecialchars($app['photo'] ?? '') ?>"
                                        data-manager-gender="<?= htmlspecialchars($app['gender'] ?? '') ?>"
                                        data-manager-address="<?= htmlspecialchars($app['address1'] ?? '') ?>"
                                        data-app-status="<?= htmlspecialchars($app['status']) ?>"
                                        data-app-message="<?= htmlspecialchars($app['message'] ?? '') ?>"
                                        data-app-date="<?= htmlspecialchars($app['created_at']) ?>">
                                        <?= htmlspecialchars($app['manager_name']) ?>
                                        <?= $app['status'] === 'ACCEPTED' ? '(확정)' : '' ?>
                                    </button>
                                <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <?php if (count($apps) > 0): ?>
                                <?php
                                // 가장 빠른 지원일시 표시 (첫 번째 지원)
                                $firstApp = $apps[0];
                                $appliedDate = $firstApp['created_at'];
                                // 날짜 형식 변환 (YYYY-MM-DD HH:MM:SS → YYYY-MM-DD HH:MM)
                                $formattedDate = date('Y-m-d H:i', strtotime($appliedDate));
                                ?>
                                <?= htmlspecialchars($formattedDate) ?>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                echo match($req['status']) {
                                    'PENDING' => 'bg-yellow-100 text-yellow-800',
                                    'MATCHING' => 'bg-blue-100 text-blue-800',
                                    'CONFIRMED' => 'bg-green-100 text-green-800',
                                    'COMPLETED' => 'bg-gray-100 text-gray-800',
                                    'CANCELLED' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <?php
                                    echo match($req['status']) {
                                        'PENDING' => '대기중',
                                        'MATCHING' => '매칭완료',
                                        'CONFIRMED' => '매칭대기(결제완료)',
                                        'COMPLETED' => '서비스 완료',
                                        'CANCELLED' => '취소',
                                        default => $req['status']
                                    };
                                ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= number_format($req['estimated_price']) ?>원</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-500">요청이 없습니다.</td>
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

<!-- 매니저 정보 모달 -->
<div id="managerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-bold">매니저 정보</h2>
            <button type="button" onclick="closeManagerModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="px-6 py-4">
            <div class="flex items-start gap-4 mb-4">
                <div id="managerPhoto" class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div>
                    <h3 id="managerName" class="text-xl font-bold"></h3>
                    <p id="managerGender" class="text-sm text-gray-500"></p>
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-500">전화번호</span>
                    <p id="managerPhone" class="font-medium"></p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">주소</span>
                    <p id="managerAddress" class="font-medium"></p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">특기</span>
                    <p id="managerSpecialty" class="font-medium"></p>
                </div>
                <div class="pt-3 border-t border-gray-200">
                    <span class="text-sm text-gray-500">지원 상태</span>
                    <p id="appStatus" class="font-medium"></p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">지원 일시</span>
                    <p id="appDate" class="font-medium"></p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">지원 메시지</span>
                    <p id="appMessage" class="font-medium text-gray-700"></p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            <button type="button" onclick="closeManagerModal()" class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">닫기</button>
        </div>
    </div>
</div>

<script>
// 매니저 버튼 클릭
document.querySelectorAll('.manager-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const name = this.dataset.managerName;
        const phone = this.dataset.managerPhone;
        const specialty = this.dataset.managerSpecialty;
        const photo = this.dataset.managerPhoto;
        const gender = this.dataset.managerGender;
        const address = this.dataset.managerAddress;
        const appStatus = this.dataset.appStatus;
        const appMessage = this.dataset.appMessage;
        const appDate = this.dataset.appDate;

        document.getElementById('managerName').textContent = name;
        document.getElementById('managerPhone').textContent = phone || '-';
        document.getElementById('managerSpecialty').textContent = specialty || '-';
        document.getElementById('managerAddress').textContent = address || '-';
        document.getElementById('managerGender').textContent = gender === 'M' ? '남성' : (gender === 'F' ? '여성' : '-');
        document.getElementById('appMessage').textContent = appMessage || '없음';
        document.getElementById('appDate').textContent = appDate;

        // 지원 상태
        const statusText = {
            'PENDING': '대기중',
            'ACCEPTED': '수락됨',
            'REJECTED': '거절됨'
        };
        document.getElementById('appStatus').textContent = statusText[appStatus] || appStatus;

        // 사진
        const photoDiv = document.getElementById('managerPhoto');
        if (photo) {
            photoDiv.innerHTML = '<img src="<?= $base ?>' + photo + '" class="w-full h-full object-cover" alt="' + name + '">';
        } else {
            photoDiv.innerHTML = '<svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>';
        }

        document.getElementById('managerModal').classList.remove('hidden');
    });
});

function closeManagerModal() {
    document.getElementById('managerModal').classList.add('hidden');
}

// 모달 외부 클릭 시 닫기
document.getElementById('managerModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeManagerModal();
    }
});
</script>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/admin-layout.php';
?>
