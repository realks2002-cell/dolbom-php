<?php
/**
 * 관리자 전용 레이아웃
 * - 사이드바 포함
 * - 관리자 권한 체크
 */
if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config/app.php';
}
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

require_admin();

$pageTitle = $pageTitle ?? '관리자 - ' . APP_NAME;
$base = rtrim(BASE_URL, '/');
$currentRoute = $_GET['route'] ?? 'admin';
$layoutContent = $layoutContent ?? '';

// 사이드바 메뉴
$menuItems = [
    ['route' => 'admin', 'label' => '대시보드', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    ['route' => 'admin/users', 'label' => '회원 관리', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
    ['route' => 'admin/managers', 'label' => '매니저 관리', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
    ['route' => 'admin/requests', 'label' => '예약요청 및 매칭 현황', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
    ['route' => 'admin/payments', 'label' => '결제 내역 조회', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
    ['route' => 'admin/refunds', 'label' => '결제 취소', 'icon' => 'M6 18L18 6M6 6l12 12'],
    ['route' => 'admin/refund-info', 'label' => '환불정보', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    ['route' => 'admin/revenue', 'label' => '일/월 매출 집계', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="관리자 - 행복안심동행">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/tailwind.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/custom.css">
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="flex min-h-screen">
        <!-- 사이드바 -->
        <aside class="hidden w-64 bg-white border-r border-gray-200 md:block" aria-label="관리자 사이드바">
            <div class="flex flex-col h-full">
                <!-- 로고 -->
                <div class="flex items-center justify-center h-16 border-b border-gray-200">
                    <a href="<?= $base ?>/admin" class="text-xl font-bold text-primary"><?= APP_NAME ?> 관리자</a>
                </div>
                
                <!-- 메뉴 -->
                <nav class="flex-1 px-4 py-6 space-y-1" aria-label="관리자 메뉴">
                    <?php foreach ($menuItems as $item): ?>
                    <a href="<?= $base ?>/<?= htmlspecialchars($item['route']) ?>" 
                       class="min-h-[44px] flex items-center gap-3 px-4 py-2 text-sm font-medium rounded-lg transition-colors <?= str_starts_with($currentRoute, $item['route']) ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>"
                       aria-current="<?= str_starts_with($currentRoute, $item['route']) ? 'page' : 'false' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= htmlspecialchars($item['icon']) ?>" />
                        </svg>
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
                
                <!-- 하단 정보 -->
                <div class="p-4 border-t border-gray-200">
                    <div class="text-sm text-gray-600">
                        <?php
                        $adminName = '관리자';
                        $adminEmail = '';
                        if (!empty($_SESSION['admin_id'])) {
                            $adminName = htmlspecialchars($_SESSION['admin_id']);
                        } elseif (!empty($currentUser)) {
                            $adminName = htmlspecialchars($currentUser['name'] ?? '관리자');
                            $adminEmail = htmlspecialchars($currentUser['email'] ?? '');
                        }
                        ?>
                        <div class="font-medium"><?= $adminName ?></div>
                        <?php if ($adminEmail): ?>
                        <div class="text-xs text-gray-500 mt-1"><?= $adminEmail ?></div>
                        <?php endif; ?>
                    </div>
                    <a href="<?= $base ?>/auth/logout" class="mt-3 min-h-[44px] inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-red-600 border border-red-300 rounded-lg hover:bg-red-50">
                        로그아웃
                    </a>
                </div>
            </div>
        </aside>
        
        <!-- 메인 콘텐츠 -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- 모바일 헤더 -->
            <header class="md:hidden bg-white border-b border-gray-200 px-4 py-3">
                <div class="flex items-center justify-between">
                    <span class="text-lg font-bold text-primary"><?= APP_NAME ?> 관리자</span>
                    <button type="button" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100" aria-label="메뉴 열기" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </header>
            
            <!-- 모바일 메뉴 -->
            <div id="mobile-menu" class="hidden md:hidden bg-white border-b border-gray-200">
                <nav class="px-4 py-2 space-y-1">
                    <?php foreach ($menuItems as $item): ?>
                    <a href="<?= $base ?>/<?= htmlspecialchars($item['route']) ?>" 
                       class="min-h-[44px] flex items-center gap-3 px-4 py-2 text-sm font-medium rounded-lg transition-colors <?= str_starts_with($currentRoute, $item['route']) ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </div>
            
            <!-- 콘텐츠 영역 -->
            <main class="flex-1 overflow-y-auto p-4 md:p-8" role="main">
                <?= $layoutContent ?>
            </main>
        </div>
    </div>
    <script src="<?= $base ?>/assets/js/main.js"></script>
</body>
</html>
