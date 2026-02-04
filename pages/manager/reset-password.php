<?php
/**
 * 매니저 비밀번호 재설정 페이지 (임시)
 * URL: /manager/reset-password?phone=01034061921
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/security.php';

$base = rtrim(BASE_URL, '/');
$error = '';
$success = '';

// normalize_phone() 함수는 includes/security.php에 정의되어 있음

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    
    if ($phone === '' || $newPassword === '' || $confirmPassword === '') {
        $error = '모든 필드를 입력해주세요.';
    } elseif (strlen($newPassword) < 8) {
        $error = '비밀번호는 8자 이상 입력해주세요.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = '비밀번호가 일치하지 않습니다.';
    } else {
        $normalizedPhone = normalize_phone($phone);
        $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
        
        // 매니저 찾기
        $st = $pdo->prepare('SELECT id, name, phone FROM managers');
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
            $error = '해당 전화번호로 등록된 매니저를 찾을 수 없습니다.';
        } else {
            // 비밀번호 해시 생성 및 업데이트
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateSt = $pdo->prepare('UPDATE managers SET password_hash = ? WHERE id = ?');
            $updateSt->execute([$hash, $manager['id']]);
            
            $success = '비밀번호가 성공적으로 변경되었습니다. 로그인 페이지로 이동하세요.';
        }
    }
}

$phoneParam = $_GET['phone'] ?? '';
$pageTitle = '매니저 비밀번호 재설정 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50 px-4 py-12';
ob_start();
?>
<div class="max-w-2xl mx-auto mt-48 text-base">
    <div class="w-full max-w-md mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-primary"><?= APP_NAME ?></h1>
                <p class="mt-2 text-gray-600">매니저 비밀번호 재설정</p>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
                <?php if ($error): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
                    <?= htmlspecialchars($success) ?>
                    <div class="mt-4">
                        <a href="<?= $base ?>/manager/login" class="text-primary hover:underline font-medium">로그인 페이지로 이동</a>
                    </div>
                </div>
                <?php else: ?>
                
                <form method="post" action="<?= $base ?>/manager/reset-password">
                    <div class="space-y-4">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">전화번호</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                value="<?= htmlspecialchars($phoneParam ?: ($_POST['phone'] ?? '')) ?>"
                                class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                                placeholder="010-1234-5678 또는 01012345678"
                                required 
                                autocomplete="tel"
                                autofocus>
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">새 비밀번호</label>
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                                placeholder="6자리"
                                required 
                                minlength="6"
                                autocomplete="new-password">
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">비밀번호 확인</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                                placeholder="6자리"
                                required 
                                minlength="6"
                                autocomplete="new-password">
                        </div>

                        <button 
                            type="submit" 
                            class="min-h-[44px] w-full bg-primary text-white rounded-lg font-medium hover:opacity-90 transition-opacity">
                            비밀번호 재설정
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>

    <div class="mt-6 text-center space-y-2">
        <a href="<?= $base ?>/manager/login" class="text-sm text-gray-600 hover:text-gray-900">로그인 페이지로 돌아가기</a>
    </div>
</div>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
