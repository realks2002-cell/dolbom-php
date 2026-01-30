<?php
/**
 * 공통 헤더 (PRD 4.1)
 * - 로고, 네비게이션, 우측 메뉴(로그인/회원가입 또는 알림·프로필)
 * - 모바일: 햄버거 메뉴
 */
$base = $base ?? rtrim(BASE_URL ?? '', '/');
$userRole = $userRole ?? null;
$currentUser = $currentUser ?? null;
?>
<header class="sticky top-0 z-50 border-b bg-white/95 backdrop-blur" role="banner">
    <div class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-4 px-4 sm:h-16 sm:px-6">
        <!-- 로고 -->
        <a href="<?= $base ?>/" class="flex shrink-0 items-center gap-2" aria-label="<?= APP_NAME ?> 홈">
            <span class="text-xl font-bold tracking-tight"><?= APP_NAME ?></span>
        </a>

        <!-- 데스크톱 네비 -->
        <nav class="hidden items-center gap-6 md:flex" aria-label="주요 메뉴">
            <a href="<?= $base ?>/about" class="text-sm font-medium text-gray-700 hover:text-gray-900">회사소개</a>
            <a href="<?= $base ?>/service-guide" class="text-sm font-medium text-gray-700 hover:text-gray-900">서비스이용</a>
            <a href="<?= $base ?>/faq" class="text-sm font-medium text-gray-700 hover:text-gray-900">자주묻는 질문</a>
            <a href="<?= $base ?>/manager/recruit" class="text-sm font-medium text-gray-700 hover:text-gray-900">매니저 지원</a>
            <?php if ($userRole === ROLE_CUSTOMER): ?>
                <a href="<?= $base ?>/requests/new" class="text-sm font-medium text-gray-700 hover:text-gray-900">서비스 요청</a>
                <a href="<?= $base ?>/bookings" class="text-sm font-medium text-gray-700 hover:text-gray-900">내 예약</a>
            <?php elseif ($userRole === ROLE_MANAGER): ?>
                <a href="<?= $base ?>/manager/requests" class="text-sm font-medium text-gray-700 hover:text-gray-900">새 요청</a>
                <a href="<?= $base ?>/manager/schedule" class="text-sm font-medium text-gray-700 hover:text-gray-900">내 일정</a>
            <?php endif; ?>
        </nav>

        <!-- 우측: 로그인/회원가입 또는 알림·프로필 -->
        <div class="flex shrink-0 items-center gap-2">
            <?php if ($userRole === null): ?>
                <a href="<?= $base ?>/auth/login" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg px-4 text-sm font-medium text-gray-700 hover:bg-gray-100">로그인</a>
                <a href="<?= $base ?>/auth/signup" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg bg-primary px-4 text-sm font-medium text-white hover:opacity-90">회원가입</a>
            <?php else: ?>
                <!-- 알림 아이콘 (추후 뱃지) -->
                <button type="button" class="relative min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100" aria-label="알림">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                </button>
                <span class="hidden text-sm text-gray-600 sm:inline"><?= $currentUser ? htmlspecialchars($currentUser['name']) : '' ?> 님</span>
                <a href="<?= $base ?>/auth/logout" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg px-4 text-sm font-medium text-gray-700 hover:bg-gray-100">로그아웃</a>
            <?php endif; ?>

            <!-- 모바일 햄버거 -->
            <button type="button" id="mobile-menu-toggle" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 md:hidden" aria-label="메뉴 열기" aria-expanded="false">
                <svg id="menu-icon" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                <svg id="close-icon" class="h-6 w-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </div>
    
    <!-- 모바일 메뉴 -->
    <div id="mobile-menu" class="hidden md:hidden border-t bg-white">
        <nav class="px-4 py-4 space-y-1" aria-label="모바일 메뉴">
            <a href="<?= $base ?>/about" class="min-h-[44px] flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">회사소개</a>
            <a href="<?= $base ?>/service-guide" class="min-h-[44px] flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">서비스이용</a>
            <a href="<?= $base ?>/faq" class="min-h-[44px] flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">자주묻는 질문</a>
            <a href="<?= $base ?>/manager/recruit" class="min-h-[44px] flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">매니저 지원</a>
            <?php if ($userRole === ROLE_CUSTOMER): ?>
                <a href="<?= $base ?>/requests/new" class="min-h-[44px] flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">서비스 요청</a>
                <a href="<?= $base ?>/bookings" class="min-h-[44px] flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">내 예약</a>
            <?php elseif ($userRole === ROLE_MANAGER): ?>
                <a href="<?= $base ?>/manager/requests" class="min-h-[44px] flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">새 요청</a>
                <a href="<?= $base ?>/manager/schedule" class="min-h-[44px] flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">내 일정</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
