<?php
/**
 * 서비스 요청 생성 - 다단계 폼 (PRD 4.3)
 * URL: /requests/new
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$base = rtrim(BASE_URL, '/');

// 회원/비회원 구분은 1단계에서 처리
// 비회원도 서비스 신청을 진행할 수 있도록 허용
$userType = $_POST['user_type'] ?? ($currentUser ? 'member' : null);
if ($userType === 'member' && !$currentUser) {
    // 회원 선택 시 로그인 페이지로 (JavaScript에서 처리하지만 서버 측에서도 확인)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_type']) && !isset($_POST['service_type'])) {
        redirect('/auth/login');
    }
}

// 로그인한 사용자는 Step 1부터 시작 (회원/비회원 구분)
$initialStep = 1;
// 회원도 Step 1.5를 거치도록 함

$error = '';
$RATE_PER_HOUR = 20000;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceType = trim((string) ($_POST['service_type'] ?? ''));
    $serviceDate = trim((string) ($_POST['service_date'] ?? ''));
    $startTime = trim((string) ($_POST['start_time'] ?? ''));
    $duration = (int) ($_POST['duration_hours'] ?? 0);
    $address = trim((string) ($_POST['address'] ?? ''));
    $addressDetail = trim((string) ($_POST['address_detail'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $details = trim((string) ($_POST['details'] ?? ''));
    $lat = isset($_POST['lat']) ? (float) $_POST['lat'] : 0.0;
    $lng = isset($_POST['lng']) ? (float) $_POST['lng'] : 0.0;

    $allowedTypes = ['병원 동행', '가사돌봄', '생활동행', '노인 돌봄', '아이 돌봄', '기타'];
    if (!in_array($serviceType, $allowedTypes, true)) {
        $error = '서비스를 선택해주세요.';
    } elseif (!$serviceDate || !$startTime || $duration < 1 || $duration > 12) {
        $error = '일시와 예상 시간을 확인해주세요.';
    } elseif ($address === '') {
        $error = '주소를 입력해주세요.';
    } elseif ($phone === '') {
        $error = '전화번호를 입력해주세요.';
    } elseif (!preg_match('/^[0-9-]+$/', $phone)) {
        $error = '올바른 전화번호 형식이 아닙니다.';
    } else {
        // 결제 단계에서는 로그인 필요
        if (!$currentUser || $currentUser['role'] !== ROLE_CUSTOMER) {
            redirect('/auth/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        }
        
        $durationMin = $duration * 60;
        $estimatedPrice = $duration * $RATE_PER_HOUR;
        $id = uuid4();
        $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
        $st = $pdo->prepare('INSERT INTO service_requests (id, customer_id, service_type, service_date, start_time, duration_minutes, address, address_detail, phone, lat, lng, details, status, estimated_price) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([$id, $currentUser['id'], $serviceType, $serviceDate, $startTime, $durationMin, $address, $addressDetail === '' ? null : $addressDetail, $phone === '' ? null : $phone, $lat, $lng, $details === '' ? null : $details, 'PENDING', $estimatedPrice]);
        init_session();
        $_SESSION['request_created'] = $id;
        redirect('/requests/detail?id=' . urlencode($id));
    }
}

$pageTitle = '서비스 요청 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50 py-8 pt-32';
ob_start();

$timeOptions = [];
for ($h = 0; $h < 24; $h++) {
    foreach ([0, 30] as $m) {
        $t = sprintf('%02d:%02d', $h, $m);
        $timeOptions[] = $t;
    }
}
$minDate = date('Y-m-d');
?>
<div class="mx-auto max-w-2xl px-4 sm:px-6">
    <h1 class="text-2xl font-bold">서비스 요청</h1>
    <p class="mt-1 text-gray-600">원하는 서비스와 일시를 선택해주세요.</p>

    <!-- 진행 바 (5단계) -->
    <div class="mt-6 flex gap-1" role="progressbar" aria-valuenow="<?= $initialStep ?>" aria-valuemin="1" aria-valuemax="5" aria-label="진행 단계">
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <div class="h-1.5 flex-1 rounded-full bg-gray-200 step-dot" data-step="<?= $i ?>"></div>
        <?php endfor; ?>
    </div>
    <p class="mt-2 text-sm font-medium text-gray-500"><span id="step-label"><?= $initialStep ?></span> / 5</p>

    <?php if ($error): ?>
    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="request-form" class="mt-6" action="<?= $base ?>/requests/new" method="post" novalidate>
        <!-- Step 1: 회원/비회원 구분 -->
        <div class="request-step <?= $initialStep === 1 ? '' : 'hidden' ?> rounded-lg border bg-white p-6" data-step="1" id="step-1">
            <h2 class="text-lg font-semibold">회원이신가요?</h2>
            <p class="mt-2 text-sm text-gray-600">서비스 신청을 위해 회원 여부를 선택해주세요.</p>
            <div class="mt-6 space-y-3">
                <label class="flex min-h-[44px] cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 has-[:checked]:border-primary has-[:checked]:ring-2 has-[:checked]:ring-primary">
                    <input type="radio" name="user_type" value="member" class="mt-1" required>
                    <div>
                        <span class="font-medium text-lg">회원</span>
                        <p class="mt-1 text-sm text-gray-600">이미 가입하신 회원이시면 로그인 후 서비스를 신청하실 수 있습니다.</p>
                    </div>
                </label>
                <label class="flex min-h-[44px] cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 has-[:checked]:border-primary has-[:checked]:ring-2 has-[:checked]:ring-primary">
                    <input type="radio" name="user_type" value="non-member" class="mt-1" required>
                    <div>
                        <span class="font-medium text-lg">비회원</span>
                        <p class="mt-1 text-sm text-gray-600">회원가입 후 서비스를 신청하실 수 있습니다.</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Step 1.5: 신청자 정보 입력 (회원/비회원 공통) -->
        <div class="request-step hidden rounded-lg border bg-white p-6" data-step="1.5" id="step-guest-info">
            <h2 class="text-lg font-semibold">신청자 정보를 입력해주세요</h2>
            <p class="mt-2 text-sm text-gray-600">서비스 신청을 위해 연락받으실 정보를 입력해주세요.</p>
            <div class="mt-6 space-y-4">
                <div>
                    <label for="guest_name" class="block text-sm font-medium text-gray-700">이름</label>
                    <input type="text" id="guest_name" name="guest_name" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" placeholder="이름을 입력하세요" value="<?= $currentUser && isset($currentUser['name']) ? htmlspecialchars($currentUser['name']) : '' ?>" required>
                </div>
                <div>
                    <label for="guest_phone" class="block text-sm font-medium text-gray-700">전화번호</label>
                    <input type="tel" id="guest_phone" name="guest_phone" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" placeholder="010-1234-5678" pattern="[0-9\-]+" value="<?= $currentUser && isset($currentUser['phone']) ? htmlspecialchars($currentUser['phone']) : '' ?>" required>
                </div>
              
                <div>
    <label for="guest_address" class="block text-sm font-medium text-gray-700">방문주소</label>
    <div class="mt-1 flex gap-2">
        <input type="text" id="guest_address" name="guest_address" class="block flex-1 rounded-lg border border-gray-300 px-4 py-3" placeholder="도로명 또는 지번 주소 입력 후 검색" value="<?= $currentUser && isset($currentUser['address']) ? htmlspecialchars($currentUser['address']) : '' ?>" required autocomplete="off" aria-describedby="guest-address-search-msg">
        <button type="button" id="btn-guest-address-search" class="shrink-0 min-h-[44px] min-w-[44px] rounded-lg bg-primary px-4 py-3 font-medium text-white hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" disabled>주소 검색</button>
    </div>
    <p id="guest-address-search-msg" class="mt-1 text-sm" role="status" aria-live="polite"></p>
    <div id="guest-address-results" class="mt-2 hidden space-y-1" role="list" aria-label="주소 검색 결과"></div>
    <div>
        <label for="guest_address_detail" class="block text-sm font-medium text-gray-700">상세 주소 <span class="text-gray-400">(선택)</span></label>
        <input type="text" id="guest_address_detail" name="guest_address_detail" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" placeholder="동/호수 등" value="<?= $currentUser && isset($currentUser['address_detail']) ? htmlspecialchars($currentUser['address_detail']) : '' ?>">
    </div>
    <!-- 비회원 주소 좌표 (hidden) -->
    <input type="hidden" id="guest_lat" name="guest_lat" value="">
    <input type="hidden" id="guest_lng" name="guest_lng" value="">
</div>

            </div>
        </div>

        <!-- Step 2: 서비스 선택 -->
        <div class="request-step <?= $initialStep === 2 ? '' : 'hidden' ?> rounded-lg border bg-white p-6" data-step="2" id="step-2">
            <h2 class="text-lg font-semibold">어떤 서비스가 필요하신가요?</h2>
            <div class="mt-4 space-y-2">
                <?php
                $services = [
                    '병원 동행' => '진료 예약부터 귀가까지 함께합니다',
                    '가사돌봄' => '가사 활동을 도와드립니다',
                    '생활동행' => '일상 생활 동행을 도와드립니다',
                    '노인 돌봄' => '어르신의 일상을 도와드립니다',
                    '아이 돌봄' => '안전하게 아이를 돌봐드립니다',
                    '기타' => '기타 동행 및 돌봄 서비스',
                ];
                foreach ($services as $val => $desc):
                    $checked = isset($_POST['service_type']) && $_POST['service_type'] === $val ? 'checked' : '';
                ?>
                <label class="flex min-h-[44px] cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 has-[:checked]:border-primary has-[:checked]:ring-2 has-[:checked]:ring-primary">
                    <input type="radio" name="service_type" value="<?= htmlspecialchars($val) ?>" class="mt-1" <?= $checked ?> required>
                    <div>
                        <span class="font-medium"><?= htmlspecialchars($val) ?></span>
                        <p class="mt-0.5 text-sm text-gray-600"><?= htmlspecialchars($desc) ?></p>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Step 3: 일시 -->
        <div class="request-step hidden rounded-lg border bg-white p-6" data-step="3" id="step-3">
            <h2 class="text-lg font-semibold">언제 서비스가 필요하신가요?</h2>
            <div class="mt-4 space-y-4">
                <div>
                    <label for="service_date" class="block text-sm font-medium text-gray-700">날짜</label>
                    <input type="date" id="service_date" name="service_date" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" min="<?= $minDate ?>" value="<?= htmlspecialchars($_POST['service_date'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="start_time" class="block text-sm font-medium text-gray-700">시작 시간</label>
                    <select id="start_time" name="start_time" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" required>
                        <option value="">선택</option>
                        <?php foreach ($timeOptions as $t): ?>
                        <option value="<?= $t ?>" <?= (isset($_POST['start_time']) && $_POST['start_time'] === $t) ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <span class="block text-sm font-medium text-gray-700">예상 소요 시간</span>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <?php for ($h = 1; $h <= 9; $h++): $sel = isset($_POST['duration_hours']) && (int)$_POST['duration_hours'] === $h ? 'checked' : ''; ?>
                        <label class="flex min-h-[44px] cursor-pointer items-center rounded-lg border border-gray-200 px-4 has-[:checked]:border-primary has-[:checked]:ring-2 has-[:checked]:ring-primary">
                            <input type="radio" name="duration_hours" value="<?= $h ?>" class="sr-only" <?= $sel ?>><?= $h ?>시간
                        </label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-sm font-medium text-gray-700">예상 금액</p>
                    <p class="mt-1 text-xl font-bold text-primary"><span id="estimated-price">0</span>원</p>
                    <p class="mt-1 text-xs text-gray-500">기본 요금 <?= number_format($RATE_PER_HOUR) ?>원/시간 × <span id="duration-display">0</span>시간 · 최종 금액은 실제 소요 시간에 따라 달라질 수 있습니다.</p>
                </div>
            </div>
        </div>

        <!-- Step 4: 상세 요청사항 -->
        <div class="request-step hidden rounded-lg border bg-white p-6" data-step="4" id="step-4">
            <h2 class="text-lg font-semibold">추가로 알려주실 사항이 있나요?</h2>
            <div class="mt-4">
                <label for="details" class="block text-sm font-medium text-gray-700">상세 요청사항 <span class="text-gray-400">(선택)</span></label>
                <textarea id="details" name="details" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" rows="5" maxlength="1000" placeholder="예: 병원 진료과, 휠체어 필요, 주차 가능 여부 등"><?= htmlspecialchars($_POST['details'] ?? '') ?></textarea>
                <p class="mt-1 text-right text-sm text-gray-500"><span id="details-count">0</span> / 1000</p>
                <p class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600">매니저에게 도움이 되는 정보를 자세히 적어주세요. 예: 환자 상태, 준비물, 특별한 요청사항 등</p>
            </div>
        </div>

        <!-- Step 5: 결제 -->
        <div class="request-step hidden rounded-lg border bg-white p-6" data-step="5" id="step-5">
            <h2 class="text-lg font-semibold">결제하기</h2>
            
            <!-- 주문 요약 -->
            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <h3 class="font-semibold text-sm text-gray-700">주문 정보</h3>
                <dl class="mt-2 space-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-600">서비스</dt><dd class="font-medium" id="summary-service">-</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-600">일시</dt><dd class="font-medium" id="summary-datetime">-</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-600">예상 시간</dt><dd class="font-medium" id="summary-duration">-</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-600">위치</dt><dd class="font-medium text-xs" id="summary-address">-</dd></div>
                </dl>
                <div class="mt-3 border-t pt-3 flex justify-between items-center">
                    <dt class="font-semibold text-gray-700">결제 금액</dt>
                    <dd class="text-xl font-bold text-primary" id="summary-price">-</dd>
                </div>
                <p class="mt-2 text-xs text-gray-500">※ 최종 금액은 실제 소요 시간에 따라 달라질 수 있습니다.</p>
            </div>
            
            <!-- 토스페이먼츠 결제 위젯 -->
            <div class="mt-6">
                <div id="payment-widget"></div>
                <div id="agreement"></div>
            </div>
            
            <label class="mt-4 flex min-h-[44px] cursor-pointer items-start gap-2">
                <input type="checkbox" id="confirm_terms" name="confirm_terms" class="mt-1" required>
                <span class="text-sm text-gray-700">위 내용을 확인했으며 서비스 이용약관에 동의합니다.</span>
            </label>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
            <button type="button" id="btn-prev" class="hidden order-2 min-h-[44px] rounded-lg border border-gray-300 bg-white px-6 font-medium text-gray-700 hover:bg-gray-50 sm:order-1">이전</button>
            <div class="flex flex-1 justify-end gap-3 sm:order-2">
                <button type="button" id="btn-next" class="min-h-[44px] rounded-lg bg-primary px-6 font-medium text-white hover:opacity-90">다음</button>
                <button type="button" id="btn-payment" class="hidden min-h-[44px] rounded-lg bg-primary px-6 font-medium text-white hover:opacity-90">결제하기</button>
            </div>
        </div>
    </form>
</div>

<script>
(function() {
    var form = document.getElementById('request-form');
    var steps = Array.from(form.querySelectorAll('.request-step'));
    var dots = document.querySelectorAll('.step-dot');
    var stepLabel = document.getElementById('step-label');
    var btnPrev = document.getElementById('btn-prev');
    var btnNext = document.getElementById('btn-next');
    var btnPayment = document.getElementById('btn-payment');
    var ratePerHour = <?= (int) $RATE_PER_HOUR ?>;
    var apiBase = <?= json_encode($base) ?>;

    function step() { return parseFloat(form.dataset.step || '1'); }
    function setStep(n) {
        var s = Math.max(1, Math.min(5, n));
        form.dataset.step = String(s);
        steps.forEach(function(el) { el.classList.add('hidden'); });
        var current = form.querySelector('.request-step[data-step="' + s + '"]');
        if (current) current.classList.remove('hidden');
        dots.forEach(function(d) {
            var dStep = parseInt(d.dataset.step, 10);
            d.classList.remove('bg-primary');
            d.classList.add('bg-gray-200');
            if (dStep <= s) {
                d.classList.remove('bg-gray-200');
                d.classList.add('bg-primary');
            }
        });
        // Step 1.5가 표시될 때 주소 검색 버튼 상태 업데이트
        if (s === 1.5 && typeof updateGuestAddrBtn === 'function') {
            setTimeout(updateGuestAddrBtn, 100);
        }
        stepLabel.textContent = s;
        btnPrev.classList.toggle('hidden', s === 1);
        btnNext.classList.toggle('hidden', s === 5);
        btnPayment.classList.toggle('hidden', s !== 5);
        
        // Step 5 (결제 단계)일 때 주문 요약 업데이트
        if (s === 5) {
            updateSummary();
        }
    }

    function validateStep(s) {
        var el = form.querySelector('.request-step[data-step="' + s + '"]');
        if (!el) return true;
        
        // Step 1: 회원/비회원 구분
        if (s === 1) {
            var userTypeRadio = form.querySelector('input[name="user_type"]:checked');
            if (!userTypeRadio) {
                alert('회원 여부를 선택해주세요.');
                var firstRadio = form.querySelector('input[name="user_type"]');
                if (firstRadio) firstRadio.focus();
                return false;
            }
            return true;
        }

        // Step 1.5: 비회원 정보 확인
        if (s === 1.5) {
            var gName = form.querySelector('#guest_name');
            var gPhone = form.querySelector('#guest_phone');
            var gAddr = form.querySelector('#guest_address');
            if (!gName || !gName.value.trim()) { alert('이름을 입력해주세요.'); if(gName) gName.focus(); return false; }
            if (!gPhone || !gPhone.value.trim()) { alert('전화번호를 입력해주세요.'); if(gPhone) gPhone.focus(); return false; }
            if (!gAddr || !gAddr.value.trim()) { alert('주소를 입력해주세요.'); if(gAddr) gAddr.focus(); return false; }
            return true;
        }
        
        // Step 2: 서비스 타입 라디오 버튼 확인
        if (s === 2) {
            var serviceRadio = form.querySelector('input[name="service_type"]:checked');
            if (!serviceRadio) {
                alert('서비스를 선택해주세요.');
                var firstRadio = form.querySelector('input[name="service_type"]');
                if (firstRadio) firstRadio.focus();
                return false;
            }
            return true;
        }
        
        // Step 3: 일시 확인
        if (s === 3) {
            var date = form.querySelector('#service_date');
            var time = form.querySelector('#start_time');
            var duration = form.querySelector('input[name="duration_hours"]:checked');
            
            if (!date || !date.value) {
                alert('서비스 날짜를 선택해주세요.');
                if (date) date.focus();
                return false;
            }
            if (!time || !time.value) {
                alert('서비스 시간을 선택해주세요.');
                if (time) time.focus();
                return false;
            }
            if (!duration) {
                alert('예상 소요 시간을 선택해주세요.');
                var firstDuration = form.querySelector('input[name="duration_hours"]');
                if (firstDuration) firstDuration.focus();
                return false;
            }
            return true;
        }
        
        // Step 4: 선택사항이므로 항상 통과
        if (s === 4) {
            return true;
        }
        
        // Step 5: 약관 동의 확인
        if (s === 5) {
            var terms = form.querySelector('#confirm_terms');
            if (!terms || !terms.checked) {
                alert('서비스 이용약관에 동의해주세요.');
                if (terms) terms.focus();
                return false;
            }
            return true;
        }
        
        // 기본 유효성 검사
        var inputs = el.querySelectorAll('[required]');
        var ok = true;
        inputs.forEach(function(inp) {
            if (inp.type === 'radio') {
                // 라디오 버튼 그룹 확인
                var name = inp.name;
                var checked = form.querySelector('input[name="' + name + '"]:checked');
                if (!checked) {
                    inp.reportValidity();
                    ok = false;
                }
            } else if (!inp.checkValidity()) {
                inp.reportValidity();
                ok = false;
            }
        });
        return ok;
    }

    function updateEstimated() {
        var d = form.querySelector('input[name="duration_hours"]:checked');
        var h = d ? parseInt(d.value, 10) : 0;
        var price = h * ratePerHour;
        var durEl = document.getElementById('duration-display');
        var priceEl = document.getElementById('estimated-price');
        if (durEl) durEl.textContent = h;
        if (priceEl) priceEl.textContent = price.toLocaleString();
    }

    function updateSummary() {
        var svc = form.querySelector('input[name="service_type"]:checked');
        var dt = form.querySelector('#service_date').value;
        var tm = form.querySelector('#start_time').value;
        var dur = form.querySelector('input[name="duration_hours"]:checked');
        
        // 주소 찾기: 비회원 주소 또는 일반 주소
        var addr = form.querySelector('#guest_address') ? form.querySelector('#guest_address').value : '';
        if (!addr) {
            addr = form.querySelector('#address') ? form.querySelector('#address').value : '';
        }
        
        // 상세 주소 찾기
        var addrDetail = form.querySelector('#guest_address_detail') ? form.querySelector('#guest_address_detail').value : '';
        if (!addrDetail) {
            addrDetail = form.querySelector('#address_detail') ? form.querySelector('#address_detail').value : '';
        }
        
        // 전체 주소 조합
        var fullAddr = addr;
        if (addrDetail) {
            fullAddr = addr + ' ' + addrDetail;
        }
        
        var h = dur ? parseInt(dur.value, 10) : 0;
        var price = h * ratePerHour;
        
        document.getElementById('summary-service').textContent = svc ? svc.value : '-';
        document.getElementById('summary-datetime').textContent = (dt && tm) ? dt + ' ' + tm : '-';
        document.getElementById('summary-duration').textContent = h ? h + '시간' : '-';
        document.getElementById('summary-address').textContent = fullAddr || '-';
        document.getElementById('summary-price').textContent = price ? price.toLocaleString() + '원' : '-';
        
        console.log('주문 요약 업데이트:', {
            service: svc ? svc.value : null,
            datetime: (dt && tm) ? dt + ' ' + tm : null,
            duration: h,
            address: fullAddr,
            price: price
        });
    }

    form.querySelectorAll('input[name="duration_hours"]').forEach(function(r) {
        r.addEventListener('change', updateEstimated);
    });
    updateEstimated();

    var detailsEl = form.querySelector('#details');
    if (detailsEl) {
        function dc() {
            document.getElementById('details-count').textContent = detailsEl.value.length;
        }
        detailsEl.addEventListener('input', dc);
        dc();
    }

    var btnAddr = document.getElementById('btn-address-search');
    var msgEl = document.getElementById('address-search-msg');
    var addrInput = document.getElementById('address');
    function updateAddrBtn() {
        if (!btnAddr || !addrInput) return;
        btnAddr.disabled = !addrInput.value.trim();
    }
    if (addrInput) addrInput.addEventListener('input', updateAddrBtn);
    if (addrInput) addrInput.addEventListener('change', updateAddrBtn);
    updateAddrBtn();

    var resultsDiv = document.getElementById('address-results');
    
    function clearResults() {
        if (resultsDiv) {
            resultsDiv.classList.add('hidden');
            resultsDiv.innerHTML = '';
        }
    }
    
    function selectAddress(item) {
        if (addrInput) addrInput.value = item.address;
        var latEl = document.getElementById('lat');
        var lngEl = document.getElementById('lng');
        if (latEl) latEl.value = String(item.y);
        if (lngEl) lngEl.value = String(item.x);
        msgEl.textContent = '주소가 선택되었습니다.';
        msgEl.classList.remove('text-red-600', 'text-gray-600');
        msgEl.classList.add('text-green-600');
        clearResults();
    }
    
    if (btnAddr && msgEl) {
        btnAddr.addEventListener('click', function() {
            var addr = (addrInput && addrInput.value) ? addrInput.value.trim() : '';
            msgEl.textContent = '';
            msgEl.classList.remove('text-red-600', 'text-green-600', 'text-gray-600');
            clearResults();
            
            if (!addr) {
                msgEl.textContent = '주소를 입력한 뒤 검색해주세요.';
                msgEl.classList.add('text-red-600');
                return;
            }
            
            btnAddr.disabled = true;
            btnAddr.textContent = '검색 중…';
            
            var url = apiBase + '/api/address-suggest?keyword=' + encodeURIComponent(addr);
            fetch(url).then(function(r) { return r.json(); }).then(function(res) {
                if (res.success && res.items && res.items.length > 0) {
                    if (res.items.length === 1) {
                        // 결과 1개: 자동 선택
                        selectAddress(res.items[0]);
                    } else {
                        // 결과 여러 개: 목록 표시
                        msgEl.textContent = '아래에서 주소를 선택해주세요 (' + res.items.length + '개)';
                        msgEl.classList.add('text-gray-600');
                        
                        if (resultsDiv) {
                            resultsDiv.classList.remove('hidden');
                            resultsDiv.innerHTML = '';
                            res.items.forEach(function(item) {
                                var btn = document.createElement('button');
                                btn.type = 'button';
                                btn.className = 'flex min-h-[44px] w-full items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-left text-sm hover:bg-primary hover:text-white focus:bg-primary focus:text-white transition-colors';
                                btn.textContent = item.address;
                                btn.addEventListener('click', function() { selectAddress(item); });
                                resultsDiv.appendChild(btn);
                            });
                        }
                    }
                } else {
                    msgEl.textContent = res.message || '일치하는 주소를 찾지 못했습니다.';
                    msgEl.classList.add('text-red-600');
                }
            }).catch(function(err) {
                msgEl.textContent = '주소 검색 중 오류가 발생했습니다.';
                msgEl.classList.add('text-red-600');
                console.error(err);
            }).finally(function() {
                btnAddr.disabled = !(addrInput && addrInput.value.trim());
                btnAddr.textContent = '주소 검색';
            });
        });
    }

    btnPrev.addEventListener('click', function() {
        if (step() === 1.5) {
            setStep(1);
        } else if (step() > 1) {
            setStep(step() - 1);
        }
    });
    btnNext.addEventListener('click', function() {
        if (!validateStep(step())) return;
        
        // Step 1: 회원/비회원 선택 처리
        if (step() === 1) {
            var userTypeRadio = form.querySelector('input[name="user_type"]:checked');
            if (userTypeRadio) {
                var userType = userTypeRadio.value;
                var isLoggedIn = <?= $currentUser && $currentUser['role'] === ROLE_CUSTOMER ? 'true' : 'false' ?>;
                
                if (userType === 'member') {
                    // 회원 선택 시: 로그인되어 있으면 Step 1.5로, 아니면 로그인 페이지로
                    if (isLoggedIn) {
                        setStep(1.5);
                    } else {
                        window.location.href = apiBase + '/auth/login?redirect=' + encodeURIComponent(window.location.pathname);
                    }
                    return;
                } else if (userType === 'non-member') {
                    // 비회원 선택 시 정보 입력 단계로
                    setStep(1.5);
                    return;
                }
            }
        }
        
        // Step 1.5: 비회원 정보 입력 완료 시 Step 2로 이동
        if (step() === 1.5) {
            setStep(2);
            return;
        }
        
        if (step() < 5) {
            var nextStep = step() + 1;
            
            // Step 5 (결제 단계)로 이동하기 전에 로그인 체크
            if (nextStep === 5) {
                // 로그인 여부 확인 및 비회원 예외 처리
                var isLoggedIn = <?= $currentUser && $currentUser['role'] === ROLE_CUSTOMER ? 'true' : 'false' ?>;
                var isGuest = form.querySelector('input[name="user_type"][value="non-member"]:checked');
                
                if (!isLoggedIn && !isGuest) {
                    alert('결제를 진행하려면 로그인이 필요합니다.');
                    window.location.href = apiBase + '/auth/login?redirect=' + encodeURIComponent(window.location.pathname);
                    return;
                }
            }
            
            setStep(nextStep);
            if (step() === 5) {
                updateSummary();
                // Step 5 도달 시 결제위젯 초기화
                setTimeout(function() {
                    console.log('Step 5 도달, 결제위젯 초기화 시작');
                    setTimeout(initPaymentWidget, 300);
                }, 100);
            }
        }
    });

    form.addEventListener('submit', function(e) {
        for (var s = 1; s <= 5; s++) {
            var el = form.querySelector('.request-step[data-step="' + s + '"]');
            if (!el) continue;
            var inputs = el.querySelectorAll('[required]');
            var bad = false;
            inputs.forEach(function(inp) {
                if (!inp.value || (inp.type === 'checkbox' && !inp.checked)) bad = true;
            });
            if (bad) {
                e.preventDefault();
                setStep(s);
                var first = el.querySelector('[required]');
                if (first) first.focus();
                return;
            }
        }
    });

    setStep(<?= $initialStep ?>);
    
    // 토스페이먼츠 결제위젯 전역 변수
    var paymentWidget = null;
    var paymentMethodWidget = null;
    var isPaymentWidgetInitializing = false;
    
    // 결제위젯 초기화 함수 (SDK v1 방식)
    function initPaymentWidget() {
        if (isPaymentWidgetInitializing) {
            console.log('이미 초기화 중입니다.');
            return;
        }
        
        if (!window.PaymentWidget) {
            console.log('PaymentWidget SDK 로딩 대기 중...');
            setTimeout(initPaymentWidget, 200);
            return;
        }
        
        if (paymentWidget) {
            console.log('이미 초기화되었습니다.');
            return;
        }
        
        isPaymentWidgetInitializing = true;
        
        try {
            var clientKey = '<?= TOSS_CLIENT_KEY ?>';
            // 비회원이면 임의의 키 생성 (실제로는 세션/쿠키 기반이 좋으나 간단히 처리)
            var customerKey = '<?= $currentUser ? $currentUser['id'] : "" ?>';
            if (!customerKey) {
                customerKey = 'guest_' + Math.random().toString(36).substring(2, 12);
            }
            
            console.log('결제위젯 초기화 시작...');
            console.log('Client Key:', clientKey ? (clientKey.substring(0, 10) + '...') : '없음');
            console.log('Customer Key:', customerKey);
            
            if (!clientKey || clientKey === 'your_toss_client_key') {
                throw new Error('토스페이먼츠 클라이언트 키가 설정되지 않았습니다. hosting.php 파일을 확인하세요.');
            }
            
            // SDK v1: PaymentWidget 함수 직접 호출
            paymentWidget = PaymentWidget(clientKey, customerKey);
            console.log('PaymentWidget 인스턴스 생성:', paymentWidget);
            
            var duration = 0;
            var radios = form.querySelectorAll('input[name="duration_hours"]:checked');
            if (radios.length) duration = parseInt(radios[0].value, 10) || 0;
            var amount = Number(duration * ratePerHour);
            
            console.log('결제 금액:', amount);
            
            // 결제 위젯 컨테이너 확인
            var widgetContainer = document.querySelector('#payment-widget');
            if (!widgetContainer) {
                throw new Error('결제 위젯 컨테이너(#payment-widget)를 찾을 수 없습니다.');
            }
            
            console.log('결제 금액으로 위젯 렌더링:', amount);
            
            // 결제 UI 렌더링 (샘플 코드와 동일한 방식)
            paymentMethodWidget = paymentWidget.renderPaymentMethods(
                '#payment-widget',
                { value: amount },
                { variantKey: 'DEFAULT' }
            );
            
            console.log('결제 방법 위젯 렌더링 완료:', paymentMethodWidget);
            
            // 약관 UI 렌더링
            var agreementContainer = document.querySelector('#agreement');
            if (agreementContainer) {
                paymentWidget.renderAgreement('#agreement', { variantKey: 'AGREEMENT' });
                console.log('약관 위젯 렌더링 완료');
            } else {
                console.warn('약관 컨테이너(#agreement)를 찾을 수 없습니다.');
            }
            
            // 렌더링 완료 이벤트
            if (paymentMethodWidget && typeof paymentMethodWidget.on === 'function') {
                paymentMethodWidget.on('ready', function() {
                    console.log('결제위젯 초기화 완료 (ready 이벤트)');
                    isPaymentWidgetInitializing = false;
                    // 결제하기 버튼 표시
                    if (btnPayment) {
                        btnPayment.classList.remove('hidden');
                    }
                });
            } else {
                console.log('결제위젯 초기화 완료 (이벤트 없음)');
                isPaymentWidgetInitializing = false;
                // 결제하기 버튼 표시
                setTimeout(function() {
                    if (btnPayment) {
                        btnPayment.classList.remove('hidden');
                    }
                }, 500);
            }
        } catch (err) {
            console.error('결제 위젯 초기화 실패:', err);
            console.error('에러 상세:', {
                message: err.message,
                stack: err.stack,
                name: err.name
            });
            isPaymentWidgetInitializing = false;
            
            var errorMsg = '결제 시스템 초기화에 실패했습니다.\n\n';
            errorMsg += '오류: ' + (err.message || err) + '\n\n';
            errorMsg += '브라우저 콘솔을 확인하시거나 페이지를 새로고침해주세요.';
            alert(errorMsg);
            
            // 에러 메시지를 화면에 표시
            var widgetContainer = document.querySelector('#payment-widget');
            if (widgetContainer) {
                var errorText = (err.message || '알 수 없는 오류').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                widgetContainer.innerHTML = '<div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">' +
                    '<p class="font-semibold">결제 시스템 초기화 실패</p>' +
                    '<p class="mt-1">' + errorText + '</p>' +
                    '<p class="mt-2 text-xs">페이지를 새로고침하거나 관리자에게 문의하세요.</p>' +
                    '</div>';
            }
        }
    }
    
    // 결제하기 버튼 (SDK v1 방식)
    if (btnPayment) {
        btnPayment.addEventListener('click', async function() {
            console.log('결제하기 버튼 클릭');
            
            if (!document.getElementById('confirm_terms').checked) {
                alert('서비스 이용약관에 동의해주세요.');
                return;
            }
            
            if (!paymentWidget) {
                alert('결제 시스템을 초기화하는 중입니다. 잠시 후 다시 시도해주세요.');
                console.error('paymentWidget이 null입니다.');
                initPaymentWidget();
                return;
            }
            
            try {
                // 폼 데이터 수집
                var formData = new FormData(form);
                
                // duration_hours는 라디오 버튼에서 가져오기
                var durationHours = 0;
                var radios = form.querySelectorAll('input[name="duration_hours"]:checked');
                if (radios.length) durationHours = parseInt(radios[0].value, 10) || 0;
                
                // 비회원 정보 수집
                var guestName = formData.get('guest_name') || '';
                var guestPhone = formData.get('guest_phone') || '';
                var guestAddress = formData.get('guest_address') || '';
                var guestAddressDetail = formData.get('guest_address_detail') || '';
                
                // 회원 정보 (PHP에서 전달)
                var userAddress = '<?= $currentUser && isset($currentUser['address']) ? htmlspecialchars($currentUser['address'], ENT_QUOTES) : "" ?>';
                var userAddressDetail = '<?= $currentUser && isset($currentUser['address_detail']) ? htmlspecialchars($currentUser['address_detail'], ENT_QUOTES) : "" ?>';
                var userPhone = '<?= $currentUser && isset($currentUser['phone']) ? htmlspecialchars($currentUser['phone'], ENT_QUOTES) : "" ?>';
                
                // 주소: 회원 > 폼 입력 > 비회원 순으로 우선순위
                var address = formData.get('address') || '';
                if (!address && userAddress) {
                    address = userAddress; // 회원 주소 사용
                }
                if (!address && guestAddress) {
                    address = guestAddress; // 비회원 주소 사용
                }
                
                // 상세 주소: 회원 > 폼 입력 > 비회원 순으로 우선순위
                var addressDetail = formData.get('address_detail') || '';
                if (!addressDetail && userAddressDetail) {
                    addressDetail = userAddressDetail; // 회원 상세 주소 사용
                }
                if (!addressDetail && guestAddressDetail) {
                    addressDetail = guestAddressDetail; // 비회원 상세 주소 사용
                }
                
                // 전화번호: 회원 > 폼 입력 > 비회원 순으로 우선순위
                var phone = formData.get('phone') || '';
                if (!phone && userPhone) {
                    phone = userPhone; // 회원 전화번호 사용
                }
                if (!phone && guestPhone) {
                    phone = guestPhone; // 비회원 전화번호 사용
                }
                
                // 좌표: 회원 > 비회원 순으로 우선순위
                var lat = formData.get('lat') || formData.get('guest_lat') || '';
                var lng = formData.get('lng') || formData.get('guest_lng') || '';
                
                var serviceData = {
                    service_type: formData.get('service_type'),
                    service_date: formData.get('service_date'),
                    start_time: formData.get('start_time'),
                    duration_hours: durationHours,
                    address: address,
                    address_detail: addressDetail,
                    phone: phone,
                    lat: lat,
                    lng: lng,
                    details: formData.get('details') || '',
                    // 비회원 정보
                    guest_name: guestName,
                    guest_phone: guestPhone,
                    guest_address: guestAddress,
                    guest_address_detail: guestAddressDetail
                };
                
                console.log('서비스 요청 데이터:', serviceData);
                
                // 서비스 요청을 먼저 DB에 저장 (AJAX)
                var saveUrl = apiBase + '/api/requests/save-temp';
                console.log('API 호출 URL:', saveUrl);
                
                var saveResponse = await fetch(saveUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include', // 세션 쿠키 포함
                    body: JSON.stringify(serviceData)
                });
                
                console.log('API 응답 상태:', saveResponse.status);
                
                if (!saveResponse.ok) {
                    var errorText = await saveResponse.text();
                    console.error('API 오류 응답 (원본):', errorText);
                    
                    // JSON 파싱 시도
                    try {
                        var errorJson = JSON.parse(errorText);
                        console.error('API 오류 응답 (파싱됨):', errorJson);
                        alert('서비스 요청 저장에 실패했습니다:\n' + (errorJson.error || errorJson.message || '알 수 없는 오류'));
                    } catch (e) {
                        console.error('JSON 파싱 실패:', e);
                        alert('서비스 요청 저장에 실패했습니다. (HTTP ' + saveResponse.status + ')\n\n응답: ' + errorText.substring(0, 200));
                    }
                    return;
                }
                
                var saveResult = await saveResponse.json();
                console.log('API 응답:', saveResult);
                
                if (!saveResult.ok || !saveResult.request_id) {
                    alert('서비스 요청 저장에 실패했습니다:\n' + (saveResult.error || saveResult.message || '알 수 없는 오류'));
                    return;
                }
                
                var orderId = saveResult.request_id; // 서비스 요청 ID를 orderId로 사용
                var orderName = document.getElementById('summary-service').textContent + ' 서비스';
                
                console.log('결제 요청:', { orderId, orderName });
                
                // 결제 수단 선택 확인
                if (paymentMethodWidget && typeof paymentMethodWidget.getSelectedPaymentMethod === 'function') {
                    try {
                        var selectedMethod = paymentMethodWidget.getSelectedPaymentMethod();
                        console.log('선택된 결제 수단:', selectedMethod);
                        
                        if (!selectedMethod || !selectedMethod.method) {
                            alert('결제 수단을 선택해주세요.\n\n위의 결제 방법 중 하나를 선택한 후 다시 시도해주세요.');
                            return;
                        }
                    } catch (e) {
                        console.warn('결제 수단 확인 중 오류:', e);
                        // 계속 진행 (일부 SDK 버전에서는 지원하지 않을 수 있음)
                    }
                }
                
                // SDK v1: requestPayment는 Promise를 반환하지 않음 (리다이렉트 방식)
                var baseUrl = '<?= $base ?>';
                var successUrl = baseUrl + '/payment/success';
                var failUrl = baseUrl + '/payment/fail';
                
                // URL이 상대 경로인 경우 절대 URL로 변환
                if (!successUrl.startsWith('http')) {
                    successUrl = window.location.origin + (successUrl.startsWith('/') ? '' : '/') + successUrl;
                }
                if (!failUrl.startsWith('http')) {
                    failUrl = window.location.origin + (failUrl.startsWith('/') ? '' : '/') + failUrl;
                }
                
                console.log('결제 URL:', { successUrl, failUrl, baseUrl: baseUrl });
                
                // 결제 요청 (SDK v1은 리다이렉트 방식)
                try {
                    // 고객 정보 설정 (비회원인 경우 입력한 이름 사용)
                    var custName = '<?= $currentUser ? htmlspecialchars($currentUser['name']) : "" ?>';
                    var custEmail = '<?= $currentUser ? htmlspecialchars($currentUser['email']) : "" ?>';
                    
                    if (!custName && formData.get('guest_name')) {
                        custName = formData.get('guest_name');
                    }
                    
                    console.log('결제 요청 시작:', { orderId, orderName, successUrl, failUrl });
                    
                    // 전역 에러 핸들러 (Promise rejection 캐치)
                    var paymentErrorHandler = function(event) {
                        console.error('결제 에러 이벤트:', event);
                        var errorMsg = event.detail?.message || event.message || '결제 처리 중 오류가 발생했습니다.';
                        
                        if (errorMsg.includes('카드 결제 정보를 선택') || errorMsg.includes('결제 수단')) {
                            alert('결제 수단을 선택해주세요.\n\n위의 결제 방법 중 하나를 선택한 후 다시 시도해주세요.');
                        } else {
                            alert('결제 처리 중 오류가 발생했습니다:\n\n' + errorMsg);
                        }
                    };
                    
                    // 에러 이벤트 리스너 추가
                    window.addEventListener('unhandledrejection', function(event) {
                        console.error('Unhandled Promise Rejection:', event.reason);
                        if (event.reason && (event.reason.message || String(event.reason)).includes('카드 결제')) {
                            event.preventDefault(); // 기본 에러 처리 방지
                            alert('결제 수단을 선택해주세요.\n\n위의 결제 방법 중 하나를 선택한 후 다시 시도해주세요.');
                        }
                    });
                    
                    // Promise로 감싸서 처리
                    try {
                        var paymentPromise = Promise.resolve(paymentWidget.requestPayment({
                            orderId: orderId,
                            orderName: orderName,
                            successUrl: successUrl,
                            failUrl: failUrl,
                            customerEmail: custEmail,
                            customerName: custName,
                        }));
                        
                        // Promise가 반환되면 처리
                        if (paymentPromise && typeof paymentPromise.then === 'function') {
                            paymentPromise.catch(function(error) {
                                console.error('결제 Promise 에러:', error);
                                var errorMsg = error?.message || String(error);
                                if (errorMsg.includes('카드 결제') || errorMsg.includes('결제 수단')) {
                                    alert('결제 수단을 선택해주세요.\n\n위의 결제 방법 중 하나를 선택한 후 다시 시도해주세요.');
                                } else {
                                    alert('결제 처리 중 오류가 발생했습니다:\n\n' + errorMsg);
                                }
                            });
                        }
                    } catch (error) {
                        console.error('결제 요청 동기 에러:', error);
                        var errorMsg = error?.message || String(error);
                        if (errorMsg.includes('카드 결제') || errorMsg.includes('결제 수단')) {
                            alert('결제 수단을 선택해주세요.\n\n위의 결제 방법 중 하나를 선택한 후 다시 시도해주세요.');
                        } else {
                            alert('결제창을 열 수 없습니다:\n\n' + errorMsg);
                        }
                    }
                } catch (error) {
                    console.error('결제 요청 외부 에러:', error);
                    alert('결제 처리 중 오류가 발생했습니다:\n\n' + (error.message || '알 수 없는 오류'));
                }
            } catch (error) {
                console.error('결제 요청 오류:', error);
                alert('결제 오류: ' + (error.message || '알 수 없는 오류'));
            }
        });
    }
    
    // ===== 비회원 주소 검색 기능 (Step 1.5) =====
    var btnGuestAddr = document.getElementById('btn-guest-address-search');
    var guestMsgEl = document.getElementById('guest-address-search-msg');
    var guestAddrInput = document.getElementById('guest_address');
    var guestResultsDiv = document.getElementById('guest-address-results');
    
    // 디버깅: 요소 존재 확인
    console.log('비회원 주소 검색 요소 확인:', {
        btnGuestAddr: btnGuestAddr,
        guestMsgEl: guestMsgEl,
        guestAddrInput: guestAddrInput,
        guestResultsDiv: guestResultsDiv
    });
    
    function updateGuestAddrBtn() {
        if (!btnGuestAddr || !guestAddrInput) {
            console.log('비회원 요소 못찾음');
            return;
        }
        var hasValue = guestAddrInput.value.trim().length > 0;
        btnGuestAddr.disabled = !hasValue;
        console.log('버튼 상태:', hasValue ? '활성화' : '비활성화');
    }
    
    // 이벤트 리스너 등록
    if (guestAddrInput) {
        guestAddrInput.addEventListener('input', updateGuestAddrBtn);
        guestAddrInput.addEventListener('change', updateGuestAddrBtn);
        console.log('비회원 이벤트 리스너 등록됨');
    }
    
    // 초기 상태 설정
    updateGuestAddrBtn();
    
    // 주소 검색 기능
    function clearGuestResults() {
        if (guestResultsDiv) {
            guestResultsDiv.classList.add('hidden');
            guestResultsDiv.innerHTML = '';
        }
    }
    
    function selectGuestAddress(item) {
        if (guestAddrInput) {
            guestAddrInput.value = item.address;
            console.log('주소 선택됨:', item.address);
        }
        // 좌표 저장 (비회원용 hidden 필드)
        var guestLatEl = document.getElementById('guest_lat');
        var guestLngEl = document.getElementById('guest_lng');
        if (guestLatEl) guestLatEl.value = String(item.y || '');
        if (guestLngEl) guestLngEl.value = String(item.x || '');
        console.log('좌표 저장됨:', { lat: item.y, lng: item.x });
        
        guestMsgEl.textContent = '주소가 선택되었습니다.';
        guestMsgEl.classList.remove('text-red-600', 'text-gray-600');
        guestMsgEl.classList.add('text-green-600');
        clearGuestResults();
    }
    
    // 검색 버튼 클릭 이벤트
    if (btnGuestAddr && guestMsgEl) {
        btnGuestAddr.addEventListener('click', function() {
            console.log('비회원 검색 버튼 클릭됨');
            var addr = (guestAddrInput && guestAddrInput.value) ? guestAddrInput.value.trim() : '';
            
            guestMsgEl.textContent = '';
            guestMsgEl.classList.remove('text-red-600', 'text-green-600', 'text-gray-600');
            clearGuestResults();
            
            if (!addr) {
                guestMsgEl.textContent = '주소를 입력한 뒤 검색해주세요.';
                guestMsgEl.classList.add('text-red-600');
                return;
            }
            
            btnGuestAddr.disabled = true;
            btnGuestAddr.textContent = '검색 중…';
            
            var url = apiBase + '/api/address-suggest?keyword=' + encodeURIComponent(addr) + '&debug=1';
            console.log('API 호출:', url);
            
            fetch(url)
                .then(function(r) {
                    console.log('응답 상태:', r.status, r.statusText);
                    console.log('Content-Type:', r.headers.get('content-type'));
                    
                    // 텍스트로 먼저 읽어서 확인
                    return r.text().then(function(text) {
                        console.log('원본 응답 텍스트:', text.substring(0, 500)); // 처음 500자만
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON 파싱 실패:', e);
                            console.error('전체 응답:', text);
                            throw new Error('JSON 파싱 실패: ' + e.message + '\n응답: ' + text.substring(0, 200));
                        }
                    });
                })
                .then(function(res) {
                    console.log('API 응답 (파싱됨):', res);
                    if (res.success && res.items && res.items.length > 0) {
                        if (res.items.length === 1) {
                            // 결과 1개: 자동 선택
                            selectGuestAddress(res.items[0]);
                        } else {
                            // 결과 여러 개: 목록 표시
                            guestMsgEl.textContent = '아래에서 주소를 선택해주세요 (' + res.items.length + '개)';
                            guestMsgEl.classList.add('text-gray-600');
                            
                            if (guestResultsDiv) {
                                guestResultsDiv.classList.remove('hidden');
                                guestResultsDiv.innerHTML = '';
                                res.items.forEach(function(item) {
                                    var btn = document.createElement('button');
                                    btn.type = 'button';
                                    btn.className = 'flex min-h-[44px] w-full items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-left text-sm hover:bg-primary hover:text-white focus:bg-primary focus:text-white transition-colors';
                                    btn.textContent = item.address;
                                    btn.addEventListener('click', function() { selectGuestAddress(item); });
                                    guestResultsDiv.appendChild(btn);
                                });
                            }
                        }
                    } else {
                        guestMsgEl.textContent = res.message || '일치하는 주소를 찾지 못했습니다.';
                        guestMsgEl.classList.add('text-red-600');
                    }
                })
                .catch(function(err) {
                    console.error('API 호출 오류:', err);
                    guestMsgEl.textContent = '주소 검색 중 오류가 발생했습니다.';
                    guestMsgEl.classList.add('text-red-600');
                })
                .finally(function() {
                    btnGuestAddr.disabled = !(guestAddrInput && guestAddrInput.value.trim());
                    btnGuestAddr.textContent = '주소 검색';
                });
        });
    }


})();
</script>
<?php
$layoutContent = ob_get_clean();

// 토스페이먼츠 SDK v1 (샘플 프로젝트와 동일)
$additionalHead = '<script src="https://js.tosspayments.com/v1/payment-widget"></script>';

require_once dirname(__DIR__, 2) . '/components/layout.php';
