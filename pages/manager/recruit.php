<?php
/**
 * 매니저 지원 페이지
 * URL: /manager/recruit
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$base = rtrim(BASE_URL, '/');
$pageTitle = '매니저 지원 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50';
ob_start();
?>
<div class="bg-white">
    <!-- 히어로 섹션 -->
    <section class="relative pt-20 pb-12 md:pt-28 md:pb-20 overflow-hidden">
        <!-- Background Blobs -->
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-orange-200/40 rounded-full blur-3xl -translate-y-1/2 translate-x-1/4 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-teal-200/30 rounded-full blur-3xl translate-y-1/4 -translate-x-1/4 pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-6 text-center relative z-10 pt-16">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900" data-aos="fade-up" data-aos-duration="1000">매니저 지원</h1>
            <p class="text-xl md:text-2xl text-gray-800 mb-2 mt-4" data-aos="fade-up" data-aos-delay="100" data-aos-duration="1000">성실하고 책임감있는 매니저를 모십니다.</p>
        </div>
    </section>

    <!-- 메인 콘텐츠 -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- 지원 안내 -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8 mb-8">
            <h2 class="text-3xl font-bold mb-6 text-gray-900">매니저 지원 안내</h2>
            
            <div class="space-y-6 text-gray-700 text-lg">
                <div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-900">모집 대상</h3>
                    <p class="text-lg">성실하고 책임감 있는 분들을 모집합니다. 경력과 나이에 상관없이 누구나 지원 가능합니다.</p>
                </div>

                <div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-900">지원 분야</h3>
                    <ul class="list-disc list-inside space-y-2 ml-4 text-lg">
                        <li>병원 동행 서비스</li>
                        <li>노인 돌봄 서비스</li>
                        <li>가사 도우미 서비스</li>
                        <li>기타 돌봄 서비스</li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-900">지원 방법</h3>
                    <p class="text-lg">아래 버튼을 클릭하여 간단한 회원가입을 진행해주세요. 회원가입 후 바로 활동을 시작할 수 있습니다.</p>
                </div>

                <div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-900">문의사항</h3>
                    <p class="text-lg">지원 과정에서 궁금한 사항이 있으시면 언제든지 고객센터로 문의해주세요.</p>
                </div>
            </div>
        </div>

        <!-- 지원 버튼 -->
        <div class="text-center space-y-4">
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="<?= $base ?>/manager/signup" 
                   class="min-h-[44px] inline-flex items-center justify-center px-8 py-3 bg-primary text-white rounded-lg font-medium text-lg hover:opacity-90 transition-opacity">
                    매니저 지원하기
                </a>
            </div>
            <p class="mt-4 text-base text-gray-600">
                이미 계정이 있으신가요? 
                <a href="<?= $base ?>/manager/login" class="text-primary hover:underline font-medium">로그인</a>
            </p>
        </div>

        <!-- 장점 섹션 -->
        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg border border-gray-200 p-6 text-center">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">안정적인 수익</h3>
                <p class="text-gray-600 text-base">공정한 정산 시스템으로 안정적인 수익을 보장합니다.</p>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-6 text-center">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">자유로운 일정</h3>
                <p class="text-gray-600 text-base">원하는 시간에 자유롭게 일정을 조율할 수 있습니다.</p>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-6 text-center">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">믿을 수 있는 플랫폼</h3>
                <p class="text-gray-600 text-base">검증된 시스템으로 안전하게 활동할 수 있습니다.</p>
            </div>
        </div>
    </div>
</div>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
?>
