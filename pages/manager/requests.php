<?php
/**
 * 서비스 요청 - 매니저 (지원 가능한 요청 목록)
 * URL: /manager/requests
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

// 진행중인 서비스 요청 조회 (PENDING, MATCHING 상태)
// 지정 도우미가 있는 요청은 제외 (designated_manager_id IS NULL)
// 이미 매칭 완료된 요청도 제외 (bookings 없는 것만)
$stmt = $pdo->prepare("
    SELECT sr.*, COALESCE(u.name, sr.guest_name, '비회원') as customer_name, COALESCE(u.phone, sr.guest_phone, '') as customer_phone
    FROM service_requests sr
    LEFT JOIN users u ON u.id = sr.customer_id
    LEFT JOIN bookings b ON b.request_id = sr.id
    WHERE sr.status IN ('PENDING', 'MATCHING')
    AND sr.designated_manager_id IS NULL
    AND b.id IS NULL
    ORDER BY sr.service_date ASC, sr.start_time ASC
    LIMIT 50
");
$stmt->execute();
$requests = $stmt->fetchAll();

// 이미 지원한 요청 ID 목록 (거절된 것은 제외)
$appliedStmt = $pdo->prepare("SELECT request_id FROM applications WHERE manager_id = ? AND status IN ('PENDING', 'ACCEPTED')");
$appliedStmt->execute([$managerId]);
$appliedIds = $appliedStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = '서비스 요청 - ' . APP_NAME;
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
                <a href="<?= $base ?>/manager/dashboard?tab=matching" class="px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700">내 매칭현황</a>
                <a href="<?= $base ?>/manager/schedule" class="px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700">내 근무기록</a>
            </div>
        </div>
    </nav>

    <!-- 메인 콘텐츠 -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold">서비스 요청</h2>
            <p class="text-gray-600 mt-1">현재 진행중인 서비스 요청입니다. 클릭하여 지원하세요.</p>
        </div>

        <?php if (count($requests) > 0): ?>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($requests as $request):
                $isApplied = in_array($request['id'], $appliedIds);
            ?>
            <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow <?= $isApplied ? 'opacity-60' : 'cursor-pointer request-card' ?>"
                 <?php if (!$isApplied): ?>
                 data-request-id="<?= htmlspecialchars($request['id']) ?>"
                 data-customer-name="<?= htmlspecialchars($request['customer_name']) ?>"
                 data-service-type="<?= htmlspecialchars($request['service_type']) ?>"
                 data-service-date="<?= htmlspecialchars($request['service_date']) ?>"
                 data-start-time="<?= htmlspecialchars($request['start_time']) ?>"
                 data-duration="<?= htmlspecialchars($request['duration_minutes']) ?>"
                 data-address="<?= htmlspecialchars($request['address']) ?>"
                 data-details="<?= htmlspecialchars($request['details'] ?? '') ?>"
                 data-price="<?= htmlspecialchars($request['estimated_price']) ?>"
                 <?php endif; ?>>

                <div class="flex items-start justify-between mb-3">
                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= $request['status'] === 'PENDING' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' ?>">
                        <?= $request['status'] === 'PENDING' ? '대기중' : '매칭중' ?>
                    </span>
                    <?php if ($isApplied): ?>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">지원완료</span>
                    <?php endif; ?>
                </div>

                <h3 class="font-semibold text-lg mb-2"><?= htmlspecialchars($request['service_type']) ?></h3>

                <div class="space-y-1 text-sm text-gray-600">
                    <p><span class="text-gray-400">고객:</span> <?= htmlspecialchars($request['customer_name']) ?></p>
                    <p><span class="text-gray-400">날짜:</span> <?= htmlspecialchars($request['service_date']) ?> <?= substr($request['start_time'], 0, 5) ?></p>
                    <p><span class="text-gray-400">시간:</span> <?= $request['duration_minutes'] ?>분</p>
                    <p><span class="text-gray-400">장소:</span> <?= htmlspecialchars(mb_substr($request['address'], 0, 20)) ?>...</p>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                    <span class="font-bold text-blue-600"><?= number_format($request['estimated_price']) ?>원</span>
                    <?php if (!$isApplied): ?>
                    <span class="text-xs text-gray-400">클릭하여 지원</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
            <p class="text-gray-500">현재 진행중인 서비스 요청이 없습니다.</p>
        </div>
        <?php endif; ?>
    </main>

    <!-- 지원하기 모달 -->
    <div id="applyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-bold">서비스 요청 상세</h2>
                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <span class="text-sm text-gray-500">서비스 종류</span>
                    <p id="modalServiceType" class="font-semibold text-lg"></p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">고객명</span>
                        <p id="modalCustomerName" class="font-medium"></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">예상 금액</span>
                        <p id="modalPrice" class="font-bold text-blue-600"></p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">서비스 날짜</span>
                        <p id="modalDate" class="font-medium"></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">소요 시간</span>
                        <p id="modalDuration" class="font-medium"></p>
                    </div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">주소</span>
                    <p id="modalAddress" class="font-medium"></p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">상세 요청사항</span>
                    <p id="modalDetails" class="font-medium text-gray-700"></p>
                </div>
                <div>
                    <label for="applyMessage" class="block text-sm text-gray-500 mb-1">지원 메시지 (선택)</label>
                    <textarea id="applyMessage" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none" placeholder="고객에게 전달할 메시지를 입력하세요."></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex gap-3">
                <button type="button" onclick="closeModal()" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium">취소</button>
                <button type="button" onclick="submitApplication()" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">지원하기</button>
            </div>
        </div>
    </div>

    <script>
    let currentRequestId = null;

    // 요청 카드 클릭
    document.querySelectorAll('.request-card').forEach(card => {
        card.addEventListener('click', function() {
            currentRequestId = this.dataset.requestId;

            document.getElementById('modalServiceType').textContent = this.dataset.serviceType;
            document.getElementById('modalCustomerName').textContent = this.dataset.customerName;
            document.getElementById('modalPrice').textContent = parseInt(this.dataset.price).toLocaleString() + '원';
            document.getElementById('modalDate').textContent = this.dataset.serviceDate + ' ' + this.dataset.startTime.substring(0, 5);
            document.getElementById('modalDuration').textContent = this.dataset.duration + '분';
            document.getElementById('modalAddress').textContent = this.dataset.address;
            document.getElementById('modalDetails').textContent = this.dataset.details || '없음';
            document.getElementById('applyMessage').value = '';

            document.getElementById('applyModal').classList.remove('hidden');
        });
    });

    function closeModal() {
        document.getElementById('applyModal').classList.add('hidden');
        currentRequestId = null;
    }

    function submitApplication() {
        if (!currentRequestId) return;

        const message = document.getElementById('applyMessage').value.trim();

        fetch('<?= $base ?>/api/manager/apply', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                request_id: currentRequestId,
                message: message
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('지원이 완료되었습니다!');
                location.reload();
            } else {
                alert('지원 실패: ' + (data.error || '알 수 없는 오류'));
            }
        })
        .catch(e => {
            console.error(e);
            alert('지원 중 오류가 발생했습니다.');
        });
    }

    // 모달 외부 클릭 시 닫기
    document.getElementById('applyModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    </script>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
