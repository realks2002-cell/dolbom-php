<?php
/**
 * 로그인 (PRD 4.2) — DB 연동
 * URL: /auth/login
 * 전화번호와 비밀번호로 로그인
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

// security.php가 없으면 기본 함수 정의
if (file_exists(dirname(__DIR__, 2) . '/includes/security.php')) {
    require_once dirname(__DIR__, 2) . '/includes/security.php';
} else {
    // 기본 CSRF 함수 (security.php 없을 때)
    if (!function_exists('csrf_field')) {
        function csrf_field() { return ''; }
    }
    if (!function_exists('verify_csrf_token')) {
        function verify_csrf_token($token) { return true; }
    }
}

$base = rtrim(BASE_URL, '/');
$error = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $currentUser) {
    redirect($currentUser['role'] === ROLE_MANAGER ? '/manager/requests' : '/');
}

/**
 * 전화번호 정규화 (하이픈 제거)
 */
function normalize_phone($phone) {
    return preg_replace('/[^0-9]/', '', $phone);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $error = '보안 토큰이 유효하지 않습니다. 페이지를 새로고침하고 다시 시도해주세요.';
    } else {
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($phone === '' || $password === '') {
            $error = '전화번호와 비밀번호를 입력해주세요.';
        } else {
        // 전화번호 정규화 (하이픈 제거)
        $normalizedPhone = normalize_phone($phone);
        
        $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
        
        // 전화번호로 사용자 조회 (하이픈 포함/미포함 모두 검색)
        $st = $pdo->prepare('SELECT id, email, password_hash, name, role FROM users WHERE phone = ? AND is_active = 1');
        $st->execute([$normalizedPhone]);
        $user = $st->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = '전화번호 또는 비밀번호가 올바르지 않습니다.';
        } else {
            init_session();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);

            // redirect 파라미터 확인
            $redirect = $_GET['redirect'] ?? '';
            
            if ($user['role'] === ROLE_MANAGER) {
                redirect($redirect ?: '/manager/requests');
            }
            if ($user['role'] === ROLE_ADMIN) {
                redirect($redirect ?: '/admin');
            }
            redirect($redirect ?: '/');
        }
    }
    }
}

$pageTitle = '로그인 - ' . APP_NAME;
$mainClass = 'min-h-screen flex flex-col items-center justify-start bg-gray-50 px-4 pt-[250px]';
ob_start();
$phoneVal = htmlspecialchars($_POST['phone'] ?? '');
?>
<div class="w-full max-w-md">
    <h1 class="text-3xl font-bold">로그인</h1>
    <p class="mt-2 text-lg text-gray-600">전화번호와 비밀번호를 입력하세요.</p>
    <?php if ($error): ?>
    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-base text-red-700" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form class="mt-6 space-y-4" action="<?= $base ?>/auth/login" method="post" autocomplete="off">
        <?= csrf_field() ?>
        <div>
            <label for="phone" class="block text-base font-medium text-gray-700">전화번호</label>
            <input type="tel" id="phone" name="phone" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3 text-lg" placeholder="01012345678" pattern="[0-9]*" inputmode="numeric" required autocomplete="off" readonly onfocus="this.removeAttribute('readonly')" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            <p class="mt-1 text-sm text-gray-500">숫자만 입력해주세요</p>
        </div>
        <div>
            <label for="password" class="block text-base font-medium text-gray-700">비밀번호</label>
            <div class="relative">
                <input type="password" id="password" name="password" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3 pr-12 text-lg" placeholder="6자리" required autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
                <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 min-h-[44px] min-w-[44px] flex items-center justify-center text-gray-500 hover:text-gray-700" onclick="togglePassword()" aria-label="비밀번호 표시/숨기기">
                    <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <svg id="eye-off-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.906 5.236m0 0L21 21"></path>
                    </svg>
                </button>
            </div>
            <a href="#" class="mt-1 block text-base text-primary hover:underline">비밀번호를 잊으셨나요?</a>
        </div>
        <button type="submit" class="flex min-h-[44px] w-full items-center justify-center rounded-lg bg-primary font-medium text-lg text-white hover:opacity-90">로그인</button>
    </form>
    <p class="mt-6 text-center text-base text-gray-600">
        계정이 없으신가요? <a href="<?= $base ?>/auth/signup" class="font-medium text-primary hover:underline">회원가입</a>
    </p>
</div>
<script>
// 페이지 로드 시 입력 필드 초기화 (브라우저 자동완성 방지)
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('phone');
    const passwordInput = document.getElementById('password');
    
    if (phoneInput) {
        phoneInput.value = '';
    }
    if (passwordInput) {
        passwordInput.value = '';
    }
});

function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');
    const eyeOffIcon = document.getElementById('eye-off-icon');
    
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
</script>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
