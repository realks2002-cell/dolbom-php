<?php
/**
 * 매니저 대시보드
 * URL: /manager/dashboard
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

init_session();

$base = rtrim(BASE_URL, '/');

// 매니저 로그인 체크
if (empty($_SESSION['manager_id'])) {
    redirect('/manager/login');
}

// PWA 사용 시 Vue.js 앱으로 리다이렉트
require_once dirname(__DIR__, 2) . '/includes/jwt.php';
$payload = [
    'sub' => (string)$_SESSION['manager_id'],
    'role' => 'manager',
    'exp' => time() + (30 * 24 * 3600),
];
$token = jwt_encode($payload, API_JWT_SECRET);
$viteAppUrl = getenv('VITE_APP_URL') ?: 'http://localhost:3000';
header('Location: ' . $viteAppUrl . '/?token=' . urlencode($token));
exit;

// 탭 파라미터 확인
$tab = $_GET['tab'] ?? '';

// 페이지네이션
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 매칭 대기중인 서비스 요청 (결제완료 상태, 매니저 미배정)
if ($tab === 'matching') {
    $requests = [];
    $totalCount = 0;
    $totalPages = 0;
} else {
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM service_requests sr
        WHERE sr.status IN ('CONFIRMED', 'MATCHING')
    ");
    $countStmt->execute();
    $totalCount = $countStmt->fetchColumn();
    $totalPages = ceil($totalCount / $perPage);

    $requestsStmt = $pdo->prepare("
        SELECT sr.id, sr.customer_id, sr.service_type, sr.service_date, sr.start_time, 
               sr.duration_minutes, sr.address, sr.address_detail, sr.lat, sr.lng,
               sr.details, sr.status, sr.estimated_price, sr.created_at, sr.updated_at,
               u.name as customer_name, u.phone as customer_phone
        FROM service_requests sr
        JOIN users u ON u.id = sr.customer_id
        WHERE sr.status IN ('CONFIRMED', 'MATCHING')
        ORDER BY sr.service_date ASC, sr.start_time ASC
        LIMIT :limit OFFSET :offset
    ");
    $requestsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $requestsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $requestsStmt->execute();
    $requests = $requestsStmt->fetchAll();
}

// 이미 지원한 요청 ID 목록 (managers 테이블의 id 사용)
$appliedStmt = $pdo->prepare("SELECT request_id FROM applications WHERE manager_id = ?");
$appliedStmt->execute([$managerId]);
$appliedIds = $appliedStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = '매니저 대시보드 - ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                <a href="<?= $base ?>/manager/dashboard" class="px-6 py-4 text-sm font-medium <?= $tab !== 'matching' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' ?>">홈</a>
                <a href="<?= $base ?>/manager/dashboard?tab=matching" class="px-6 py-4 text-sm font-medium <?= $tab === 'matching' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' ?>">내 매칭현황</a>
                <a href="<?= $base ?>/manager/schedule" class="px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700">내 근무기록</a>
                <a href="<?= $base ?>/manager/earnings" class="px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700">입금현황</a>
            </div>
        </div>
    </nav>

    <!-- 메인 콘텐츠 -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php if ($tab === 'matching'): ?>
        <!-- 매칭 현황 탭 -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold">내 매칭 현황</h2>
            <p class="text-gray-600 mt-1">지원한 서비스 요청의 매칭 상태를 확인하세요.</p>
        </div>
        <?php
        // 내가 지원한 요청 목록
        $myApplicationsStmt = $pdo->prepare("
            SELECT 
                a.id as application_id,
                a.request_id,
                a.status as application_status,
                a.created_at as application_created_at,
                sr.id,
                sr.service_type,
                sr.service_date,
                sr.start_time,
                sr.duration_minutes,
                sr.address,
                sr.status,
                sr.estimated_price,
                u.name as customer_name
            FROM applications a
            JOIN service_requests sr ON sr.id = a.request_id
            JOIN users u ON u.id = sr.customer_id
            WHERE a.manager_id = ?
            ORDER BY a.created_at DESC
        ");
        $myApplicationsStmt->execute([$managerId]);
        $myApplications = $myApplicationsStmt->fetchAll();
        ?>
        <?php if (count($myApplications) > 0): ?>
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">근무일시</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">서비스</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">고객</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">지원일</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">상태</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($myApplications as $app): ?>
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <?= htmlspecialchars($app['service_date']) ?>
                                <br><span class="text-gray-500"><?= substr($app['start_time'], 0, 5) ?></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($app['service_type']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($app['customer_name']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($app['application_created_at']) ?></td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                    echo match($app['status']) {
                                        'PENDING' => 'bg-yellow-100 text-yellow-800',
                                        'ACCEPTED' => 'bg-green-100 text-green-800',
                                        'REJECTED' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                ?>">
                                    <?php
                                    echo match($app['status']) {
                                        'PENDING' => '대기중',
                                        'ACCEPTED' => '수락됨',
                                        'REJECTED' => '거절됨',
                                        default => $app['status']
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
        <?php else: ?>
        <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
            <p class="text-gray-500">지원한 서비스 요청이 없습니다.</p>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <!-- 홈 탭 (서비스 요청 목록) -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold">서비스 요청</h2>
            <p class="text-gray-600 mt-1">매칭을 기다리는 고객의 서비스 요청입니다. 클릭하여 지원하세요.</p>
        </div>

        <?php if (count($requests) > 0): ?>
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">근무일시</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">서비스</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">고객</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">위치</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">특기사항</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">소요시간</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">금액</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">상태</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($requests as $request):
                            $isApplied = in_array($request['id'], $appliedIds);
                        ?>
                        <tr class="hover:bg-gray-50 <?= $isApplied ? 'opacity-60' : 'cursor-pointer request-row' ?>"
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
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <?= htmlspecialchars($request['service_date']) ?>
                                <br><span class="text-gray-500"><?= substr($request['start_time'], 0, 5) ?></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($request['service_type']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($request['customer_name']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars(mb_substr($request['address'], 0, 15)) ?>...</td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <?php if (!empty($request['details'])): ?>
                                    <span class="text-gray-700"><?= htmlspecialchars(mb_substr($request['details'], 0, 30)) ?><?= mb_strlen($request['details']) > 30 ? '...' : '' ?></span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <?php
                                $hours = floor($request['duration_minutes'] / 60);
                                $minutes = $request['duration_minutes'] % 60;
                                if ($hours > 0 && $minutes > 0) {
                                    echo $hours . '시간 ' . $minutes . '분';
                                } elseif ($hours > 0) {
                                    echo $hours . '시간';
                                } else {
                                    echo $minutes . '분';
                                }
                                ?>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-blue-600"><?= number_format($request['estimated_price']) ?>원</td>
                            <td class="px-4 py-3 text-sm">
                                <?php if ($isApplied): ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">지원완료</span>
                                <?php else: ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">매칭대기</span>
                                <?php endif; ?>
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
            <p class="text-gray-500">현재 매칭을 기다리는 서비스 요청이 없습니다.</p>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </main>

    <!-- 지원하기 모달 -->
    <div id="applyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-bold">서비스 지원</h2>
                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-6 space-y-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">서비스</span>
                            <p id="modalServiceType" class="font-semibold"></p>
                        </div>
                        <div>
                            <span class="text-gray-500">금액</span>
                            <p id="modalPrice" class="font-bold text-blue-600"></p>
                        </div>
                        <div>
                            <span class="text-gray-500">날짜/시간</span>
                            <p id="modalDate" class="font-medium"></p>
                        </div>
                        <div>
                            <span class="text-gray-500">소요시간</span>
                            <p id="modalDuration" class="font-medium"></p>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-500">고객</span>
                            <p id="modalCustomerName" class="font-medium"></p>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-500">주소</span>
                            <p id="modalAddress" class="font-medium"></p>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-500">요청사항</span>
                            <p id="modalDetails" class="font-medium text-gray-700"></p>
                        </div>
                    </div>
                </div>
                <div class="text-center py-4">
                    <p class="text-lg font-medium">이 서비스에 지원하시겠습니까?</p>
                    <p class="text-sm text-gray-500 mt-1">지원 후 고객 확인 시 매칭이 완료됩니다.</p>
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

    // 테이블 행 클릭 (홈 탭에서만 작동)
    <?php if ($tab !== 'matching'): ?>
    document.querySelectorAll('.request-row').forEach(row => {
        row.addEventListener('click', function() {
            currentRequestId = this.dataset.requestId;

            document.getElementById('modalServiceType').textContent = this.dataset.serviceType;
            document.getElementById('modalCustomerName').textContent = this.dataset.customerName;
            document.getElementById('modalPrice').textContent = parseInt(this.dataset.price).toLocaleString() + '원';
            document.getElementById('modalDate').textContent = this.dataset.serviceDate + ' ' + this.dataset.startTime.substring(0, 5);
            // 소요시간을 분에서 시간으로 변환
            var durationMinutes = parseInt(this.dataset.duration);
            var hours = Math.floor(durationMinutes / 60);
            var minutes = durationMinutes % 60;
            var durationText = '';
            if (hours > 0 && minutes > 0) {
                durationText = hours + '시간 ' + minutes + '분';
            } else if (hours > 0) {
                durationText = hours + '시간';
            } else {
                durationText = minutes + '분';
            }
            document.getElementById('modalDuration').textContent = durationText;
            document.getElementById('modalAddress').textContent = this.dataset.address;
            document.getElementById('modalDetails').textContent = this.dataset.details || '없음';

            document.getElementById('applyModal').classList.remove('hidden');
        });
    });
    <?php endif; ?>

    function closeModal() {
        document.getElementById('applyModal').classList.add('hidden');
        currentRequestId = null;
    }

    function submitApplication() {
        if (!currentRequestId) return;

        fetch('<?= $base ?>/api/manager/apply', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                request_id: currentRequestId,
                message: ''
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success || data.message) {
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
        if (e.target === this) closeModal();
    });
    </script>
</body>
</html>
