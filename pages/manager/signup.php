<?php
/**
 * 매니저 회원가입 페이지
 * URL: /manager/signup
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/security.php';

init_session();

$base = rtrim(BASE_URL, '/');
$error = '';

// 이미 로그인한 매니저는 대시보드로 리다이렉트
if (!empty($_SESSION['manager_id'])) {
    redirect('/manager/dashboard');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $gender = trim((string) ($_POST['gender'] ?? ''));
    $bank = trim((string) ($_POST['bank'] ?? ''));
    $ssn = trim((string) ($_POST['ssn'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address1 = trim((string) ($_POST['address1'] ?? ''));
    $address2 = trim((string) ($_POST['address2'] ?? ''));
    $accountNumber = trim((string) ($_POST['account_number'] ?? ''));
    $specialty = trim((string) ($_POST['specialty'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $terms = !empty($_POST['terms']);
    $privacy = !empty($_POST['privacy']);

    if (!$terms) {
        $error = '서비스 이용약관에 동의해주세요.';
    } elseif (!$privacy) {
        $error = '개인정보 이용에 동의해주세요.';
    } elseif ($name === '') {
        $error = '이름을 입력해주세요.';
    } elseif ($gender === '') {
        $error = '성별을 선택해주세요.';
    } elseif ($ssn === '') {
        $error = '주민번호를 입력해주세요.';
    } elseif ($phone === '') {
        $error = '전화번호를 입력해주세요.';
    } elseif ($address1 === '') {
        $error = '주소를 입력해주세요.';
    } elseif ($bank === '') {
        $error = '은행을 선택해주세요.';
    } elseif ($accountNumber === '') {
        $error = '계좌번호를 입력해주세요.';
    } elseif (strlen($password) < 6) {
        $error = '비밀번호는 6자 이상 입력해주세요.';
    } elseif ($password !== $passwordConfirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } else {
        $pdo = require __DIR__ . '/../../database/connect.php';
        // 사진 업로드 처리 (선택)
        $photoPath = null;
        if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['photo']['tmp_name'];
            $info = @getimagesize($tmp);
            if ($info === false) {
                // 이미지 아님, 무시
            } else {
                $uploadDir = __DIR__ . '/../../storage/managers';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = 'mgr_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . ($ext ?: 'jpg');
                $dest = $uploadDir . '/' . $filename;
                
                // 이미지 리사이즈 및 압축 (1MB 이하로) 시도
                $resizeSuccess = false;
                if (function_exists('resize_and_compress_image')) {
                    $resizeSuccess = resize_and_compress_image($tmp, $dest, 1920, 1920, 1048576);
                }
                
                // 리사이즈 실패 시 원본 파일 그대로 저장
                if (!$resizeSuccess) {
                    error_log('[manager/signup] 이미지 리사이즈 실패, 원본 파일 저장');
                    if (@move_uploaded_file($tmp, $dest)) {
                        $photoPath = '/storage/managers/' . $filename;
                    }
                } else {
                    // 리사이즈 성공
                    $photoPath = '/storage/managers/' . $filename;
                }
            }
        }
        
        // 중복 체크
        $st = $pdo->prepare('SELECT 1 FROM managers WHERE phone = ? OR ssn = ?');
        $st->execute([$phone, $ssn]);
        if ($st->fetch()) {
            $error = '이미 등록된 전화번호 또는 주민번호입니다.';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $managerId = uuid4(); // UUID 생성

                // approval_status = 'pending'으로 등록 (관리자 승인 필요)
                $st = $pdo->prepare('INSERT INTO managers (id, name, ssn, phone, address1, address2, account_number, bank, specialty, photo, gender, password_hash, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $st->execute([
                    $managerId, // UUID
                    $name,
                    $ssn, // 주민번호 (평문)
                    $phone,
                    $address1,
                    $address2 === '' ? null : $address2,
                    $accountNumber,
                    $bank === '' ? null : $bank,
                    $specialty === '' ? null : $specialty,
                    $photoPath === null ? null : $photoPath,
                    $gender === '' ? null : $gender,
                    $hash,
                    'pending' // 승인 대기 상태
                ]);

                // 자동 로그인 제거 - 승인 대기 안내 페이지로 이동
                init_session();
                $_SESSION['signup_completed'] = true;
                $_SESSION['signup_manager_name'] = $name;
                $_SESSION['signup_manager_phone'] = $phone;

                redirect('/manager/signup-complete');
            } catch (Exception $e) {
                $error = '회원가입 중 오류가 발생했습니다: ' . $e->getMessage();
                error_log('[manager/signup] 에러: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            }
        }
    }
}

$pageTitle = '매니저 회원가입 - ' . APP_NAME;
$mainClass = 'min-h-screen bg-gray-50 px-4 py-12';
ob_start();
?>
<div class="mx-auto max-w-2xl mt-48 text-base">
    <!-- 헤더 -->
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-primary"><?= APP_NAME ?></h1>
        <p class="mt-2 text-lg text-gray-600">매니저 회원가입</p>
    </div>

            <!-- 회원가입 폼 -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
                <?php if ($error): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-base">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="post" action="<?= $base ?>/manager/signup" enctype="multipart/form-data" class="space-y-4">
                    <!-- 이름 -->
                    <div>
                        <label for="name" class="block text-base font-medium text-gray-700 mb-1">이름 <span class="text-red-500">*</span></label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                            class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                            placeholder="홍길동"
                            required>
                    </div>

                    <!-- 성별 (필수) -->
                    <div>
                        <label class="block text-base font-medium text-gray-700 mb-1">성별 <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-4">
                            <label class="inline-flex items-center text-base cursor-pointer">
                                <input type="radio" name="gender" value="M" <?= (isset($_POST['gender']) && $_POST['gender'] === 'M') ? 'checked' : '' ?> class="h-4 w-4 text-primary border-gray-300" required>
                                <span class="ml-2">남</span>
                            </label>
                            <label class="inline-flex items-center text-base cursor-pointer">
                                <input type="radio" name="gender" value="F" <?= (isset($_POST['gender']) && $_POST['gender'] === 'F') ? 'checked' : '' ?> class="h-4 w-4 text-primary border-gray-300" required>
                                <span class="ml-2">여</span>
                            </label>
                        </div>
                    </div>

                    <!-- 사진 업로드 -->
                    <div>
                        <label for="photo" class="block text-base font-medium text-gray-700 mb-1">사진 업로드</label>
                        <input type="file" id="photo" name="photo" accept="image/*" class="block w-full text-base text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-primary file:text-white" />
                    </div>

                    <!-- 주민번호 -->
                    <div>
                        <label for="ssn" class="block text-base font-medium text-gray-700 mb-1">주민번호 <span class="text-red-500">*</span></label>
                        <input 
                            type="text" 
                            id="ssn" 
                            name="ssn" 
                            value="<?= htmlspecialchars($_POST['ssn'] ?? '') ?>"
                            class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                            placeholder="123456-1234567"
                            required>
                    </div>

                    <!-- 전화번호 -->
                    <div>
                        <label for="phone" class="block text-base font-medium text-gray-700 mb-1">전화번호 <span class="text-red-500">*</span></label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                            class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                            placeholder="010-1234-5678"
                            required
                            autocomplete="tel">
                    </div>

                    <!-- 주소1 -->
                    <div>
                        <label for="address1" class="block text-base font-medium text-gray-700 mb-1">주소 <span class="text-red-500">*</span></label>
                        <input 
                            type="text" 
                            id="address1" 
                            name="address1" 
                            value="<?= htmlspecialchars($_POST['address1'] ?? '') ?>"
                            class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                            placeholder="서울시 강남구 테헤란로 123"
                            required>
                    </div>

                    <!-- 주소2 -->
                    <div>
                        <label for="address2" class="block text-base font-medium text-gray-700 mb-1">상세주소</label>
                        <input 
                            type="text" 
                            id="address2" 
                            name="address2" 
                            value="<?= htmlspecialchars($_POST['address2'] ?? '') ?>"
                            class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                            placeholder="101동 101호">
                    </div>

                    <!-- 은행 선택 -->
                    <div>
                        <label for="bank" class="block text-base font-medium text-gray-700 mb-1">은행 <span class="text-red-500">*</span></label>
                        <select id="bank" name="bank" class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 bg-white focus:ring-2 focus:ring-primary focus:border-transparent" required>
                            <?php $b = $_POST['bank'] ?? ''; ?>
                            <option value="" <?= $b === '' ? 'selected' : '' ?> disabled>은행을 선택하세요</option>
                            <option value="국민은행" <?= $b === '국민은행' ? 'selected' : '' ?>>국민은행</option>
                            <option value="신한은행" <?= $b === '신한은행' ? 'selected' : '' ?>>신한은행</option>
                            <option value="하나은행" <?= $b === '하나은행' ? 'selected' : '' ?>>하나은행</option>
                            <option value="우리은행" <?= $b === '우리은행' ? 'selected' : '' ?>>우리은행</option>
                            <option value="농협" <?= $b === '농협' ? 'selected' : '' ?>>농협</option>
                            <option value="기업은행" <?= $b === '기업은행' ? 'selected' : '' ?>>기업은행</option>
                            <option value="카카오뱅크" <?= $b === '카카오뱅크' ? 'selected' : '' ?>>카카오뱅크</option>
                            <option value="케이뱅크" <?= $b === '케이뱅크' ? 'selected' : '' ?>>케이뱅크</option>
                            <option value="수협은행" <?= $b === '수협은행' ? 'selected' : '' ?>>수협은행</option>
                            <option value="SC제일은행" <?= $b === 'SC제일은행' ? 'selected' : '' ?>>SC제일은행</option>
                            <option value="부산은행" <?= $b === '부산은행' ? 'selected' : '' ?>>부산은행</option>
                            <option value="대구은행" <?= $b === '대구은행' ? 'selected' : '' ?>>대구은행</option>
                            <option value="광주은행" <?= $b === '광주은행' ? 'selected' : '' ?>>광주은행</option>
                            <option value="경남은행" <?= $b === '경남은행' ? 'selected' : '' ?>>경남은행</option>
                            <option value="제주은행" <?= $b === '제주은행' ? 'selected' : '' ?>>제주은행</option>
                            <option value="우체국" <?= $b === '우체국' ? 'selected' : '' ?>>우체국</option>
                            <option value="토스뱅크" <?= $b === '토스뱅크' ? 'selected' : '' ?>>토스뱅크</option>
                        </select>
                    </div>

                    <!-- 계좌번호 -->
                    <div>
                        <label for="account_number" class="block text-base font-medium text-gray-700 mb-1">계좌번호 <span class="text-red-500">*</span></label>
                        <input 
                            type="text" 
                            id="account_number" 
                            name="account_number" 
                            value="<?= htmlspecialchars($_POST['account_number'] ?? '') ?>"
                            class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                            placeholder="123-456-789012"
                            required>
                    </div>

                    <!-- 특기 -->
                    <div>
                        <label for="specialty" class="block text-base font-medium text-gray-700 mb-1">특기</label>
                        <input 
                            type="text" 
                            id="specialty" 
                            name="specialty" 
                            value="<?= htmlspecialchars($_POST['specialty'] ?? '') ?>"
                            class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                            placeholder="예: 간병, 요리, 청소, 반려동물 돌봄 등">
                    </div>

                    <!-- 비밀번호 -->
                    <div>
                        <label for="password" class="block text-base font-medium text-gray-700 mb-1">비밀번호 <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 pr-12 focus:ring-2 focus:ring-primary focus:border-transparent" 
                                placeholder="6자 이상"
                                required
                                autocomplete="new-password">
                            <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                                <svg id="eye-password" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- 비밀번호 확인 -->
                    <div>
                        <label for="password_confirm" class="block text-base font-medium text-gray-700 mb-1">비밀번호 확인 <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password_confirm" 
                                name="password_confirm" 
                                class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 pr-12 focus:ring-2 focus:ring-primary focus:border-transparent" 
                                placeholder="6자 이상"
                                required
                                autocomplete="new-password">
                            <button type="button" onclick="togglePassword('password_confirm')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                                <svg id="eye-password_confirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- 약관 동의 -->
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <input 
                                type="checkbox" 
                                id="terms" 
                                name="terms" 
                                class="mt-1 h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary"
                                required>
                            <label for="terms" class="ml-2 text-base text-gray-700">
                                <a href="#" class="text-primary hover:underline">서비스 이용약관</a>에 동의합니다. <span class="text-red-500">*</span>
                            </label>
                        </div>
                        <div class="flex items-start">
                            <input 
                                type="checkbox" 
                                id="privacy" 
                                name="privacy" 
                                class="mt-1 h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary"
                                required>
                            <label for="privacy" class="ml-2 text-base text-gray-700">
                                <a href="#" class="text-primary hover:underline">개인정보 이용</a>에 동의합니다. <span class="text-red-500">*</span>
                            </label>
                        </div>
                    </div>

                    <!-- 제출 버튼 -->
                    <button 
                        type="submit" 
                        class="min-h-[44px] w-full bg-primary text-white text-lg rounded-lg font-medium hover:opacity-90 transition-opacity">
                        회원가입
                    </button>
                </form>
            </div>

    <!-- 하단 링크 -->
    <div class="mt-6 text-center">
        <p class="text-base text-gray-600">
            이미 계정이 있으신가요? 
            <a href="<?= $base ?>/manager/login" class="text-primary hover:underline font-medium">로그인</a>
        </p>
    </div>
</div>

<script>
// 비밀번호 표시/숨김 토글
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const eye = document.getElementById('eye-' + inputId);
    
    if (input.type === 'password') {
        input.type = 'text';
        eye.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
    } else {
        input.type = 'password';
        eye.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
    }
}
</script>

<?php
$layoutContent = ob_get_clean();
require __DIR__ . '/../../components/layout.php';
