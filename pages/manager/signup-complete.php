<?php
/**
 * 매니저 지원 접수 완료 페이지 (승인 대기 안내)
 * URL: /manager/signup-complete
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

init_session();

$base = rtrim(BASE_URL, '/');

// 지원 완료 세션 확인
$signupCompleted = $_SESSION['signup_completed'] ?? false;
$managerName = $_SESSION['signup_manager_name'] ?? '';

// 세션 데이터 삭제 (일회성)
unset($_SESSION['signup_completed'], $_SESSION['signup_manager_name'], $_SESSION['signup_manager_phone']);

$pageTitle = '매니저 지원 접수 - ' . APP_NAME;
$mainClass = 'min-h-screen flex flex-col items-center justify-center bg-gray-50 px-4';
ob_start();
?>
<div class="w-full max-w-md text-center">
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
        <!-- 시계 아이콘 (대기 중) -->
        <div class="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-6">
            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-2">매니저 지원이 접수되었습니다</h1>

        <?php if ($managerName): ?>
        <p class="text-gray-600 mb-4"><?= htmlspecialchars($managerName) ?>님, 지원해 주셔서 감사합니다.</p>
        <?php endif; ?>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-sm text-yellow-800">
                <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <strong>승인 대기 중</strong><br>
                관리자가 지원 내용을 검토 중입니다.<br>
                승인 후 로그인이 가능합니다.
            </p>
        </div>

        <a href="<?= $base ?>/" class="inline-block w-full min-h-[44px] bg-primary text-white rounded-lg font-medium hover:opacity-90 transition-opacity py-3">
            홈으로 이동
        </a>
    </div>
</div>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/layout.php';
