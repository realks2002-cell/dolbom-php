<?php
/**
 * 홈 (PRD 4.3)
 * - Hero, 서비스 카테고리, 이용 방법, 리뷰, CTA, FAQ
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
$base = rtrim(BASE_URL, '/');
$ctaHref = $currentUser ? $base . '/requests/new' : $base . '/auth/signup';
$pageTitle = APP_NAME . ' - 믿을 수 있는 병원동행과 돌봄 서비스';
ob_start();
?>
<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24">
    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">믿을 수 있는 병원동행과 돌봄 서비스</h1>
    <p class="mt-4 text-lg text-gray-600">필요한 순간, 신뢰할 수 있는 매니저와 함께</p>
    <div class="mt-8">
        <a href="<?= $ctaHref ?>" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-primary px-6 py-3 text-base font-medium text-white hover:opacity-90">지금 서비스 요청하기</a>
    </div>
</section>
<section class="border-t bg-gray-50 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <h2 class="text-2xl font-bold">어떤 도움이 필요하신가요?</h2>
        <p class="mt-2 text-gray-600">서비스 카테고리 (추가 예정)</p>
    </div>
</section>
<section class="py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <h2 class="text-2xl font-bold">이렇게 간단합니다</h2>
        <p class="mt-2 text-gray-600">Step 1~3 이용 방법 (추가 예정)</p>
    </div>
</section>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__) . '/components/layout.php';
