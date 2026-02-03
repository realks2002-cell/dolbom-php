<?php
/**
 * 공통 헤더 (랜딩 디자인)
 * - 로고, 네비게이션, 로그인/회원가입
 * - 모바일: 햄버거 메뉴, Lucide 아이콘
 */
$base = $base ?? rtrim(BASE_URL ?? '', '/');
$userRole = $userRole ?? null;
$currentUser = $currentUser ?? null;
?>
<nav id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 py-5" role="banner">
    <div class="max-w-7xl mx-auto px-6 flex items-center justify-between">
        <a href="<?= $base ?>/" class="flex items-center gap-2 group" aria-label="<?= APP_NAME ?> 홈">
            <div class="bg-orange-500 text-white p-2 rounded-xl group-hover:rotate-12 transition-transform duration-300">
                <i data-lucide="heart-handshake" class="w-6 h-6"></i>
            </div>
            <span class="text-2xl font-bold tracking-tight text-gray-900">
                행복안심<span class="text-orange-500">동행</span>
            </span>
        </a>

        <!-- Desktop Links -->
        <div class="hidden md:flex items-center gap-8">
            <a href="<?= $base ?>/about" class="text-lg text-gray-900 hover:text-orange-600 font-medium transition-colors">회사소개</a>
            <a href="<?= $base ?>/service-guide" class="text-lg text-gray-900 hover:text-orange-600 font-medium transition-colors">서비스이용</a>
            <a href="<?= $base ?>/manager/recruit" class="text-lg text-gray-900 hover:text-orange-600 font-medium transition-colors">매니저 지원</a>
            <a href="<?= $base ?>/faq" class="text-lg text-gray-900 hover:text-orange-600 font-medium transition-colors">자주묻는 질문</a>
            <a href="<?= $base ?>/bookings/guest-check" class="text-lg text-gray-900 hover:text-orange-600 font-medium transition-colors">예약조회</a>
            <?php if (isset($userRole) && $userRole === ROLE_CUSTOMER): ?>
                <a href="<?= $base ?>/requests/new" class="text-lg text-gray-900 hover:text-orange-600 font-medium transition-colors">서비스 요청</a>
                <a href="<?= $base ?>/bookings" class="text-lg text-gray-900 hover:text-orange-600 font-medium transition-colors">내 예약</a>
            <?php elseif (isset($userRole) && $userRole === ROLE_MANAGER): ?>
                <a href="<?= $base ?>/manager/requests" class="text-lg text-gray-900 hover:text-orange-600 font-medium transition-colors">새 요청</a>
                <a href="<?= $base ?>/manager/schedule" class="text-lg text-gray-900 hover:text-orange-600 font-medium transition-colors">내 일정</a>
            <?php endif; ?>
            <?php if ($userRole === null): ?>
                <a href="<?= $base ?>/auth/login" class="text-lg px-6 py-3 rounded-full font-semibold transition-all duration-300 active:scale-95 flex items-center justify-center gap-2 text-gray-900 hover:text-orange-600">회원 로그인</a>
                <a href="<?= $base ?>/auth/signup" class="text-lg px-6 py-3 rounded-full font-semibold transition-all duration-300 active:scale-95 flex items-center justify-center gap-2 bg-orange-500 hover:bg-orange-600 text-white shadow-lg shadow-orange-500/20">회원가입</a>
            <?php else: ?>
                <span class="text-base text-gray-900"><?= $currentUser ? htmlspecialchars($currentUser['name']) : '' ?> 님</span>
                <a href="<?= $base ?>/auth/logout" class="text-lg px-6 py-3 rounded-full font-semibold transition-all duration-300 active:scale-95 flex items-center justify-center gap-2 text-gray-900 hover:text-orange-600">로그아웃</a>
            <?php endif; ?>
        </div>

        <!-- Mobile Menu Toggle -->
        <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-900 min-h-[44px] min-w-[44px]" aria-label="메뉴 열기">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
    </div>
    
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
        <nav class="px-4 py-4 space-y-1" aria-label="모바일 메뉴">
            <a href="<?= $base ?>/about" class="min-h-[44px] flex items-center px-4 py-2 text-base font-medium text-gray-900 rounded-lg hover:bg-gray-100">회사소개</a>
            <a href="<?= $base ?>/service-guide" class="min-h-[44px] flex items-center px-4 py-2 text-base font-medium text-gray-900 rounded-lg hover:bg-gray-100">서비스이용</a>
            <a href="<?= $base ?>/manager/recruit" class="min-h-[44px] flex items-center px-4 py-2 text-base font-medium text-gray-900 rounded-lg hover:bg-gray-100">매니저 지원</a>
            <a href="<?= $base ?>/faq" class="min-h-[44px] flex items-center px-4 py-2 text-base font-medium text-gray-900 rounded-lg hover:bg-gray-100">자주묻는 질문</a>
            <a href="<?= $base ?>/bookings/guest-check" class="min-h-[44px] flex items-center px-4 py-2 text-base font-medium text-gray-900 rounded-lg hover:bg-gray-100">예약조회</a>
            <?php if (isset($userRole) && $userRole === ROLE_CUSTOMER): ?>
                <a href="<?= $base ?>/requests/new" class="min-h-[44px] flex items-center px-4 py-2 text-base font-medium text-gray-900 rounded-lg hover:bg-gray-100">서비스 요청</a>
                <a href="<?= $base ?>/bookings" class="min-h-[44px] flex items-center px-4 py-2 text-base font-medium text-gray-900 rounded-lg hover:bg-gray-100">내 예약</a>
            <?php elseif (isset($userRole) && $userRole === ROLE_MANAGER): ?>
                <a href="<?= $base ?>/manager/requests" class="min-h-[44px] flex items-center px-4 py-2 text-base font-medium text-gray-900 rounded-lg hover:bg-gray-100">새 요청</a>
                <a href="<?= $base ?>/manager/schedule" class="min-h-[44px] flex items-center px-4 py-2 text-base font-medium text-gray-900 rounded-lg hover:bg-gray-100">내 일정</a>
            <?php endif; ?>
            <?php if ($userRole === null): ?>
                <a href="<?= $base ?>/auth/login" class="min-h-[44px] flex items-center px-4 py-2 text-base font-medium text-gray-900 rounded-lg hover:bg-gray-100">회원 로그인</a>
                <a href="<?= $base ?>/auth/signup" class="min-h-[44px] flex items-center px-4 py-2 text-base font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600">회원가입</a>
            <?php else: ?>
                <div class="min-h-[44px] flex items-center px-4 py-2 text-base font-medium text-gray-900"><?= $currentUser ? htmlspecialchars($currentUser['name']) : '' ?> 님</div>
                <a href="<?= $base ?>/auth/logout" class="min-h-[44px] flex items-center px-4 py-2 text-base font-medium text-gray-900 rounded-lg hover:bg-gray-100">로그아웃</a>
            <?php endif; ?>
        </nav>
    </div>
</nav>
