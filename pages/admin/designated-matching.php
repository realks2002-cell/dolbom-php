<?php
/**
 * 지정 도우미 매칭 관리
 * URL: /admin/designated-matching
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

require_admin();

$base = rtrim(BASE_URL, '/');
$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

// 필터
$status = $_GET['status'] ?? 'pending'; // pending, confirmed, all
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// WHERE 조건 구성
$where = "sr.designated_manager_id IS NOT NULL";
$params = [];

if ($status === 'pending') {
    $where .= " AND sr.status IN ('PENDING', 'CONFIRMED')"; // 결제 대기 + 결제 완료, 매칭 대기
} elseif ($status === 'confirmed') {
    $where .= " AND sr.status = 'MATCHING'"; // 매칭 확정
}

// 디버깅 정보는 APP_DEBUG 모드일 때만 활성화
if (defined('APP_DEBUG') && APP_DEBUG) {
    $debugStmt = $pdo->prepare("SELECT COUNT(*) as total, status, COUNT(*) as count FROM service_requests WHERE designated_manager_id IS NOT NULL GROUP BY status");
    $debugStmt->execute();
    $debugCounts = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
    error_log('지정 도우미 요청 상태별 집계: ' . json_encode($debugCounts));

    $allRecentStmt = $pdo->prepare("SELECT id, customer_id, designated_manager_id, service_type, service_date, status, created_at FROM service_requests ORDER BY created_at DESC LIMIT 10");
    $allRecentStmt->execute();
    $allRecent = $allRecentStmt->fetchAll(PDO::FETCH_ASSOC);
    error_log('최근 요청 10개: ' . json_encode($allRecent));
} else {
    $debugCounts = [];
    $allRecent = [];
}

// 전체 개수
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM service_requests sr WHERE {$where}");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// 서비스 요청 조회 (지정 도우미 정보 포함)
$stmt = $pdo->prepare("
    SELECT 
        sr.*,
        COALESCE(u.name, sr.guest_name, '비회원') as customer_name,
        COALESCE(u.phone, sr.guest_phone, '') as customer_phone,
        m.name as designated_manager_name,
        m.phone as designated_manager_phone,
        m.address1 as designated_manager_address,
        m.specialty as designated_manager_specialty,
        m.photo as designated_manager_photo,
        m.gender as designated_manager_gender,
        b.id as booking_id
    FROM service_requests sr
    LEFT JOIN users u ON u.id = sr.customer_id
    LEFT JOIN managers m ON m.id = sr.designated_manager_id
    LEFT JOIN bookings b ON b.request_id = sr.id
    WHERE {$where}
    ORDER BY sr.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$requests = $stmt->fetchAll();

$pageTitle = '지정 도우미 매칭 - ' . APP_NAME;
ob_start();
?>
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">지정 도우미 매칭</h1>
            <p class="text-gray-600 mt-1">고객이 직접 지정한 도우미와의 매칭을 확인하고 승인합니다</p>
        </div>
    </div>

    <!-- 필터 -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div class="flex gap-2 flex-wrap">
            <a href="?status=pending" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'pending' ? 'bg-primary text-white border-primary' : '' ?>">
                대기중 (<?= $status === 'pending' ? $total : '' ?>)
            </a>
            <a href="?status=confirmed" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'confirmed' ? 'bg-primary text-white border-primary' : '' ?>">
                확정됨
            </a>
            <a href="?status=all" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'all' ? 'bg-primary text-white border-primary' : '' ?>">
                전체
            </a>
        </div>
    </div>

    <!-- 디버깅: 상태별 집계 (APP_DEBUG 모드일 때만 표시) -->
    <?php if (defined('APP_DEBUG') && APP_DEBUG && (!empty($debugCounts) || !empty($allRecent))): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <p class="text-sm font-semibold text-yellow-900 mb-2">🔍 디버깅 정보 (개발 모드)</p>
        
        <?php if (!empty($debugCounts)): ?>
            <p class="text-sm text-yellow-800 font-semibold mb-1">지정 도우미 요청 (designated_manager_id NOT NULL):</p>
            <?php foreach ($debugCounts as $row): ?>
            <p class="text-sm text-yellow-800 ml-4">
                - 상태 <strong><?= htmlspecialchars($row['status']) ?></strong>: <?= $row['count'] ?>건
            </p>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-sm text-red-600 font-semibold">⚠️ designated_manager_id가 NULL이 아닌 요청이 없습니다!</p>
        <?php endif; ?>
        
        <?php if (!empty($allRecent)): ?>
            <p class="text-sm text-yellow-800 font-semibold mt-3 mb-1">최근 요청 10개:</p>
            <div class="text-xs text-yellow-700 ml-4 space-y-1">
                <?php foreach ($allRecent as $r): ?>
                <div class="font-mono">
                    ID: <?= substr($r['id'], 0, 8) ?>... | 
                    지정도우미: <?= $r['designated_manager_id'] ? '✅ ' . substr($r['designated_manager_id'], 0, 8) . '...' : '❌ NULL' ?> | 
                    상태: <?= htmlspecialchars($r['status']) ?> | 
                    일시: <?= htmlspecialchars($r['created_at']) ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- 안내 메시지 -->
    <?php if ($status === 'pending'): ?>
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-blue-800">
            <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            도우미에게 전화로 확인 후 "매칭 확정" 버튼을 클릭하세요. 이 요청들은 매니저 앱에 표시되지 않습니다.
        </p>
    </div>
    <?php endif; ?>

    <!-- 요청 목록 -->
    <?php if (count($requests) > 0): ?>
    <div class="space-y-4">
        <?php foreach ($requests as $req): ?>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <span class="px-3 py-1 text-xs font-medium rounded-full <?= $req['status'] === 'CONFIRMED' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                        <?= $req['status'] === 'CONFIRMED' ? '대기중' : '확정됨' ?>
                    </span>
                    <span class="ml-2 text-sm text-gray-500">요청일시: <?= htmlspecialchars($req['created_at']) ?></span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 고객 정보 -->
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">고객 정보</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-600">이름</dt>
                            <dd class="font-medium"><?= htmlspecialchars($req['customer_name']) ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">전화번호</dt>
                            <dd class="font-medium"><?= htmlspecialchars($req['customer_phone']) ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">서비스</dt>
                            <dd class="font-medium text-primary"><?= htmlspecialchars($req['service_type']) ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">일시</dt>
                            <dd class="font-medium"><?= htmlspecialchars($req['service_date']) ?> <?= htmlspecialchars($req['start_time']) ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">예상 시간</dt>
                            <dd class="font-medium"><?= (int)($req['duration_minutes'] / 60) ?>시간</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">위치</dt>
                            <dd class="font-medium text-xs"><?= htmlspecialchars($req['address']) ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">예상 금액</dt>
                            <dd class="font-bold text-primary"><?= number_format($req['estimated_price']) ?>원</dd>
                        </div>
                    </dl>
                </div>

                <!-- 지정 도우미 정보 -->
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">지정 도우미</h3>
                    <div class="flex items-start gap-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <img 
                            src="<?= $req['designated_manager_photo'] ? htmlspecialchars($req['designated_manager_photo']) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23999%22%3E%3Cpath d=%22M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z%22/%3E%3C/svg%3E' ?>" 
                            alt="<?= htmlspecialchars($req['designated_manager_name']) ?>" 
                            class="w-16 h-16 rounded-full object-cover bg-gray-200"
                            onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23999%22%3E%3Cpath d=%22M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z%22/%3E%3C/svg%3E'"
                        >
                        <div class="flex-1">
                            <p class="font-semibold text-lg"><?= htmlspecialchars($req['designated_manager_name']) ?></p>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($req['designated_manager_phone']) ?></p>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($req['designated_manager_address'] ?? '') ?></p>
                            <p class="text-sm text-primary mt-1"><?= htmlspecialchars($req['designated_manager_specialty'] ?? '') ?></p>
                        </div>
                    </div>
                    
                    <?php if ($req['status'] === 'CONFIRMED'): ?>
                    <div class="mt-4">
                        <button 
                            type="button" 
                            class="w-full min-h-[44px] bg-primary text-white rounded-lg px-4 py-3 font-medium hover:opacity-90 confirm-matching-btn"
                            data-request-id="<?= htmlspecialchars($req['id']) ?>"
                            data-customer-name="<?= htmlspecialchars($req['customer_name']) ?>"
                            data-manager-name="<?= htmlspecialchars($req['designated_manager_name']) ?>"
                            data-manager-phone="<?= htmlspecialchars($req['designated_manager_phone']) ?>"
                        >
                            매칭 확정
                        </button>
                        <p class="text-xs text-gray-500 mt-2 text-center">도우미와 전화 확인 후 클릭하세요</p>
                    </div>
                    <?php elseif ($req['status'] === 'MATCHING'): ?>
                    <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg text-center">
                        <p class="text-sm text-green-800 font-medium">✓ 매칭 확정 완료</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 상세 요청사항 -->
            <?php if (!empty($req['details'])): ?>
            <div class="mt-4 pt-4 border-t border-gray-200">
                <h4 class="font-semibold text-gray-900 mb-2">상세 요청사항</h4>
                <p class="text-sm text-gray-600 whitespace-pre-wrap"><?= htmlspecialchars($req['details']) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 페이지네이션 -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-6 flex justify-center gap-2">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?status=<?= urlencode($status) ?>&page=<?= $i ?>" 
           class="min-h-[44px] min-w-[44px] flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 <?= $i === $page ? 'bg-primary text-white border-primary' : '' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
        <svg class="mx-auto w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
        <p class="text-gray-600">지정 도우미 요청이 없습니다.</p>
    </div>
    <?php endif; ?>
</div>

<!-- 매칭 확정 모달 -->
<div id="confirm-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-xl font-bold mb-4">매칭 확정</h2>
        <div class="mb-6">
            <p class="text-gray-700 mb-4">
                도우미 <strong id="modal-manager-name"></strong> (<span id="modal-manager-phone"></span>)님과<br>
                고객 <strong id="modal-customer-name"></strong>님의 매칭을 확정하시겠습니까?
            </p>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                <p class="text-sm text-yellow-800">
                    <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    도우미와 전화로 확인하셨나요?
                </p>
            </div>
        </div>
        <div class="flex gap-3">
            <button type="button" id="modal-cancel" class="flex-1 min-h-[44px] border border-gray-300 rounded-lg px-4 py-2 font-medium text-gray-700 hover:bg-gray-50">
                취소
            </button>
            <button type="button" id="modal-confirm" class="flex-1 min-h-[44px] bg-primary text-white rounded-lg px-4 py-2 font-medium hover:opacity-90">
                확정
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    var apiBase = <?= json_encode($base) ?>;
    var modal = document.getElementById('confirm-modal');
    var modalCancel = document.getElementById('modal-cancel');
    var modalConfirm = document.getElementById('modal-confirm');
    var currentRequestId = null;

    // 매칭 확정 버튼 클릭
    document.querySelectorAll('.confirm-matching-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            currentRequestId = this.dataset.requestId;
            document.getElementById('modal-customer-name').textContent = this.dataset.customerName;
            document.getElementById('modal-manager-name').textContent = this.dataset.managerName;
            document.getElementById('modal-manager-phone').textContent = this.dataset.managerPhone;
            modal.classList.remove('hidden');
        });
    });

    // 모달 취소
    modalCancel.addEventListener('click', function() {
        modal.classList.add('hidden');
        currentRequestId = null;
    });

    // 모달 확정
    modalConfirm.addEventListener('click', function() {
        if (!currentRequestId) return;

        modalConfirm.disabled = true;
        modalConfirm.textContent = '처리 중...';

        fetch(apiBase + '/api/admin/confirm-designated-matching.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: currentRequestId })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.ok) {
                alert('매칭이 확정되었습니다.');
                window.location.reload();
            } else {
                alert('오류: ' + (res.message || '매칭 확정에 실패했습니다.'));
                modalConfirm.disabled = false;
                modalConfirm.textContent = '확정';
            }
        })
        .catch(function(err) {
            console.error('매칭 확정 오류:', err);
            alert('매칭 확정 중 오류가 발생했습니다.');
            modalConfirm.disabled = false;
            modalConfirm.textContent = '확정';
        });
    });

    // 모달 배경 클릭 시 닫기
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
            currentRequestId = null;
        }
    });
})();
</script>

<?php
$layoutContent = ob_get_clean();
require_once dirname(__DIR__, 2) . '/components/admin-layout.php';
