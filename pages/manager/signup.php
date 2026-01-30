<?php
/**
 * 매니저 회원가입 페이지
 * URL: /manager/signup
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

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
    } elseif (strlen($password) < 8) {
        $error = '비밀번호는 8자 이상 입력해주세요.';
    } elseif ($password !== $passwordConfirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } else {
        $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
        // 사진 업로드 처리 (선택)
        $photoPath = null;
        if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['photo']['tmp_name'];
            $info = @getimagesize($tmp);
            if ($info === false) {
                // 이미지 아님, 무시
            } else {
                $uploadDir = dirname(__DIR__, 2) . '/storage/managers';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = 'mgr_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . ($ext ?: 'jpg');
                $dest = $uploadDir . '/' . $filename;
                if (@move_uploaded_file($tmp, $dest)) {
                    // 웹에서 접근 가능한 상대 경로로 저장
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
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st = $pdo->prepare('INSERT INTO managers (name, ssn, phone, address1, address2, account_number, bank, specialty, photo, gender, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $st->execute([
                $name,
                $ssn,
                $phone,
                $address1,
                $address2 === '' ? null : $address2,
                $accountNumber,
                $bank === '' ? null : $bank,
                $specialty === '' ? null : $specialty,
                $photoPath === null ? null : $photoPath,
                $gender === '' ? null : $gender,
                $hash
            ]);
            
            // 자동 로그인
            init_session();
            $managerId = $pdo->lastInsertId();
            $_SESSION['manager_id'] = $managerId;
            $_SESSION['manager_name'] = $name;
            $_SESSION['manager_phone'] = $phone;
            if ($photoPath !== null) {
                $_SESSION['manager_photo'] = $photoPath;
            }
            if (!empty($gender)) {
                $_SESSION['manager_gender'] = $gender;
            }
            if (!empty($bank)) {
                $_SESSION['manager_bank'] = $bank;
            }
            
            redirect('/manager/dashboard');
        }
    }
}

$pageTitle = '매니저 회원가입 - ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="매니저 회원가입 - Hangbok77">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/tailwind.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/custom.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Noto Sans KR', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: { DEFAULT: '#2563eb' },
                    },
                },
            },
        };
    </script>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-2xl mx-auto">
            <!-- 헤더 -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-primary"><?= APP_NAME ?></h1>
                <p class="mt-2 text-gray-600">매니저 회원가입</p>
            </div>

            <!-- 회원가입 폼 -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
                <?php if ($error): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="post" action="<?= $base ?>/manager/signup" enctype="multipart/form-data" class="space-y-4">
                    <!-- 이름 -->
                    <div class="flex items-start gap-4">
                        <div class="flex-1">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">이름 <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                                placeholder="홍길동"
                                required>
                        </div>

                        <div class="w-40">
                            <label class="block text-sm font-medium text-gray-700 mb-1">성별</label>
                            <div class="flex items-center gap-3">
                                <label class="inline-flex items-center text-sm">
                                    <input type="radio" name="gender" value="M" <?= (isset($_POST['gender']) && $_POST['gender'] === 'M') ? 'checked' : '' ?> class="h-4 w-4 text-primary border-gray-300">
                                    <span class="ml-2">남</span>
                                </label>
                                <label class="inline-flex items-center text-sm">
                                    <input type="radio" name="gender" value="F" <?= (isset($_POST['gender']) && $_POST['gender'] === 'F') ? 'checked' : '' ?> class="h-4 w-4 text-primary border-gray-300">
                                    <span class="ml-2">여</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 사진 업로드 (이름 아래) -->
                    <div>
                        <label for="photo" class="block text-sm font-medium text-gray-700 mb-1">사진 업로드</label>
                        <input type="file" id="photo" name="photo" accept="image/*" class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-primary file:text-white" />
                    </div>

                    <!-- 주민번호 -->
                    <div>
                        <label for="ssn" class="block text-sm font-medium text-gray-700 mb-1">주민번호 <span class="text-red-500">*</span></label>
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
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">전화번호 <span class="text-red-500">*</span></label>
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
                        <label for="address1" class="block text-sm font-medium text-gray-700 mb-1">주소 <span class="text-red-500">*</span></label>
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
                        <label for="address2" class="block text-sm font-medium text-gray-700 mb-1">상세주소</label>
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
                        <label for="bank" class="block text-sm font-medium text-gray-700 mb-1">은행 <span class="text-red-500">*</span></label>
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
                        <label for="account_number" class="block text-sm font-medium text-gray-700 mb-1">계좌번호 <span class="text-red-500">*</span></label>
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
                        <label for="specialty" class="block text-sm font-medium text-gray-700 mb-1">특기</label>
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
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">비밀번호 <span class="text-red-500">*</span></label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                            placeholder="8자 이상"
                            required
                            autocomplete="new-password">
                    </div>

                    <!-- 비밀번호 확인 -->
                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-1">비밀번호 확인 <span class="text-red-500">*</span></label>
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            class="min-h-[44px] block w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" 
                            placeholder="비밀번호 재입력"
                            required
                            autocomplete="new-password">
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
                            <label for="terms" class="ml-2 text-sm text-gray-700">
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
                            <label for="privacy" class="ml-2 text-sm text-gray-700">
                                <a href="#" class="text-primary hover:underline">개인정보 이용</a>에 동의합니다. <span class="text-red-500">*</span>
                            </label>
                        </div>
                    </div>

                    <!-- 제출 버튼 -->
                    <button 
                        type="submit" 
                        class="min-h-[44px] w-full bg-primary text-white rounded-lg font-medium hover:opacity-90 transition-opacity">
                        회원가입
                    </button>
                </form>
            </div>

            <!-- 하단 링크 -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    이미 계정이 있으신가요? 
                    <a href="<?= $base ?>/manager/login" class="text-primary hover:underline font-medium">로그인</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
