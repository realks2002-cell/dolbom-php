<?php
/**
 * 리뷰 작성 (PRD 4.3)
 * URL: /bookings/[id]/review — id는 GET으로 전달
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
$pageTitle = '리뷰 작성 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50 py-8';
$base = rtrim(BASE_URL, '/');
$bookingId = $_GET['id'] ?? null;
ob_start();
?>
<div class="mx-auto max-w-2xl px-4 sm:px-6">
    <h1 class="text-2xl font-bold">리뷰 작성</h1>
    <p class="mt-2 text-gray-600">별점 선택 + 리뷰 내용 (추가 예정)</p>
    <?php if ($bookingId): ?>
    <p class="mt-4 text-sm text-gray-500">예약 ID: <?= htmlspecialchars($bookingId) ?></p>
    <?php endif; ?>
</div>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
