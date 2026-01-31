<?php
/**
 * 관리자 전용 로그인 페이지
 * URL: /admin.php
 * admins 테이블 사용
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$base = rtrim(BASE_URL, '/');
$error = '';

// 이미 로그인한 관리자는 대시보드로 리다이렉트
if (!empty($_SESSION['admin_id'])) {
    redirect('/admin');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminId = trim((string) ($_POST['admin_id'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($adminId === '' || $password === '') {
        $error = '관리자 ID와 비밀번호를 입력해주세요.';
    } else {
        $pdo = require __DIR__ . '/database/connect.php';
        $st = $pdo->prepare('SELECT id, admin_id, password_hash FROM admins WHERE admin_id = ?');
        $st->execute([$adminId]);
        $admin = $st->fetch();

        if (!$admin) {
            $error = '관리자 ID가 존재하지 않습니다.';
        } elseif (!$admin['password_hash']) {
            $error = '비밀번호 해시가 설정되지 않았습니다.';
        } elseif (!password_verify($password, $admin['password_hash'])) {
            // 디버깅용 (개발 환경에서만)
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log('비밀번호 검증 실패 - 입력: ' . $password . ', 해시: ' . substr($admin['password_hash'], 0, 20) . '...');
            }
            $error = '관리자 ID 또는 비밀번호가 올바르지 않습니다.';
        } else {
            init_session();
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_db_id'] = $admin['id'];
            redirect('/admin');
        }
    }
}

$pageTitle = '관리자 로그인 - ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="관리자 로그인 - 행복안심동행">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/tailwind.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/custom.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Noto Sans KR', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: { DEFAULT: '#2563eb' },
                    },
                },
            },
        };
    </script>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex flex-col items-center justify-center px-4">
        <div class="w-full max-w-md">
            <!-- 로고 -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-primary"><?= APP_NAME ?></h1>
                <p class="mt-2 text-lg text-gray-600">관리자 로그인</p>
            </div>

            <!-- 로그인 폼 -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
                <?php if ($error): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-base">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" autocomplete="off">
                    <div class="space-y-4">
                        <div>
                            <label for="admin_id" class="block text-base font-medium text-gray-700 mb-1">관리자 ID</label>
                            <input 
                                type="text" 
                                id="admin_id" 
                                name="admin_id" 
                                class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 text-lg focus:ring-2 focus:ring-primary focus:border-transparent" 
                                placeholder="관리자 ID 입력"
                                required 
                                autocomplete="off"
                                readonly
                                onfocus="this.removeAttribute('readonly')"
                                autofocus>
                        </div>

                        <div>
                            <label for="password" class="block text-base font-medium text-gray-700 mb-1">비밀번호</label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 pr-12 text-lg focus:ring-2 focus:ring-primary focus:border-transparent" 
                                    placeholder="비밀번호 입력"
                                    required 
                                    autocomplete="off"
                                    readonly
                                    onfocus="this.removeAttribute('readonly')">
                                <button 
                                    type="button" 
                                    class="absolute right-3 top-1/2 -translate-y-1/2 min-h-[44px] min-w-[44px] flex items-center justify-center text-gray-500 hover:text-gray-700" 
                                    onclick="togglePassword()" 
                                    aria-label="비밀번호 표시/숨기기">
                                    <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <svg id="eye-off-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.906 5.236m0 0L21 21"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button 
                            type="submit" 
                            class="min-h-[44px] w-full bg-primary text-white rounded-lg font-medium text-lg hover:opacity-90 transition-opacity">
                            로그인
                        </button>
                    </div>
                </form>
            </div>

            <!-- 하단 링크 -->
            <div class="mt-6 text-center">
                <a href="<?= $base ?>/" class="text-base text-gray-600 hover:text-gray-900">일반 사용자 로그인</a>
            </div>
        </div>
    </div>
    <script>
    // 페이지 로드 시 입력 필드 초기화 (브라우저 자동완성 방지)
    document.addEventListener('DOMContentLoaded', function() {
        const adminIdInput = document.getElementById('admin_id');
        const passwordInput = document.getElementById('password');
        
        if (adminIdInput) {
            adminIdInput.value = '';
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
</body>
</html>
