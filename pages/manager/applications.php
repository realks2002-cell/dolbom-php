<?php
/**
 * 내 지원 현황 - 매니저 (PRD 4.4)
 * URL: /manager/applications
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
$pageTitle = '내 지원 현황 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50 py-8';
$base = rtrim(BASE_URL, '/');
ob_start();
?>
<div class="mx-auto max-w-4xl px-4 sm:px-6">
    <h1 class="text-2xl font-bold">내 지원 현황</h1>
    <p class="mt-2 text-gray-600">대기 중 / 확정됨 / 거절됨 탭 (추가 예정)</p>
</div>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
