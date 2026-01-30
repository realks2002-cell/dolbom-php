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
    <div class="bg-gradient-to-r from-primary to-blue-600 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">매니저 지원</h1>
            <p class="text-xl md:text-2xl text-blue-100">성실하고 책임감있는 매니저를 모십니다.</p>
            <p class="mt-4 text-lg text-blue-100">언제든지 환영합니다.</p>
        </div>
    </div>

    <!-- 메인 콘텐츠 -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- 지원 안내 -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-900">매니저 지원 안내</h2>
            
            <div class="space-y-6 text-gray-700">
                <div>
                    <h3 class="text-lg font-semibold mb-2 text-gray-900">모집 대상</h3>
                    <p>성실하고 책임감 있는 분들을 모집합니다. 경력과 나이에 상관없이 누구나 지원 가능합니다.</p>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-2 text-gray-900">지원 분야</h3>
                    <ul class="list-disc list-inside space-y-2 ml-4">
                        <li>병원 동행 서비스</li>
                        <li>노인 돌봄 서비스</li>
                        <li>가사 도우미 서비스</li>
                        <li>기타 돌봄 서비스</li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-2 text-gray-900">지원 방법</h3>
                    <p>아래 버튼을 클릭하여 간단한 회원가입을 진행해주세요. 회원가입 후 바로 활동을 시작할 수 있습니다.</p>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-2 text-gray-900">문의사항</h3>
                    <p>지원 과정에서 궁금한 사항이 있으시면 언제든지 고객센터로 문의해주세요.</p>
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
                <button 
                    id="installAppBtn"
                    class="min-h-[44px] inline-flex items-center justify-center px-8 py-3 bg-green-600 text-white rounded-lg font-medium text-lg hover:opacity-90 transition-opacity hidden">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    매니저 앱 다운받기
                </button>
                <a href="http://localhost:3000/install" 
                   id="goToAppBtn"
                   target="_blank"
                   class="min-h-[44px] inline-flex items-center justify-center px-8 py-3 bg-green-600 text-white rounded-lg font-medium text-lg hover:opacity-90 transition-opacity">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    매니저 앱 다운받기
                </a>
            </div>
            <p class="mt-4 text-sm text-gray-600">
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
                <h3 class="text-lg font-semibold mb-2">안정적인 수익</h3>
                <p class="text-gray-600 text-sm">공정한 정산 시스템으로 안정적인 수익을 보장합니다.</p>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-6 text-center">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold mb-2">자유로운 일정</h3>
                <p class="text-gray-600 text-sm">원하는 시간에 자유롭게 일정을 조율할 수 있습니다.</p>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-6 text-center">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold mb-2">믿을 수 있는 플랫폼</h3>
                <p class="text-gray-600 text-sm">검증된 시스템으로 안전하게 활동할 수 있습니다.</p>
            </div>
        </div>
    </div>
</div>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
?>
<script>
// PWA 설치 기능
let deferredPrompt;
const installBtn = document.getElementById('installAppBtn');
const goToAppBtn = document.getElementById('goToAppBtn');

// PWA 설치 가능 시 이벤트 캡처
window.addEventListener('beforeinstallprompt', (e) => {
    // 기본 설치 팝업 방지
    e.preventDefault();
    deferredPrompt = e;
    
    // 설치 버튼 표시
    if (installBtn) {
        installBtn.classList.remove('hidden');
        goToAppBtn.classList.add('hidden');
    }
});

// 설치 버튼 클릭 시
if (installBtn) {
    installBtn.addEventListener('click', async () => {
        if (!deferredPrompt) {
            // 설치 불가능한 경우 Vue.js 앱으로 이동
            window.location.href = 'http://localhost:3000';
            return;
        }
        
        // 설치 팝업 표시
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        
        if (outcome === 'accepted') {
            console.log('PWA 설치됨');
            installBtn.textContent = '앱이 설치되었습니다!';
            installBtn.disabled = true;
        } else {
            console.log('PWA 설치 취소됨');
        }
        
        deferredPrompt = null;
        installBtn.classList.add('hidden');
    });
}

// 이미 설치된 경우 버튼 숨기기
if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
    if (installBtn) installBtn.classList.add('hidden');
    if (goToAppBtn) goToAppBtn.classList.add('hidden');
}
</script>
