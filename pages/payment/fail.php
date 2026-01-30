<?php
/**
 * 결제 실패 페이지
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$base = rtrim(BASE_URL, '/');

$code = $_GET['code'] ?? '';
$message = $_GET['message'] ?? '결제에 실패했습니다.';

$pageTitle = '결제 실패 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50 py-8';
ob_start();
?>
<div class="mx-auto max-w-lg px-4">
    <div class="rounded-lg border border-red-200 bg-white p-6 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
            <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        <h1 class="mt-4 text-2xl font-bold text-gray-900">결제 실패</h1>
        <p class="mt-2 text-red-600"><?= htmlspecialchars($message) ?></p>
        <?php if ($code): ?>
        <p class="mt-1 text-sm text-gray-500">오류 코드: <?= htmlspecialchars($code) ?></p>
        <?php endif; ?>
        
        <div class="mt-6 flex flex-col gap-2">
            <a href="<?= $base ?>/requests/new" class="rounded-lg bg-primary px-6 py-3 font-medium text-white hover:opacity-90">다시 시도</a>
            <a href="<?= $base ?>/" class="rounded-lg border border-gray-300 px-6 py-3 font-medium text-gray-700 hover:bg-gray-50">홈으로</a>
        </div>
    </div>
</div>
<?php
$layoutContent = ob_get_clean();
require_once dirname(__DIR__, 2) . '/components/layout.php';
