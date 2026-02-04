<?php
/**
 * 내 매칭현황 - 매니저
 * URL: /manager/matching
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

// 페이지네이션
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 내가 지원한 요청 총 개수
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE manager_id = ?");
$countStmt->execute([$managerId]);
$totalCount = $countStmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// 매칭현황 (내가 지원한 요청들)
$matchingStmt = $pdo->prepare("
    SELECT sr.*, COALESCE(u.name, sr.guest_name, '비회원') as customer_name, COALESCE(u.phone, sr.guest_phone, '') as customer_phone,
           a.id as application_id, a.status as app_status, a.message as app_message, a.created_at as applied_at
    FROM applications a
    JOIN service_requests sr ON sr.id = a.request_id
    LEFT JOIN users u ON u.id = sr.customer_id
    WHERE a.manager_id = ?
    ORDER BY a.created_at DESC
    LIMIT :limit OFFSET :offset
");
$matchingStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$matchingStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$matchingStmt->execute([$managerId]);
$matchingRequests = $matchingStmt->fetchAll();

$pageTitle = '내 매칭현황 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50';
ob_start();
?>
    <!-- 매니저 내부 헤더 -->
    <header class="bg-white border-b border-gray-200 sticky top-24 z-40">
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
                <a href="<?= $base ?>/manager/dashboard?tab=matching" class="px-6 py-4 text-sm font-medium text-blue-600 border-b-2 border-blue-600">내 매칭현황</a>
                <a href="<?= $base ?>/manager/schedule" class="px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700">내 근무기록</a>
            </div>
        </div>
    </nav>

    <!-- 메인 콘텐츠 -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold">내 매칭현황</h2>
            <p class="text-gray-600 mt-1">내가 지원한 서비스 요청 현황입니다.</p>
        </div>

        <?php if (count($matchingRequests) > 0): ?>
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">서비스 일시</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">서비스</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">고객</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">위치</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">금액</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">지원상태</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">요청상태</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($matchingRequests as $req): ?>
                        <tr class="hover:bg-gray-50 cursor-pointer matching-row"
                            data-request-id="<?= htmlspecialchars($req['id']) ?>"
                            data-customer-name="<?= htmlspecialchars($req['customer_name']) ?>"
                            data-customer-phone="<?= htmlspecialchars($req['customer_phone']) ?>"
                            data-service-type="<?= htmlspecialchars($req['service_type']) ?>"
                            data-service-date="<?= htmlspecialchars($req['service_date']) ?>"
                            data-start-time="<?= htmlspecialchars($req['start_time']) ?>"
                            data-duration="<?= htmlspecialchars($req['duration_minutes']) ?>"
                            data-address="<?= htmlspecialchars($req['address']) ?>"
                            data-address-detail="<?= htmlspecialchars($req['address_detail'] ?? '') ?>"
                            data-details="<?= htmlspecialchars($req['details'] ?? '') ?>"
                            data-price="<?= htmlspecialchars($req['estimated_price']) ?>"
                            data-app-status="<?= htmlspecialchars($req['app_status']) ?>"
                            data-app-message="<?= htmlspecialchars($req['app_message'] ?? '') ?>"
                            data-applied-at="<?= htmlspecialchars($req['applied_at']) ?>">
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <?= htmlspecialchars($req['service_date']) ?>
                                <br><span class="text-gray-500"><?= substr($req['start_time'], 0, 5) ?></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($req['service_type']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($req['customer_name']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars(mb_substr($req['address'], 0, 15)) ?>...</td>
                            <td class="px-4 py-3 text-sm font-medium text-blue-600"><?= number_format($req['estimated_price']) ?>원</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                    echo match($req['app_status']) {
                                        'PENDING' => 'bg-yellow-100 text-yellow-800',
                                        'ACCEPTED' => 'bg-green-100 text-green-800',
                                        'REJECTED' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                ?>">
                                    <?php
                                        echo match($req['app_status']) {
                                            'PENDING' => '대기중',
                                            'ACCEPTED' => '수락됨',
                                            'REJECTED' => '거절됨',
                                            default => $req['app_status']
                                        };
                                    ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                    echo match($req['status']) {
                                        'PENDING' => 'bg-yellow-100 text-yellow-800',
                                        'CONFIRMED' => 'bg-blue-100 text-blue-800',
                                        'MATCHING' => 'bg-green-100 text-green-800',
                                        'COMPLETED' => 'bg-gray-100 text-gray-800',
                                        'CANCELLED' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                ?>">
                                    <?php
                                        echo match($req['status']) {
                                            'PENDING' => '대기중',
                                            'CONFIRMED' => '매칭대기',
                                            'MATCHING' => '매칭완료',
                                            'COMPLETED' => '서비스완료',
                                            'CANCELLED' => '취소',
                                            default => $req['status']
                                        };
                                    ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 페이지네이션 -->
        <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex justify-center gap-2">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">이전</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="?page=<?= $i ?>" class="px-4 py-2 border rounded-lg <?= $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">다음</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
            <p class="text-gray-500">지원한 서비스 요청이 없습니다.</p>
            <a href="<?= $base ?>/manager/dashboard" class="text-blue-600 hover:underline mt-2 inline-block">서비스 요청 보러가기</a>
        </div>
        <?php endif; ?>
    </main>

    <!-- 상세 모달 -->
    <div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-bold">지원 상세</h2>
                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <span class="text-sm text-gray-500">서비스 종류</span>
                    <p id="detailServiceType" class="font-semibold text-lg"></p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">고객명</span>
                        <p id="detailCustomerName" class="font-medium"></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">연락처</span>
                        <p id="detailCustomerPhone" class="font-medium"></p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">서비스 날짜</span>
                        <p id="detailDate" class="font-medium"></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">소요 시간</span>
                        <p id="detailDuration" class="font-medium"></p>
                    </div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">주소</span>
                    <p id="detailAddress" class="font-medium"></p>
                    <p id="detailAddressDetail" class="text-sm text-gray-600"></p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">상세 요청사항</span>
                    <p id="detailDetails" class="font-medium text-gray-700"></p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">예상 금액</span>
                    <p id="detailPrice" class="font-bold text-blue-600 text-xl"></p>
                </div>
                <div class="pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-500">지원 상태</span>
                            <p id="detailAppStatus" class="font-medium"></p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">지원 일시</span>
                            <p id="detailAppliedAt" class="font-medium"></p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-sm text-gray-500">내 지원 메시지</span>
                        <p id="detailAppMessage" class="font-medium text-gray-700"></p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200">
                <button type="button" onclick="closeModal()" class="w-full px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">닫기</button>
            </div>
        </div>
    </div>

    <script>
    // 매칭현황 행 클릭 (상세보기)
    document.querySelectorAll('.matching-row').forEach(row => {
        row.addEventListener('click', function() {
            document.getElementById('detailServiceType').textContent = this.dataset.serviceType;
            document.getElementById('detailCustomerName').textContent = this.dataset.customerName;
            document.getElementById('detailCustomerPhone').textContent = this.dataset.customerPhone || '-';
            document.getElementById('detailDate').textContent = this.dataset.serviceDate + ' ' + this.dataset.startTime.substring(0, 5);
            document.getElementById('detailDuration').textContent = this.dataset.duration + '분';
            document.getElementById('detailAddress').textContent = this.dataset.address;
            document.getElementById('detailAddressDetail').textContent = this.dataset.addressDetail || '';
            document.getElementById('detailDetails').textContent = this.dataset.details || '없음';
            document.getElementById('detailPrice').textContent = parseInt(this.dataset.price).toLocaleString() + '원';
            document.getElementById('detailAppliedAt').textContent = this.dataset.appliedAt;
            document.getElementById('detailAppMessage').textContent = this.dataset.appMessage || '없음';

            const appStatusText = {
                'PENDING': '대기중',
                'ACCEPTED': '수락됨',
                'REJECTED': '거절됨'
            };
            document.getElementById('detailAppStatus').textContent = appStatusText[this.dataset.appStatus] || this.dataset.appStatus;

            document.getElementById('detailModal').classList.remove('hidden');
        });
    });

    function closeModal() {
        document.getElementById('detailModal').classList.add('hidden');
    }

    // 모달 외부 클릭 시 닫기
    document.getElementById('detailModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    </script>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
