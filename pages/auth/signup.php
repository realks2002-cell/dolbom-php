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
    $terms = !empty($_POST['terms']);

    if (!$terms) {
        $error = '서비스 이용약관 및 개인정보 처리방침에 동의해주세요.';
    } elseif ($role === '') {
        $error = '역할을 선택해주세요.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '올바른 이메일을 입력해주세요.';
    } elseif (strlen($password) < 8) {
        $error = '비밀번호는 8자 이상 입력해주세요.';
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
            $st = $pdo->prepare('INSERT INTO users (id, email, password_hash, name, phone, role) VALUES (?, ?, ?, ?, ?, ?)');
            $st->execute([$id, $email, $hash, $name, $phone === '' ? null : $phone, $role]);

            init_session();
            $_SESSION['user_id'] = $id;
            $_SESSION['user_role'] = $role;

            if ($role === 'CUSTOMER') {
                redirect('/payment/register-card');
            }
            redirect('/manager/profile');
        }
    }
}

$pageTitle = '회원가입 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50 px-4 py-12';
ob_start();
$emailVal = htmlspecialchars($_POST['email'] ?? '');
$nameVal = htmlspecialchars($_POST['name'] ?? '');
$phoneVal = htmlspecialchars($_POST['phone'] ?? '');
?>
<div class="mx-auto max-w-md mt-48 text-base">
    <h1 class="text-3xl font-bold">회원가입</h1>
    <p class="mt-2 text-lg text-gray-600">역할 선택 후 정보를 입력하세요.</p>
    <?php if ($error): ?>
    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-base text-red-700" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form class="mt-6 space-y-4" action="<?= $base ?>/auth/signup" method="post">
        <div>
            <span class="block text-base font-medium text-gray-700">역할 선택</span>
            <div class="mt-2 flex gap-4 text-base">
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
            <input type="password" id="password" name="password" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" placeholder="8자 이상" required autocomplete="new-password">
        </div>
        <div>
            <label for="password_confirm" class="block text-base font-medium text-gray-700">비밀번호 확인</label>
            <input type="password" id="password_confirm" name="password_confirm" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" required autocomplete="new-password">
        </div>
        <div>
            <label for="name" class="block text-base font-medium text-gray-700">이름</label>
            <input type="text" id="name" name="name" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" placeholder="실명" value="<?= $nameVal ?>" required autocomplete="name">
        </div>
        <div>
            <label for="phone" class="block text-base font-medium text-gray-700">전화번호</label>
            <input type="tel" id="phone" name="phone" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3" placeholder="010-1234-5678" value="<?= $phoneVal ?>">
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
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
