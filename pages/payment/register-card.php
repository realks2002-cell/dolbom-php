<?php
/**
 * 카드 등록 (PRD 4.3)
 * URL: /payment/register-card
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
$pageTitle = '카드 등록 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50 py-8';
$base = rtrim(BASE_URL, '/');
ob_start();
?>
<div class="mx-auto max-w-md px-4 sm:px-6 mt-[300px]">
    <h1 class="text-2xl font-bold">카드 등록</h1>
    <p class="mt-2 text-gray-600">토스페이먼츠 연동 (추가 예정)</p>
    <div class="mt-8 rounded-lg border bg-white p-6">
        <p class="text-gray-500">안전한 결제를 위해 카드를 등록해주세요.</p>
        <button type="button" class="mt-4 flex min-h-[44px] w-full items-center justify-center rounded-lg bg-primary font-medium text-white">카드 등록하기</button>
    </div>
</div>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
