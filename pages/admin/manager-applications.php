<?php
/**
 * 매니저 지원확인 (승인/거절)
 * URL: /admin/manager-applications
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

require_admin();

$base = rtrim(BASE_URL, '/');
$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

// 필터
$status = $_GET['status'] ?? 'pending'; // pending, approved, rejected, all
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// WHERE 조건 구성
$where = "1=1";
$params = [];

if ($status !== 'all') {
    $where .= " AND approval_status = ?";
    $params[] = $status;
}

// 전체 개수
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM managers WHERE {$where}");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// 상태별 개수
$pendingCount = $pdo->query("SELECT COUNT(*) FROM managers WHERE approval_status = 'pending'")->fetchColumn();
$approvedCount = $pdo->query("SELECT COUNT(*) FROM managers WHERE approval_status = 'approved'")->fetchColumn();
$rejectedCount = $pdo->query("SELECT COUNT(*) FROM managers WHERE approval_status = 'rejected'")->fetchColumn();

// 매니저 목록 조회
$stmt = $pdo->prepare("
    SELECT * FROM managers
    WHERE {$where}
    ORDER BY created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$managers = $stmt->fetchAll();

$pageTitle = '매니저 지원확인 - ' . APP_NAME;
ob_start();
?>
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">매니저 지원확인</h1>
            <p class="text-gray-600 mt-1">신규 매니저 지원을 검토하고 승인/거절합니다</p>
        </div>
    </div>

    <!-- 필터 -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div class="flex gap-2 flex-wrap">
            <a href="?status=pending" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'pending' ? 'bg-primary text-white border-primary' : '' ?>">
                대기중 (<?= $pendingCount ?>)
            </a>
            <a href="?status=approved" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'approved' ? 'bg-primary text-white border-primary' : '' ?>">
                승인됨 (<?= $approvedCount ?>)
            </a>
            <a href="?status=rejected" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'rejected' ? 'bg-primary text-white border-primary' : '' ?>">
                거절됨 (<?= $rejectedCount ?>)
            </a>
            <a href="?status=all" class="min-h-[44px] px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center <?= $status === 'all' ? 'bg-primary text-white border-primary' : '' ?>">
                전체
            </a>
        </div>
    </div>

    <!-- 안내 메시지 -->
    <?php if ($status === 'pending' && $pendingCount > 0): ?>
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-blue-800">
            <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            매니저 정보를 확인 후 승인 또는 거절 버튼을 클릭하세요.
        </p>
    </div>
    <?php endif; ?>

    <!-- 매니저 목록 -->
    <?php if (count($managers) > 0): ?>
    <div class="space-y-4">
        <?php foreach ($managers as $mgr): ?>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-2">
                    <?php
                    $statusLabel = '';
                    $statusClass = '';
                    switch ($mgr['approval_status'] ?? 'pending') {
                        case 'pending':
                            $statusLabel = '대기중';
                            $statusClass = 'bg-yellow-100 text-yellow-800';
                            break;
                        case 'approved':
                            $statusLabel = '승인됨';
                            $statusClass = 'bg-green-100 text-green-800';
                            break;
                        case 'rejected':
                            $statusLabel = '거절됨';
                            $statusClass = 'bg-red-100 text-red-800';
                            break;
                    }
                    ?>
                    <span class="px-3 py-1 text-xs font-medium rounded-full <?= $statusClass ?>">
                        <?= $statusLabel ?>
                    </span>
                    <span class="text-sm text-gray-500">지원일시: <?= htmlspecialchars($mgr['created_at']) ?></span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- 프로필 사진 -->
                <div class="flex items-center gap-4">
                    <img
                        src="<?= $mgr['photo'] ? htmlspecialchars($base . $mgr['photo']) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23999%22%3E%3Cpath d=%22M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z%22/%3E%3C/svg%3E' ?>"
                        alt="<?= htmlspecialchars($mgr['name']) ?>"
                        class="w-20 h-20 rounded-full object-cover bg-gray-200"
                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23999%22%3E%3Cpath d=%22M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z%22/%3E%3C/svg%3E'"
                    >
                    <div>
                        <p class="font-semibold text-lg"><?= htmlspecialchars($mgr['name']) ?></p>
                        <p class="text-sm text-gray-600">
                            <?= $mgr['gender'] === 'M' ? '남성' : ($mgr['gender'] === 'F' ? '여성' : '-') ?>
                        </p>
                    </div>
                </div>

                <!-- 기본 정보 -->
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">연락처 정보</h3>
                    <dl class="space-y-1 text-sm">
                        <div class="flex">
                            <dt class="text-gray-600 w-20">전화번호</dt>
                            <dd class="font-medium"><?= htmlspecialchars($mgr['phone']) ?></dd>
                        </div>
                        <div class="flex">
                            <dt class="text-gray-600 w-20">주소</dt>
                            <dd class="font-medium"><?= htmlspecialchars($mgr['address1']) ?></dd>
                        </div>
                        <?php if (!empty($mgr['address2'])): ?>
                        <div class="flex">
                            <dt class="text-gray-600 w-20">상세주소</dt>
                            <dd class="font-medium"><?= htmlspecialchars($mgr['address2']) ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </div>

                <!-- 추가 정보 -->
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">계좌 및 특기</h3>
                    <dl class="space-y-1 text-sm">
                        <div class="flex">
                            <dt class="text-gray-600 w-16">은행</dt>
                            <dd class="font-medium"><?= htmlspecialchars($mgr['bank'] ?? '-') ?></dd>
                        </div>
                        <div class="flex">
                            <dt class="text-gray-600 w-16">계좌번호</dt>
                            <dd class="font-medium"><?= htmlspecialchars($mgr['account_number']) ?></dd>
                        </div>
                        <div class="flex">
                            <dt class="text-gray-600 w-16">특기</dt>
                            <dd class="font-medium text-primary"><?= htmlspecialchars($mgr['specialty'] ?? '-') ?></dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- 거절 사유 (거절된 경우에만 표시) -->
            <?php if (($mgr['approval_status'] ?? 'pending') === 'rejected' && !empty($mgr['rejection_reason'])): ?>
            <div class="mt-4 pt-4 border-t border-gray-200">
                <p class="text-sm text-red-600">
                    <strong>거절 사유:</strong> <?= htmlspecialchars($mgr['rejection_reason']) ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- 승인/거절 버튼 (대기중인 경우에만) -->
            <?php if (($mgr['approval_status'] ?? 'pending') === 'pending'): ?>
            <div class="mt-4 pt-4 border-t border-gray-200 flex gap-3">
                <button
                    type="button"
                    class="flex-1 min-h-[44px] bg-primary text-white rounded-lg px-4 py-2 font-medium hover:opacity-90 approve-btn"
                    data-manager-id="<?= htmlspecialchars($mgr['id']) ?>"
                    data-manager-name="<?= htmlspecialchars($mgr['name']) ?>"
                >
                    승인
                </button>
                <button
                    type="button"
                    class="flex-1 min-h-[44px] bg-red-500 text-white rounded-lg px-4 py-2 font-medium hover:opacity-90 reject-btn"
                    data-manager-id="<?= htmlspecialchars($mgr['id']) ?>"
                    data-manager-name="<?= htmlspecialchars($mgr['name']) ?>"
                >
                    거절
                </button>
            </div>
            <?php elseif (($mgr['approval_status'] ?? 'pending') === 'approved'): ?>
            <div class="mt-4 pt-4 border-t border-gray-200">
                <p class="text-sm text-green-600">
                    ✓ <?= $mgr['approved_at'] ? date('Y-m-d H:i', strtotime($mgr['approved_at'])) . ' 승인됨' : '승인됨' ?>
                </p>
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
        <p class="text-gray-600">
            <?php if ($status === 'pending'): ?>
                대기 중인 매니저 지원이 없습니다.
            <?php else: ?>
                해당 상태의 매니저가 없습니다.
            <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<!-- 승인 확인 모달 -->
<div id="approve-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-xl font-bold mb-4">매니저 승인</h2>
        <p class="text-gray-700 mb-6">
            <strong id="approve-manager-name"></strong>님을 매니저로 승인하시겠습니까?
        </p>
        <div class="flex gap-3">
            <button type="button" id="approve-modal-cancel" class="flex-1 min-h-[44px] border border-gray-300 rounded-lg px-4 py-2 font-medium text-gray-700 hover:bg-gray-50">
                취소
            </button>
            <button type="button" id="approve-modal-confirm" class="flex-1 min-h-[44px] bg-primary text-white rounded-lg px-4 py-2 font-medium hover:opacity-90">
                승인
            </button>
        </div>
    </div>
</div>

<!-- 거절 모달 -->
<div id="reject-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-xl font-bold mb-4">매니저 거절</h2>
        <p class="text-gray-700 mb-4">
            <strong id="reject-manager-name"></strong>님의 지원을 거절하시겠습니까?
        </p>
        <div class="mb-4">
            <label for="rejection-reason" class="block text-sm font-medium text-gray-700 mb-1">거절 사유 (선택)</label>
            <textarea
                id="rejection-reason"
                rows="3"
                class="block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="거절 사유를 입력하세요 (선택사항)"
            ></textarea>
        </div>
        <div class="flex gap-3">
            <button type="button" id="reject-modal-cancel" class="flex-1 min-h-[44px] border border-gray-300 rounded-lg px-4 py-2 font-medium text-gray-700 hover:bg-gray-50">
                취소
            </button>
            <button type="button" id="reject-modal-confirm" class="flex-1 min-h-[44px] bg-red-500 text-white rounded-lg px-4 py-2 font-medium hover:opacity-90">
                거절
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    var apiBase = <?= json_encode($base) ?>;
    var currentManagerId = null;

    // 승인 모달
    var approveModal = document.getElementById('approve-modal');
    var approveModalCancel = document.getElementById('approve-modal-cancel');
    var approveModalConfirm = document.getElementById('approve-modal-confirm');

    // 거절 모달
    var rejectModal = document.getElementById('reject-modal');
    var rejectModalCancel = document.getElementById('reject-modal-cancel');
    var rejectModalConfirm = document.getElementById('reject-modal-confirm');
    var rejectionReasonInput = document.getElementById('rejection-reason');

    // 승인 버튼 클릭
    document.querySelectorAll('.approve-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            currentManagerId = this.dataset.managerId;
            document.getElementById('approve-manager-name').textContent = this.dataset.managerName;
            approveModal.classList.remove('hidden');
        });
    });

    // 거절 버튼 클릭
    document.querySelectorAll('.reject-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            currentManagerId = this.dataset.managerId;
            document.getElementById('reject-manager-name').textContent = this.dataset.managerName;
            rejectionReasonInput.value = '';
            rejectModal.classList.remove('hidden');
        });
    });

    // 승인 모달 취소
    approveModalCancel.addEventListener('click', function() {
        approveModal.classList.add('hidden');
        currentManagerId = null;
    });

    // 승인 확정
    approveModalConfirm.addEventListener('click', function() {
        if (!currentManagerId) return;

        approveModalConfirm.disabled = true;
        approveModalConfirm.textContent = '처리 중...';

        fetch(apiBase + '/api/admin/approve-manager.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ manager_id: currentManagerId })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.ok) {
                alert(res.message || '승인되었습니다.');
                window.location.reload();
            } else {
                alert('오류: ' + (res.message || '승인에 실패했습니다.'));
                approveModalConfirm.disabled = false;
                approveModalConfirm.textContent = '승인';
            }
        })
        .catch(function(err) {
            console.error('승인 오류:', err);
            alert('승인 중 오류가 발생했습니다.');
            approveModalConfirm.disabled = false;
            approveModalConfirm.textContent = '승인';
        });
    });

    // 거절 모달 취소
    rejectModalCancel.addEventListener('click', function() {
        rejectModal.classList.add('hidden');
        currentManagerId = null;
    });

    // 거절 확정
    rejectModalConfirm.addEventListener('click', function() {
        if (!currentManagerId) return;

        rejectModalConfirm.disabled = true;
        rejectModalConfirm.textContent = '처리 중...';

        fetch(apiBase + '/api/admin/reject-manager.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                manager_id: currentManagerId,
                reason: rejectionReasonInput.value.trim()
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.ok) {
                alert(res.message || '거절되었습니다.');
                window.location.reload();
            } else {
                alert('오류: ' + (res.message || '거절에 실패했습니다.'));
                rejectModalConfirm.disabled = false;
                rejectModalConfirm.textContent = '거절';
            }
        })
        .catch(function(err) {
            console.error('거절 오류:', err);
            alert('거절 중 오류가 발생했습니다.');
            rejectModalConfirm.disabled = false;
            rejectModalConfirm.textContent = '거절';
        });
    });

    // 모달 배경 클릭 시 닫기
    approveModal.addEventListener('click', function(e) {
        if (e.target === approveModal) {
            approveModal.classList.add('hidden');
            currentManagerId = null;
        }
    });

    rejectModal.addEventListener('click', function(e) {
        if (e.target === rejectModal) {
            rejectModal.classList.add('hidden');
            currentManagerId = null;
        }
    });
})();
</script>

<?php
$layoutContent = ob_get_clean();
require_once dirname(__DIR__, 2) . '/components/admin-layout.php';
