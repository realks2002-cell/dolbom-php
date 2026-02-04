<?php
/**
 * 예약 조회 (회원/비회원 통합)
 * URL: /bookings/guest-check
 * 로그인 없이 예약번호와 전화번호로 조회 가능
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/security.php';

$base = rtrim(BASE_URL, '/');
$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

$error = '';
$booking = null;
$bookings = [];

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = trim((string) ($_POST['request_id'] ?? ''));
    $guestPhone = trim((string) ($_POST['guest_phone'] ?? ''));
    
    // 전화번호 정규화 (하이픈 제거)
    $normalizedPhone = normalize_phone($guestPhone);
    
    // 둘 중 하나는 필수
    if ($requestId === '' && $normalizedPhone === '') {
        $error = '예약번호 또는 전화번호 중 하나를 입력해주세요.';
    } else {
        // 동적 WHERE 조건 생성
        $whereConditions = [];
        $params = [];
        
        if ($requestId !== '') {
            $whereConditions[] = 'sr.id = ?';
            $params[] = $requestId;
        }
        
        if ($normalizedPhone !== '') {
            $whereConditions[] = '(sr.guest_phone = ? OR sr.phone = ? OR u.phone = ?)';
            $params[] = $normalizedPhone;
            $params[] = $normalizedPhone;
            $params[] = $normalizedPhone;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // 회원/비회원 통합 예약 조회
        $st = $pdo->prepare("
            SELECT 
                sr.id,
                sr.service_type,
                sr.service_date,
                sr.start_time,
                sr.duration_minutes,
                sr.address,
                sr.address_detail,
                sr.estimated_price,
                sr.status,
                sr.guest_name,
                sr.guest_phone,
                sr.phone,
                sr.created_at,
                u.name as customer_name,
                u.phone as customer_phone,
                p.id as payment_id,
                p.amount,
                p.payment_method,
                p.status as payment_status,
                p.paid_at,
                p.refund_amount,
                p.refunded_at,
                b.id as booking_id,
                b.manager_id,
                m.name as manager_name,
                m.phone as manager_phone
            FROM service_requests sr
            LEFT JOIN users u ON u.id = sr.customer_id
            LEFT JOIN payments p ON p.service_request_id = sr.id
            LEFT JOIN bookings b ON b.request_id = sr.id
            LEFT JOIN managers m ON m.id = b.manager_id
            WHERE {$whereClause}
            ORDER BY sr.service_date DESC, sr.start_time DESC
        ");
        $st->execute($params);
        $bookings = $st->fetchAll();
        
        if (empty($bookings)) {
            $error = '예약 정보를 찾을 수 없습니다. 예약번호 또는 전화번호를 확인해주세요.';
        } elseif (count($bookings) === 1) {
            // 예약이 하나면 그대로 표시
            $booking = $bookings[0];
        }
        // 예약이 여러 개면 목록으로 표시 (아래에서 처리)
    }
}

$pageTitle = '예약 조회 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50 pt-[228px] pb-8';
ob_start();
?>
<div class="mx-auto max-w-2xl px-4 sm:px-6">
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-900">예약 조회</h1>
        <p class="mt-2 text-gray-600">예약번호 또는 전화번호로 예약 내역을 확인하세요</p>
    </div>
    
    <?php if (!$booking && empty($bookings)): ?>
    <!-- 조회 폼 -->
    <div class="rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
        <?php if ($error): ?>
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
            <p class="text-sm text-red-800"><?= htmlspecialchars($error) ?></p>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6" onsubmit="return validateForm(event)">
            <div>
                <label for="request_id" class="block text-sm font-medium text-gray-700 mb-2">
                    예약번호
                </label>
                <input type="text" 
                       id="request_id" 
                       name="request_id" 
                       class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:ring-2 focus:ring-primary/20"
                       placeholder="예약번호를 입력하세요"
                       value="<?= htmlspecialchars($_POST['request_id'] ?? '') ?>">
                <p class="mt-1 text-xs text-gray-500">예약 완료 시 받은 예약번호를 입력하세요</p>
            </div>
            
            <div>
                <label for="guest_phone" class="block text-sm font-medium text-gray-700 mb-2">
                    전화번호
                </label>
                <input type="tel" 
                       id="guest_phone" 
                       name="guest_phone" 
                       class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:ring-2 focus:ring-primary/20"
                       placeholder="01012345678"
                       pattern="[0-9-]+"
                       value="<?= htmlspecialchars($_POST['guest_phone'] ?? '') ?>">
                <p class="mt-1 text-xs text-gray-500">하이픈(-) 없이 숫자만 입력하세요</p>
            </div>
            
            <div class="rounded-lg bg-blue-50 border border-blue-200 p-3">
                <p class="text-xs text-blue-800">
                    <strong>안내:</strong> 예약번호 또는 전화번호 중 하나만 입력해도 조회 가능합니다.
                </p>
            </div>
            
            <button type="submit" 
                    class="w-full min-h-[44px] rounded-lg bg-primary px-6 py-3 font-medium text-white hover:opacity-90 transition-opacity">
                예약 조회
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                회원이신가요? 
                <a href="<?= $base ?>/auth/login" class="font-medium text-primary hover:underline">로그인</a>
            </p>
        </div>
    </div>
    
    <script>
    function validateForm(e) {
        var requestId = document.getElementById('request_id').value.trim();
        var guestPhone = document.getElementById('guest_phone').value.trim();
        
        if (requestId === '' && guestPhone === '') {
            e.preventDefault();
            alert('예약번호 또는 전화번호 중 하나를 입력해주세요.');
            return false;
        }
        
        return true;
    }
    </script>
    
    <?php elseif (count($bookings) > 1): ?>
    <!-- 예약 목록 표시 (여러 개 조회된 경우) -->
    <div class="space-y-4">
        <div class="rounded-lg bg-blue-50 border border-blue-200 p-4">
            <p class="text-sm text-blue-800">
                <strong><?= count($bookings) ?>건</strong>의 예약이 조회되었습니다. 확인할 예약을 선택해주세요.
            </p>
        </div>
        
        <?php foreach ($bookings as $item): ?>
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($item['service_type']) ?></h3>
                    <p class="mt-1 text-sm text-gray-600">예약번호: <?= htmlspecialchars($item['id']) ?></p>
                    <p class="mt-1 text-sm text-gray-600">
                        <?= htmlspecialchars($item['service_date']) ?> 
                        <?= htmlspecialchars(substr($item['start_time'], 0, 5)) ?>
                        · 예상 <?= (int)($item['duration_minutes'] / 60) ?>시간
                    </p>
                    <p class="mt-1 text-sm text-gray-600">
                        예약자: <?= htmlspecialchars($item['customer_name'] ?: $item['guest_name'] ?: '비회원') ?>
                    </p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-medium shrink-0 <?php
                    echo match($item['status']) {
                        'CONFIRMED' => 'bg-yellow-100 text-yellow-800',
                        'MATCHING' => 'bg-blue-100 text-blue-800',
                        'COMPLETED' => 'bg-green-100 text-green-800',
                        'CANCELLED' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100 text-gray-800'
                    };
                ?>">
                    <?php
                    echo match($item['status']) {
                        'CONFIRMED' => '예약 확정',
                        'MATCHING' => '매니저 매칭 완료',
                        'COMPLETED' => '서비스 완료',
                        'CANCELLED' => '취소됨',
                        default => $item['status']
                    };
                    ?>
                </span>
            </div>
            
            <form method="POST" class="mt-4">
                <input type="hidden" name="request_id" value="<?= htmlspecialchars($item['id']) ?>">
                <input type="hidden" name="guest_phone" value="<?= htmlspecialchars($_POST['guest_phone'] ?? '') ?>">
                <button type="submit" 
                        class="w-full min-h-[44px] rounded-lg bg-primary px-6 py-3 font-medium text-white hover:opacity-90 transition-opacity">
                    상세 보기
                </button>
            </form>
        </div>
        <?php endforeach; ?>
        
        <div class="text-center pt-4">
            <a href="<?= $base ?>/bookings/guest-check" 
               class="text-sm text-gray-600 hover:text-primary">
                다른 예약 조회하기
            </a>
        </div>
    </div>
    
    <?php else: ?>
    <!-- 예약 정보 표시 (단일 예약) -->
    <div class="space-y-6">
        <!-- 예약 상태 -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($booking['service_type']) ?></h2>
                    <p class="mt-1 text-sm text-gray-600">예약번호: <?= htmlspecialchars($booking['id']) ?></p>
                </div>
                <span class="rounded-full px-3 py-1 text-sm font-medium <?php
                    echo match($booking['status']) {
                        'CONFIRMED' => 'bg-yellow-100 text-yellow-800',
                        'MATCHING' => 'bg-blue-100 text-blue-800',
                        'COMPLETED' => 'bg-green-100 text-green-800',
                        'CANCELLED' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100 text-gray-800'
                    };
                ?>">
                    <?php
                    echo match($booking['status']) {
                        'CONFIRMED' => '예약 확정',
                        'MATCHING' => '매니저 매칭 완료',
                        'COMPLETED' => '서비스 완료',
                        'CANCELLED' => '취소됨',
                        default => $booking['status']
                    };
                    ?>
                </span>
            </div>
            
            <div class="space-y-3 border-t border-gray-100 pt-4">
                <div class="flex justify-between">
                    <span class="text-gray-600">예약자</span>
                    <span class="font-medium"><?= htmlspecialchars($booking['customer_name'] ?: $booking['guest_name'] ?: '비회원') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">전화번호</span>
                    <span class="font-medium"><?= htmlspecialchars($booking['customer_phone'] ?: $booking['guest_phone'] ?: $booking['phone'] ?: '') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">서비스 일시</span>
                    <span class="font-medium">
                        <?= htmlspecialchars($booking['service_date']) ?> 
                        <?= htmlspecialchars(substr($booking['start_time'], 0, 5)) ?>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">예상 소요시간</span>
                    <span class="font-medium"><?= (int)($booking['duration_minutes'] / 60) ?>시간</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">방문 주소</span>
                    <span class="font-medium text-right">
                        <?= htmlspecialchars($booking['address']) ?>
                        <?php if ($booking['address_detail']): ?>
                        <br><span class="text-sm text-gray-500"><?= htmlspecialchars($booking['address_detail']) ?></span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- 결제 정보 -->
        <?php if ($booking['payment_id']): ?>
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-4">결제 정보</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">결제 금액</span>
                    <span class="font-bold text-lg"><?= number_format($booking['amount']) ?>원</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">결제 수단</span>
                    <span class="font-medium"><?= htmlspecialchars($booking['payment_method']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">결제 일시</span>
                    <span class="font-medium"><?= htmlspecialchars($booking['paid_at'] ? date('Y-m-d H:i', strtotime($booking['paid_at'])) : '-') ?></span>
                </div>
                <?php if ($booking['payment_status'] === 'REFUNDED'): ?>
                <div class="flex justify-between border-t border-gray-100 pt-3">
                    <span class="text-gray-600">환불 금액</span>
                    <span class="font-medium text-green-600"><?= number_format($booking['refund_amount']) ?>원</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">환불 일시</span>
                    <span class="font-medium"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($booking['refunded_at']))) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 매니저 정보 -->
        <?php if ($booking['manager_name']): ?>
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-4">담당 매니저</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">매니저</span>
                    <span class="font-medium"><?= htmlspecialchars($booking['manager_name']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">연락처</span>
                    <span class="font-medium"><?= htmlspecialchars($booking['manager_phone']) ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 안내 메시지 -->
        <?php if ($booking['status'] === 'CANCELLED'): ?>
        <div class="rounded-lg bg-red-50 border border-red-200 p-4">
            <p class="text-sm text-red-800">
                이 예약은 취소되었습니다. 
                <?php if ($booking['payment_status'] === 'REFUNDED'): ?>
                환불이 완료되었습니다.
                <?php else: ?>
                환불 처리 중입니다. 1-3 영업일 내 환불됩니다.
                <?php endif; ?>
            </p>
        </div>
        <?php elseif ($booking['status'] === 'CONFIRMED'): ?>
        <div class="rounded-lg bg-blue-50 border border-blue-200 p-4">
            <p class="text-sm text-blue-800">
                예약이 확정되었습니다. 매니저 배정을 기다리고 있습니다.
            </p>
        </div>
        <?php elseif ($booking['status'] === 'MATCHING'): ?>
        <div class="rounded-lg bg-green-50 border border-green-200 p-4">
            <p class="text-sm text-green-800">
                매니저가 배정되었습니다. 서비스 일시에 방문 예정입니다.
            </p>
        </div>
        <?php endif; ?>
        
        <!-- 버튼 -->
        <div class="flex gap-3">
            <a href="<?= $base ?>/bookings/guest-check" 
               class="flex-1 min-h-[44px] inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-6 py-3 font-medium text-gray-700 hover:bg-gray-50">
                다른 예약 조회
            </a>
            
            <?php if (in_array($booking['status'], ['CONFIRMED', 'MATCHING']) && $booking['payment_status'] === 'SUCCESS'): ?>
            <button type="button"
                    onclick="cancelBooking()"
                    class="flex-1 min-h-[44px] inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-6 py-3 font-medium text-red-700 hover:bg-red-50">
                예약 취소
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (in_array($booking['status'], ['CONFIRMED', 'MATCHING']) && $booking['payment_status'] === 'SUCCESS'): ?>
    <script>
    function cancelBooking() {
        if (!confirm('예약을 취소하시겠습니까?\n결제 금액은 환불 처리됩니다.')) {
            return;
        }
        
        // 비회원 취소 API 호출
        fetch('<?= $base ?>/api/bookings/cancel-guest', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                request_id: '<?= htmlspecialchars($booking['id']) ?>',
                guest_phone: '<?= htmlspecialchars($booking['guest_phone']) ?>'
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                alert('예약이 취소되었습니다.\n환불은 1-3 영업일 내 처리됩니다.');
                location.reload();
            } else {
                alert('취소 실패: ' + (data.error || '알 수 없는 오류'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('취소 처리 중 오류가 발생했습니다.');
        });
    }
    </script>
    <?php endif; ?>
    
    <?php endif; ?>
</div>
<?php
$layoutContent = ob_get_clean();
require_once dirname(__DIR__, 2) . '/components/layout.php';
