<?php
/**
 * 로그인 (PRD 4.2) — DB 연동
 * URL: /auth/login
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$base = rtrim(BASE_URL, '/');
$error = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $currentUser) {
    redirect($currentUser['role'] === ROLE_MANAGER ? '/manager/requests' : '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = '이메일과 비밀번호를 입력해주세요.';
    } else {
        $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
        $st = $pdo->prepare('SELECT id, email, password_hash, name, role FROM users WHERE email = ? AND is_active = 1');
        $st->execute([$email]);
        $user = $st->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = '이메일 또는 비밀번호가 올바르지 않습니다.';
        } else {
            init_session();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);

            if ($user['role'] === ROLE_MANAGER) {
                redirect('/manager/requests');
            }
            if ($user['role'] === ROLE_ADMIN) {
                redirect('/admin');
            }
            redirect('/');
        }
    }
}

$pageTitle = '로그인 - ' . APP_NAME;
$mainClass = 'min-h-screen flex flex-col items-center justify-center bg-gray-50 px-4';
ob_start();
$emailVal = htmlspecialchars($_POST['email'] ?? '');
?>
<div class="w-full max-w-md">
    <h1 class="text-2xl font-bold">로그인</h1>
    <p class="mt-2 text-gray-600">이메일과 비밀번호를 입력하세요.</p>
    <?php if ($error): ?>
    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form class="mt-6 space-y-4" action="<?= $base ?>/auth/login" method="post">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">이메일</label>
            <input type="email" id="email" name="email" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" placeholder="example@email.com" value="<?= $emailVal ?>" required autocomplete="email">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">비밀번호</label>
            <input type="password" id="password" name="password" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" required autocomplete="current-password">
            <a href="#" class="mt-1 block text-sm text-primary hover:underline">비밀번호를 잊으셨나요?</a>
        </div>
        <button type="submit" class="flex min-h-[44px] w-full items-center justify-center rounded-lg bg-primary font-medium text-white hover:opacity-90">로그인</button>
    </form>
    <p class="mt-6 text-center text-sm text-gray-600">
        계정이 없으신가요? <a href="<?= $base ?>/auth/signup" class="font-medium text-primary hover:underline">회원가입</a>
    </p>
</div>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
