<?php
/**
 * 회원가입 (PRD 4.2) — DB 연동
 * URL: /auth/signup
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$base = rtrim(BASE_URL, '/');
$error = '';
$role = isset($_POST['role']) && in_array($_POST['role'], ['CUSTOMER', 'MANAGER'], true) ? $_POST['role'] : 'CUSTOMER';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $currentUser) {
    redirect($currentUser['role'] === ROLE_MANAGER ? '/manager/requests' : '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = isset($_POST['role']) && in_array($_POST['role'], ['CUSTOMER', 'MANAGER'], true) ? $_POST['role'] : 'CUSTOMER';
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $addressDetail = trim((string) ($_POST['address_detail'] ?? ''));
    $terms = !empty($_POST['terms']);

    if (!$terms) {
        $error = '서비스 이용약관 및 개인정보 처리방침에 동의해주세요.';
    } elseif ($role === '') {
        $error = '역할을 선택해주세요.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '올바른 이메일을 입력해주세요.';
    } elseif (strlen($password) < 6) {
        $error = '비밀번호는 6자 이상 입력해주세요.';
    } elseif ($password !== $passwordConfirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } elseif ($name === '') {
        $error = '이름을 입력해주세요.';
    } else {
        $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
        $st = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            $error = '이미 사용 중인 이메일입니다.';
        } else {
            $id = uuid4();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st = $pdo->prepare('INSERT INTO users (id, email, password_hash, name, phone, address, address_detail, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $st->execute([
                $id,
                $email,
                $hash,
                $name,
                $phone === '' ? null : $phone,
                $address === '' ? null : $address,
                $addressDetail === '' ? null : $addressDetail,
                $role
            ]);

            // 회원가입 성공 - 자동 로그인하지 않음
            // 세션에 사용자 정보 저장하지 않음
            
            // redirect 파라미터 확인
            $redirect = $_GET['redirect'] ?? '';
            $redirectParam = $redirect ? '&redirect=' . urlencode($redirect) : '';
            
            // 페이지 새로고침하여 모달 표시
            header('Location: ' . $base . '/auth/signup?success=1' . $redirectParam);
            exit;
        }
    }
}

$pageTitle = '회원가입 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50 px-4 py-12';
$signupSuccess = isset($_GET['success']) && $_GET['success'] == '1';
ob_start();
$emailVal = htmlspecialchars($_POST['email'] ?? '');
$nameVal = htmlspecialchars($_POST['name'] ?? '');
$phoneVal = htmlspecialchars($_POST['phone'] ?? '');
?>
<div class="mx-auto max-w-md mt-48 text-base">
    <h1 class="text-3xl font-bold">회원가입</h1>
    <p class="mt-2 text-lg text-gray-600">고객 또는 매니저 선택 후 정보를 입력하세요.</p>
    <?php if ($error): ?>
    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-base text-red-700" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form class="mt-6 space-y-4" action="<?= $base ?>/auth/signup" method="post">
        <div>
            <span class="block text-lg font-medium text-gray-700">역할 선택</span>
            <div class="mt-2 flex gap-4 text-lg">
                <label class="flex min-h-[44px] min-w-[44px] cursor-pointer items-center gap-2"><input type="radio" name="role" value="CUSTOMER" <?= $role === 'CUSTOMER' ? 'checked' : '' ?>> 고객</label>
                <label class="flex min-h-[44px] min-w-[44px] cursor-pointer items-center gap-2"><input type="radio" name="role" value="MANAGER" <?= $role === 'MANAGER' ? 'checked' : '' ?>> 매니저</label>
            </div>
        </div>
        <div>
            <label for="email" class="block text-base font-medium text-gray-700">이메일</label>
            <input type="email" id="email" name="email" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" placeholder="example@email.com" value="<?= $emailVal ?>" required autocomplete="email">
        </div>
        <div>
            <label for="password" class="block text-base font-medium text-gray-700">비밀번호</label>
            <div class="relative">
                <input type="password" id="password" name="password" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3 pr-12" placeholder="6자리" required autocomplete="new-password">
                <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 min-h-[44px] min-w-[44px] flex items-center justify-center text-gray-500 hover:text-gray-700" onclick="togglePassword('password')" aria-label="비밀번호 표시/숨기기">
                    <svg id="eye-icon-password" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <svg id="eye-off-icon-password" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.906 5.236m0 0L21 21"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div>
            <label for="password_confirm" class="block text-base font-medium text-gray-700">비밀번호 확인</label>
            <div class="relative">
                <input type="password" id="password_confirm" name="password_confirm" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3 pr-12" placeholder="6자리" required autocomplete="new-password">
                <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 min-h-[44px] min-w-[44px] flex items-center justify-center text-gray-500 hover:text-gray-700" onclick="togglePassword('password_confirm')" aria-label="비밀번호 확인 표시/숨기기">
                    <svg id="eye-icon-password_confirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <svg id="eye-off-icon-password_confirm" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.906 5.236m0 0L21 21"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div>
            <label for="name" class="block text-base font-medium text-gray-700">이름</label>
            <input type="text" id="name" name="name" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" placeholder="실명" value="<?= $nameVal ?>" required autocomplete="name">
        </div>
        <div>
            <label for="phone" class="block text-base font-medium text-gray-700">전화번호</label>
            <input type="tel" id="phone" name="phone" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" placeholder="010-1234-5678" value="<?= $phoneVal ?>">
        </div>
        <div>
            <label for="address" class="block text-base font-medium text-gray-700">주소</label>
            <div class="mt-1 flex gap-2">
                <input type="text" id="address" name="address" class="block flex-1 rounded-lg border border-gray-300 px-4 py-3" placeholder="도로명 또는 지번 주소 입력 후 검색" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" autocomplete="off" aria-describedby="address-search-msg">
                <button type="button" id="btn-address-search" class="shrink-0 min-h-[44px] min-w-[44px] rounded-lg bg-primary px-4 py-3 font-medium text-white hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" disabled>주소 검색</button>
            </div>
            <p id="address-search-msg" class="mt-1 text-sm" role="status" aria-live="polite"></p>
            <div id="address-results" class="mt-2 hidden space-y-1" role="list" aria-label="주소 검색 결과"></div>
            <div>
                <label for="address_detail" class="block text-sm font-medium text-gray-700 mt-2">상세 주소 <span class="text-gray-400">(선택)</span></label>
                <input type="text" id="address_detail" name="address_detail" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" placeholder="동/호수 등" value="<?= htmlspecialchars($_POST['address_detail'] ?? '') ?>">
            </div>
        </div>
        <div class="flex items-start gap-2">
            <input type="checkbox" id="terms" name="terms" class="mt-1" <?= !empty($_POST['terms']) ? 'checked' : '' ?> required>
            <label for="terms" class="text-base text-gray-700">서비스 이용약관 및 개인정보 처리방침에 동의합니다.</label>
        </div>
        <button type="submit" class="flex min-h-[44px] w-full items-center justify-center rounded-lg bg-primary text-lg font-medium text-white hover:opacity-90">회원가입</button>
    </form>
    <p class="mt-6 text-center text-base text-gray-600">
        이미 계정이 있으신가요? <a href="<?= $base ?>/auth/login" class="font-medium text-primary hover:underline">로그인</a>
    </p>
</div>
<?php if ($signupSuccess): ?>
<script>
// 모달 동적 생성 및 표시
(function() {
    function createAndShowModal() {
        // 이미 모달이 있으면 제거
        const existingModal = document.getElementById('successModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // 모달 HTML 생성
        const modalHTML = `
            <div id="successModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50" style="display: flex;">
                <div class="bg-white rounded-lg shadow-xl p-8 max-w-md mx-4">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">회원등록이 완료 되었습니다.</h3>
                        <p class="text-gray-600 mb-6">환영합니다! 로그인하여 서비스를 이용해보세요.</p>
                        <?php 
                        $redirect = $_GET['redirect'] ?? '';
                        $loginUrl = $base . '/auth/login' . ($redirect ? '?redirect=' . urlencode($redirect) : '');
                        ?>
                        <a href="<?= $loginUrl ?>" class="inline-block min-h-[44px] px-6 py-2 bg-primary text-white rounded-lg font-medium hover:opacity-90 transition-opacity">
                            로그인하기
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        // body에 모달 추가
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        const modal = document.getElementById('successModal');
        if (modal) {
            // 모달 외부 클릭 시 닫기
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }
    }
    
    // 페이지 로드 시 모달 표시
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createAndShowModal);
    } else {
        createAndShowModal();
    }
    
    // 추가로 즉시 실행
    setTimeout(createAndShowModal, 100);
})();
</script>
<?php endif; ?>
<script>
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const eyeIcon = document.getElementById('eye-icon-' + fieldId);
    const eyeOffIcon = document.getElementById('eye-off-icon-' + fieldId);
    
    if (passwordInput && eyeIcon && eyeOffIcon) {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.add('hidden');
            eyeOffIcon.classList.remove('hidden');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('hidden');
            eyeOffIcon.classList.add('hidden');
        }
    }
}

// VWorld API 주소 검색 기능
(function() {
    var btnAddr = document.getElementById('btn-address-search');
    var msgEl = document.getElementById('address-search-msg');
    var addrInput = document.getElementById('address');
    var resultsDiv = document.getElementById('address-results');
    var apiBase = '<?= $base ?>';
    
    if (!btnAddr || !msgEl || !addrInput) return;
    
    function updateAddrBtn() {
        if (!btnAddr || !addrInput) return;
        btnAddr.disabled = !addrInput.value.trim();
    }
    
    addrInput.addEventListener('input', updateAddrBtn);
    addrInput.addEventListener('change', updateAddrBtn);
    updateAddrBtn();
    
    function clearResults() {
        if (resultsDiv) {
            resultsDiv.classList.add('hidden');
            resultsDiv.innerHTML = '';
        }
    }
    
    function selectAddress(item) {
        if (addrInput) addrInput.value = item.address;
        msgEl.textContent = '주소가 선택되었습니다.';
        msgEl.classList.remove('text-red-600', 'text-gray-600');
        msgEl.classList.add('text-green-600');
        clearResults();
    }
    
    btnAddr.addEventListener('click', function() {
        var addr = addrInput.value.trim();
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
        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success && res.items && res.items.length > 0) {
                    // 항상 리스트 표시 (1개여도)
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
                } else {
                    msgEl.textContent = res.message || '일치하는 주소를 찾지 못했습니다.';
                    msgEl.classList.add('text-red-600');
                }
            })
            .catch(function(err) {
                msgEl.textContent = '주소 검색 중 오류가 발생했습니다.';
                msgEl.classList.add('text-red-600');
                console.error(err);
            })
            .finally(function() {
                btnAddr.disabled = !addrInput.value.trim();
                btnAddr.textContent = '주소 검색';
            });
    });
})();
</script>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
