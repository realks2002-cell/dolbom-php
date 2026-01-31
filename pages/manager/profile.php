<?php
/**
 * 매니저 프로필 작성/수정 (PRD 4.4)
 * URL: /manager/profile
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
$pageTitle = '매니저 프로필 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50 py-8';
$base = rtrim(BASE_URL, '/');
ob_start();
?>
<div class="mx-auto max-w-2xl px-4 sm:px-6">
    <h1 class="text-2xl font-bold">매니저 프로필</h1>
    <p class="mt-2 text-gray-600">자기소개, 제공 서비스, 활동 지역, 희망 요금 등 (추가 예정)</p>
</div>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
