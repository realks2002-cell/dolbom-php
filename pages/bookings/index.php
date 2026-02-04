<?php
/**
 * 내 예약 목록 (PRD 4.3)
 * URL: /bookings
 * 탭: 예정된 예약 / 완료된 예약 / 취소된 예약
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$base = rtrim(BASE_URL, '/');

if (!$currentUser) {
    redirect('/auth/login');
}

// 탭 선택 (기본값: 예정된 예약)
$tab = $_GET['tab'] ?? 'upcoming';
$allowedTabs = ['upcoming' => '예정된 예약', 'completed' => '완료된 예약', 'cancelled' => '취소된 예약'];
if (!isset($allowedTabs[$tab])) {
    $tab = 'upcoming';
}

// DB에서 예약 목록 가져오기
$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

// service_requests를 기준으로 예약 목록 조회 (bookings는 매니저 매칭 후 생성되므로)
$statusMap = [
    'upcoming' => ['CONFIRMED', 'MATCHING'], // 예정된 예약
    'completed' => ['COMPLETED'], // 완료된 예약
    'cancelled' => ['CANCELLED'], // 취소된 예약
];

$statuses = $statusMap[$tab] ?? ['CONFIRMED', 'MATCHING'];
$placeholders = implode(',', array_fill(0, count($statuses), '?'));

$st = $pdo->prepare("
    SELECT 
        sr.id,
        sr.service_type,
        sr.service_date,
        sr.start_time,
        sr.duration_minutes,
        sr.address,
        sr.address_detail,
        sr.status,
        sr.estimated_price,
        sr.created_at,
        b.id as booking_id,
        b.manager_id,
        b.final_price,
        b.payment_status,
        m.name as manager_name,
        p.status as payment_status_detail,
        p.paid_at,
        CASE 
            WHEN EXISTS (SELECT 1 FROM applications a WHERE a.request_id = sr.id) THEN 1
            ELSE 0
        END as has_applications
    FROM service_requests sr
    LEFT JOIN bookings b ON b.request_id = sr.id
    LEFT JOIN users m ON m.id = b.manager_id
    LEFT JOIN payments p ON p.service_request_id = sr.id
    WHERE sr.customer_id = ? 
    AND sr.status IN ($placeholders)
    ORDER BY sr.service_date DESC, sr.start_time DESC
");
// 회원 전용: customer_id로 조회 (비회원은 pages/bookings/guest-check.php 사용)
$params = array_merge([$currentUser['id']], $statuses);
$st->execute($params);
$bookings = $st->fetchAll();

$pageTitle = '내 예약 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50 py-8';
ob_start();
?>
<div class="mx-auto max-w-4xl px-4 sm:px-6">
    <h1 class="text-2xl font-bold">내 예약</h1>
    
    <!-- 탭 -->
    <div class="mt-6 flex gap-2 border-b">
        <?php foreach ($allowedTabs as $key => $label): ?>
        <a href="<?= $base ?>/bookings?tab=<?= $key ?>" 
           class="min-h-[44px] inline-flex items-center border-b-2 px-4 py-2 text-sm font-medium transition-colors <?= $tab === $key ? 'border-primary text-primary' : 'border-transparent text-gray-600 hover:text-gray-900' ?>">
            <?= htmlspecialchars($label) ?>
        </a>
        <?php endforeach; ?>
    </div>
    
    <!-- 예약 목록 -->
    <div class="mt-6">
        <?php if (count($bookings) === 0): ?>
        <div class="rounded-lg border border-gray-200 bg-white p-12 text-center">
            <p class="text-gray-500"><?= htmlspecialchars($allowedTabs[$tab]) ?>이 없습니다.</p>
            <?php if ($tab === 'upcoming'): ?>
            <a href="<?= $base ?>/requests/new" class="mt-4 inline-block rounded-lg bg-primary px-6 py-3 font-medium text-white hover:opacity-90">새 서비스 요청하기</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($bookings as $booking): ?>
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-semibold"><?= htmlspecialchars($booking['service_type']) ?></h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    <?= htmlspecialchars($booking['service_date']) ?> 
                                    <?= htmlspecialchars(substr($booking['start_time'], 0, 5)) ?>
                                    · 예상 <?= (int)($booking['duration_minutes'] / 60) ?>시간
                                </p>
                                <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars($booking['address']) ?></p>
                                <?php if ($booking['address_detail']): ?>
                                <p class="mt-0.5 text-xs text-gray-500"><?= htmlspecialchars($booking['address_detail']) ?></p>
                                <?php endif; ?>
                            </div>
                            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-medium <?php
                                // 상태에 따른 배지 색상 결정
                                if ($booking['status'] === 'MATCHING' || ($booking['status'] === 'CONFIRMED' && $booking['has_applications'])) {
                                    echo 'bg-blue-100 text-blue-800'; // 매칭완료
                                } elseif ($booking['status'] === 'CONFIRMED') {
                                    echo 'bg-yellow-100 text-yellow-800'; // 매칭 대기중
                                } elseif ($booking['status'] === 'COMPLETED') {
                                    echo 'bg-green-100 text-green-800'; // 완료
                                } elseif ($booking['status'] === 'CANCELLED') {
                                    echo 'bg-red-100 text-red-800'; // 취소됨
                                } else {
                                    echo 'bg-primary/10 text-primary';
                                }
                            ?>">
                                <?php
                                // 상태 라벨 결정
                                if ($booking['status'] === 'MATCHING' || ($booking['status'] === 'CONFIRMED' && $booking['has_applications'])) {
                                    echo '매니저 매칭완료';
                                } elseif ($booking['status'] === 'CONFIRMED') {
                                    echo '매칭 대기중';
                                } else {
                                    $statusLabels = [
                                        'COMPLETED' => '서비스 완료',
                                        'CANCELLED' => '취소됨'
                                    ];
                                    echo htmlspecialchars($statusLabels[$booking['status']] ?? $booking['status']);
                                }
                                ?>
                            </span>
                        </div>
                        
                        <?php if ($booking['manager_name']): ?>
                        <p class="mt-3 text-sm text-gray-700">
                            <span class="font-medium">매니저:</span> <?= htmlspecialchars($booking['manager_name']) ?>
                        </p>
                        <?php endif; ?>
                        
                        <div class="mt-3 flex flex-wrap gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">예상 금액:</span>
                                <span class="ml-1 font-semibold"><?= number_format($booking['estimated_price']) ?>원</span>
                            </div>
                            <?php if ($booking['final_price']): ?>
                            <div>
                                <span class="text-gray-600">최종 금액:</span>
                                <span class="ml-1 font-semibold"><?= number_format($booking['final_price']) ?>원</span>
                            </div>
                            <?php endif; ?>
                            <?php if ($booking['payment_status_detail'] === 'SUCCESS'): ?>
                            <div>
                                <span class="text-green-600">✓ 결제 완료</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex shrink-0 flex-col gap-2 sm:ml-4">
                        <?php if ($tab === 'upcoming' && in_array($booking['status'], ['CONFIRMED', 'MATCHING'], true)): ?>
                        <button type="button" 
                                class="booking-cancel-btn min-h-[44px] inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-4 text-sm font-medium text-red-700 hover:bg-red-50" 
                                data-request-id="<?= htmlspecialchars($booking['id']) ?>">
                            취소
                        </button>
                        <?php endif; ?>
                        <?php if ($tab === 'completed' && $booking['booking_id']): ?>
                        <a href="<?= $base ?>/bookings/review?booking_id=<?= urlencode($booking['booking_id']) ?>" class="min-h-[44px] inline-flex items-center justify-center rounded-lg bg-primary px-4 text-sm font-medium text-white hover:opacity-90">리뷰 작성</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    var apiBase = <?= json_encode($base) ?>;
    var cancelButtons = document.querySelectorAll('.booking-cancel-btn');
    
    cancelButtons.forEach(function(btn) {
        btn.addEventListener('click', async function() {
            var requestId = this.dataset.requestId;
            if (!requestId) return;
            
            if (!confirm('예약을 취소하시겠습니까? 결제 금액은 환불 처리됩니다.')) {
                return;
            }
            
            this.disabled = true;
            this.textContent = '처리 중...';
            
            try {
                var response = await fetch(apiBase + '/api/bookings/cancel', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: requestId })
                });
                
                var result = await response.json();
                
                if (result.ok && result.cancelled) {
                    if (result.refund_warning) {
                        alert(result.refund_warning);
                    } else {
                        alert('예약이 취소되었습니다. 환불이 진행됩니다.');
                    }
                    // 페이지 새로고침
                    window.location.reload();
                } else {
                    alert('취소 처리에 실패했습니다: ' + (result.error || '알 수 없는 오류'));
                    this.disabled = false;
                    this.textContent = '취소';
                }
            } catch (error) {
                console.error('취소 요청 오류:', error);
                alert('취소 처리 중 오류가 발생했습니다.');
                this.disabled = false;
                this.textContent = '취소';
            }
        });
    });
})();
</script>
<?php
$layoutContent = ob_get_clean();
require_once dirname(__DIR__, 2) . '/components/layout.php';
