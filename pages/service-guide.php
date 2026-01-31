<?php
/**
 * 서비스 이용 안내 페이지
 * URL: /service-guide
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
$base = rtrim(BASE_URL, '/');
$pageTitle = '서비스 이용 안내 - ' . APP_NAME;
ob_start();
?>
<section class="mx-auto max-w-4xl px-4 py-16 sm:px-6 sm:py-24">
    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl mb-8">서비스 이용 안내</h1>
    
    <div class="prose prose-lg max-w-none">
        <div class="mb-12">
            <h2 class="text-2xl font-semibold mb-6">이용 절차</h2>
            <div class="space-y-6">
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold">1</div>
                    <div>
                        <h3 class="font-semibold text-lg mb-2">서비스 요청</h3>
                        <p class="text-gray-700">원하는 서비스 유형, 일시, 위치를 선택하고 상세 정보를 입력합니다.</p>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold">2</div>
                    <div>
                        <h3 class="font-semibold text-lg mb-2">결제</h3>
                        <p class="text-gray-700">카드 등록 후 안전하게 결제를 진행합니다.</p>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold">3</div>
                    <div>
                        <h3 class="font-semibold text-lg mb-2">매니저 매칭</h3>
                        <p class="text-gray-700">결제 완료 후 매니저들이 지원하고, 고객이 매니저를 선택합니다.</p>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold">4</div>
                    <div>
                        <h3 class="font-semibold text-lg mb-2">서비스 제공</h3>
                        <p class="text-gray-700">약속한 일시에 매니저가 방문하여 서비스를 제공합니다.</p>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold">5</div>
                    <div>
                        <h3 class="font-semibold text-lg mb-2">후기 작성</h3>
                        <p class="text-gray-700">서비스 완료 후 후기를 작성하여 다른 고객들에게 도움을 줍니다.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mb-12 border-t pt-8">
            <h2 class="text-2xl font-semibold mb-4">이용 안내</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="font-semibold mb-2">서비스 시간</h3>
                    <p class="text-gray-700">24시간 서비스 요청이 가능하며, 매니저 매칭은 실시간으로 진행됩니다.</p>
                </div>
                <div>
                    <h3 class="font-semibold mb-2">결제 방법</h3>
                    <p class="text-gray-700">카드 등록 후 자동 결제 방식으로 진행됩니다. 안전한 토스페이먼츠를 통해 결제가 처리됩니다.</p>
                </div>
                <div>
                    <h3 class="font-semibold mb-2">취소 및 환불</h3>
                    <p class="text-gray-700">서비스 시작 전까지 취소 가능하며, 환불은 결제 수단으로 자동 처리됩니다.</p>
                </div>
            </div>
        </div>
        
        <div class="mb-8 border-t pt-8">
            <h2 class="text-2xl font-semibold mb-4">주의사항</h2>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
                <li>서비스 요청 시 정확한 정보를 입력해주세요.</li>
                <li>서비스 일시 변경은 최소 24시간 전에 요청해주세요.</li>
                <li>매니저와의 약속 시간을 지켜주세요.</li>
                <li>서비스 중 문제가 발생하면 즉시 고객센터로 연락주세요.</li>
            </ul>
        </div>
    </div>
</section>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__) . '/components/layout.php';
