<?php
/**
 * 자주묻는 질문 페이지
 * URL: /faq
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
$base = rtrim(BASE_URL, '/');
$pageTitle = '자주묻는 질문 - ' . APP_NAME;
ob_start();
?>
<section class="mx-auto max-w-4xl px-4 py-16 sm:px-6 sm:py-24">
    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl mb-8">자주묻는 질문</h1>
    
    <div class="space-y-6">
        <div class="border rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-3">Q1. 서비스 요청은 어떻게 하나요?</h2>
            <p class="text-gray-700">
                회원가입 후 "서비스 요청" 메뉴에서 원하는 서비스 유형, 일시, 위치를 선택하고 상세 정보를 입력한 후 결제를 진행하시면 됩니다.
            </p>
        </div>
        
        <div class="border rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-3">Q2. 매니저는 어떻게 선택되나요?</h2>
            <p class="text-gray-700">
                결제 완료 후 매니저들이 지원하고, 고객님이 여러 지원자 중에서 매니저를 선택할 수 있습니다. 매니저의 프로필과 후기를 확인하실 수 있습니다.
            </p>
        </div>
        
        <div class="border rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-3">Q3. 서비스를 취소할 수 있나요?</h2>
            <p class="text-gray-700">
                네, 서비스 시작 전까지 취소 가능합니다. "내 예약" 페이지에서 취소할 수 있으며, 환불은 자동으로 처리됩니다.
            </p>
        </div>
        
        <div class="border rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-3">Q4. 결제는 어떻게 하나요?</h2>
            <p class="text-gray-700">
                카드 등록 후 안전한 토스페이먼츠를 통해 결제됩니다. 카드 정보는 안전하게 암호화되어 저장됩니다.
            </p>
        </div>
        
        <div class="border rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-3">Q5. 매니저가 되려면 어떻게 해야 하나요?</h2>
            <p class="text-gray-700">
                "매니저지원" 메뉴에서 회원가입을 진행하시면 됩니다. 신분증, 계좌 정보 등을 입력하시면 심사 후 활동이 가능합니다.
            </p>
        </div>
        
        <div class="border rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-3">Q6. 서비스 비용은 어떻게 책정되나요?</h2>
            <p class="text-gray-700">
                서비스 유형과 시간에 따라 차등 요금제가 적용됩니다. 서비스 요청 시 예상 금액이 표시됩니다.
            </p>
        </div>
        
        <div class="border rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-3">Q7. 문제가 발생하면 어떻게 하나요?</h2>
            <p class="text-gray-700">
                서비스 중 문제가 발생하면 즉시 고객센터(1588-0000)로 연락주시거나, "내 예약" 페이지에서 문의하실 수 있습니다.
            </p>
        </div>
        
        <div class="border rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-3">Q8. 환불은 언제 처리되나요?</h2>
            <p class="text-gray-700">
                취소 요청 후 3-5일 내에 결제 수단으로 자동 환불 처리됩니다. 환불 내역은 "내 예약" 페이지에서 확인하실 수 있습니다.
            </p>
        </div>
    </div>
    
    <div class="mt-12 p-6 bg-gray-50 rounded-lg">
        <h2 class="text-xl font-semibold mb-3">추가 문의</h2>
        <p class="text-gray-700 mb-4">
            위의 질문으로 해결되지 않으신 경우, 아래로 문의해주세요.
        </p>
        <p class="text-gray-700">
            이메일: support@hangbok77.com<br>
            전화: 1588-0000<br>
            운영시간: 평일 09:00 - 18:00
        </p>
    </div>
</section>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__) . '/components/layout.php';
