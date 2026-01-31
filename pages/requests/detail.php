<?php
/**
 * 요청 상세 및 지원자 확인 (PRD 4.3)
 * URL: /requests/[id] — id는 GET으로 전달
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$pageTitle = '요청 상세 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50 py-8';
$base = rtrim(BASE_URL, '/');
$requestId = $_GET['id'] ?? null;
$justCreated = false;
if ($requestId && !empty($_SESSION['request_created']) && $_SESSION['request_created'] === $requestId) {
    $justCreated = true;
    unset($_SESSION['request_created']);
}
ob_start();
?>
<div class="mx-auto max-w-3xl px-4 sm:px-6">
    <h1 class="text-2xl font-bold">요청 상세</h1>
    <?php if ($justCreated): ?>
    <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800" role="alert">요청이 등록되었습니다. 매니저가 지원하면 알려드릴게요.</div>
    <?php endif; ?>
    <p class="mt-4 text-gray-600">상태별 화면 (PENDING / MATCHING / CONFIRMED) — 추가 예정</p>
    <?php if ($requestId): ?>
    <p class="mt-2 text-sm text-gray-500">요청 ID: <?= htmlspecialchars($requestId) ?></p>
    <?php endif; ?>
</div>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
