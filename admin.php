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
    <meta name="description" content="관리자 로그인 - Hangbok77">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                <h1 class="text-3xl font-bold text-primary"><?= APP_NAME ?></h1>
                <p class="mt-2 text-gray-600">관리자 로그인</p>
            </div>

            <!-- 로그인 폼 -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
                <?php if ($error): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <div class="space-y-4">
                        <div>
                            <label for="admin_id" class="block text-sm font-medium text-gray-700 mb-1">관리자 ID</label>
                            <input 
                                type="text" 
                                id="admin_id" 
                                name="admin_id" 
                                value="<?= htmlspecialchars($_POST['admin_id'] ?? '') ?>"
                                class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                                placeholder="관리자 ID 입력"
                                required 
                                autocomplete="username"
                                autofocus>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">비밀번호</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                                placeholder="비밀번호 입력"
                                required 
                                autocomplete="current-password">
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
            <div class="mt-6 text-center">
                <a href="<?= $base ?>/" class="text-sm text-gray-600 hover:text-gray-900">일반 사용자 로그인</a>
            </div>
        </div>
    </div>
</body>
</html>
