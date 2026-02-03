<?php
/**
 * 매니저 로그인 페이지
 * URL: /manager/login
 * 전화번호와 비밀번호로 로그인
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/jwt.php';

init_session();

$base = rtrim(BASE_URL, '/');
$error = '';

// 이미 로그인한 매니저는 대시보드로 리다이렉트
if (!empty($_SESSION['manager_id'])) {
    redirect('/manager/dashboard');
}

/**
 * 전화번호 정규화 (하이픈 제거)
 */
function normalize_phone($phone) {
    return preg_replace('/[^0-9]/', '', $phone);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($phone === '' || $password === '') {
        $error = '전화번호와 비밀번호를 입력해주세요.';
    } else {
        // 전화번호 정규화 (하이픈 제거)
        $normalizedPhone = normalize_phone($phone);
        
        $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
        
        // 전화번호로 매니저 조회 (하이픈 포함/미포함 모두 검색)
        // DB의 전화번호도 정규화하여 비교
        $st = $pdo->prepare('SELECT id, name, phone, password_hash FROM managers');
        $st->execute();
        $managers = $st->fetchAll();
        
        $manager = null;
        foreach ($managers as $m) {
            $dbPhone = normalize_phone($m['phone']);
            if ($dbPhone === $normalizedPhone) {
                $manager = $m;
                break;
            }
        }

        if (!$manager) {
            $error = '전화번호 또는 비밀번호가 올바르지 않습니다.';
        } elseif (empty($manager['password_hash'])) {
            $error = '비밀번호가 설정되지 않은 계정입니다. 관리자에게 문의하세요.';
        } elseif (!password_verify($password, $manager['password_hash'])) {
            $error = '전화번호 또는 비밀번호가 올바르지 않습니다.';
        } else {
            init_session();
            $_SESSION['manager_id'] = $manager['id'];
            $_SESSION['manager_name'] = $manager['name'];
            $_SESSION['manager_phone'] = $manager['phone'];
            
            // PHP 대시보드로 리다이렉트
            redirect('/manager/dashboard');
        }
    }
}

$pageTitle = '매니저 로그인 - ' . APP_NAME;
$mainClass = 'min-h-screen flex flex-col items-center justify-center bg-gray-50 px-4';
ob_start();
?>
<div class="w-full max-w-md">
        <div class="w-full max-w-md">
            <!-- 로고 -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-primary"><?= APP_NAME ?></h1>
                <p class="mt-2 text-gray-600">매니저 로그인</p>
            </div>

            <!-- 로그인 폼 -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
                <?php if ($error): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="post" action="<?= $base ?>/manager/login" autocomplete="off">
                    <div class="space-y-4">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">전화번호</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                                placeholder="01012345678"
                                pattern="[0-9]*"
                                inputmode="numeric"
                                required 
                                autocomplete="off"
                                readonly
                                onfocus="this.removeAttribute('readonly')"
                                autofocus>
                            <p class="mt-1 text-xs text-gray-500">숫자만 입력해주세요</p>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">비밀번호</label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 pr-12 focus:ring-2 focus:ring-primary focus:border-transparent" 
                                    placeholder="6자리"
                                    required 
                                    autocomplete="off"
                                    readonly
                                    onfocus="this.removeAttribute('readonly')">
                                <button
                                    type="button"
                                    id="togglePassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 min-h-[44px] min-w-[44px] flex items-center justify-center text-gray-500 hover:text-gray-700 focus:outline-none"
                                    aria-label="비밀번호 표시/숨기기"
                                    tabindex="0">
                                    <!-- 눈 아이콘 (숨김 상태) -->
                                    <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <!-- 눈 슬래시 아이콘 (표시 상태) - 숨김 -->
                                    <svg id="eyeSlashIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.906 5.236m0 0L21 21"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button 
                            type="submit" 
                            class="min-h-[44px] w-full bg-primary text-white rounded-lg font-medium hover:opacity-90 transition-opacity">
                            로그인
                        </button>
                    </div>
                </form>
            </div>

            <!-- 하단 링크 -->
            <div class="mt-6 text-center space-y-2">
                <p class="text-sm text-gray-600">
                    계정이 없으신가요? 
                    <a href="<?= $base ?>/manager/signup" class="text-primary hover:underline font-medium">회원가입</a>
                </p>
                <a href="<?= $base ?>/" class="text-sm text-gray-600 hover:text-gray-900">일반 사용자 로그인</a>
            </div>
        </div>
    </div>
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
    
    // 전화번호 입력 필터링 (숫자만 허용)
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            // 숫자가 아닌 문자 제거
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // 붙여넣기 시에도 필터링
        phoneInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            this.value = paste.replace(/[^0-9]/g, '');
        });
    }
    
    // 비밀번호 표시/숨기기 토글
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    const eyeSlashIcon = document.getElementById('eyeSlashIcon');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // 아이콘 전환
            if (type === 'text') {
                eyeIcon.classList.add('hidden');
                eyeSlashIcon.classList.remove('hidden');
            } else {
                eyeIcon.classList.remove('hidden');
                eyeSlashIcon.classList.add('hidden');
            }
        });
        
        // 키보드 접근성 (Enter 키로도 토글 가능)
        togglePassword.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                togglePassword.click();
            }
        });
    }
</script>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
