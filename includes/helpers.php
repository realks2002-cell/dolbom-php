<?php
/**
 * 공통 헬퍼
 */

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config/app.php';
}

/**
 * 세션 초기화 (저장 경로: storage/sessions — xampp/tmp 권한 이슈 회피)
 */
function init_session(): void {
    $dir = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';
    
    // 디렉토리 생성 시도
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            // 디렉토리 생성 실패 시 에러 로깅
            error_log('[init_session] 세션 디렉토리 생성 실패: ' . $dir);
            
            // 폴백: 기본 세션 경로 사용
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            return;
        }
    }
    
    // 디렉토리 쓰기 권한 확인
    if (!is_writable($dir)) {
        error_log('[init_session] 세션 디렉토리 쓰기 권한 없음: ' . $dir . ' (권한: ' . substr(sprintf('%o', fileperms($dir)), -4) . ')');
        
        // 폴백: 기본 세션 경로 사용
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        return;
    }
    
    // 세션 시작
    if (session_status() === PHP_SESSION_NONE) {
        session_save_path($dir);
        
        // session_start() 실패 시 에러 처리
        if (!@session_start()) {
            error_log('[init_session] 세션 시작 실패 (경로: ' . $dir . ')');
            
            // 폴백: 기본 경로로 재시도
            @session_start();
        }
    }
}

/**
 * UUID v4 생성 (users.id 등)
 */
function uuid4(): string {
    $b = random_bytes(16);
    $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
    $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

/**
 * 경로로 리다이렉트 (BASE_PATH 반영)
 */
function redirect(string $path): never {
    $base = rtrim(BASE_PATH ?? '', '/');
    $url = $base === '' ? $path : $base . $path;
    if (!str_starts_with($url, '/')) {
        $url = '/' . $url;
    }
    header('Location: ' . $url, true, 302);
    exit;
}

/**
 * 관리자 권한 체크 (admins 테이블 또는 users 테이블의 ADMIN 역할 허용)
 */
function require_admin(): void {
    if (!defined('ROOT_PATH')) {
        require_once dirname(__DIR__) . '/config/app.php';
    }
    // auth.php는 호출하는 곳에서 이미 로드되어야 함 (순환 참조 방지)
    
    // admins 테이블에서 세션 확인
    if (!empty($_SESSION['admin_db_id'])) {
        $pdo = require dirname(__DIR__) . '/database/connect.php';
        $st = $pdo->prepare('SELECT id, admin_id FROM admins WHERE id = ?');
        $st->execute([$_SESSION['admin_db_id']]);
        $admin = $st->fetch();
        if ($admin) {
            return; // admins 테이블 인증 성공
        }
    }
    
    // users 테이블의 ADMIN 역할 확인
    global $currentUser, $userRole;
    if ($currentUser && $userRole === ROLE_ADMIN) {
        return; // users 테이블 ADMIN 인증 성공
    }
    
    // 둘 다 실패하면 관리자 로그인 페이지로 리다이렉트
    redirect('/admin.php');
}

/**
 * 이미지를 리사이즈하고 압축하여 1MB 이하로 만드는 함수
 * 
 * @param string $sourcePath 원본 이미지 경로
 * @param string $destPath 저장할 경로
 * @param int $maxWidth 최대 너비 (기본값: 1920)
 * @param int $maxHeight 최대 높이 (기본값: 1920)
 * @param int $maxSizeBytes 최대 파일 크기 (바이트, 기본값: 1048576 = 1MB)
 * @return bool 성공 여부
 */
function resize_and_compress_image(string $sourcePath, string $destPath, int $maxWidth = 1920, int $maxHeight = 1920, int $maxSizeBytes = 1048576): bool {
    // GD 라이브러리 확인
    if (!function_exists('imagecreatefromjpeg') && !function_exists('imagecreatefrompng')) {
        error_log('[resize_and_compress_image] GD 라이브러리가 설치되어 있지 않습니다.');
        return false;
    }
    
    // 이미지 정보 가져오기
    $info = @getimagesize($sourcePath);
    if ($info === false) {
        error_log('[resize_and_compress_image] 이미지 파일이 아닙니다: ' . $sourcePath);
        return false;
    }
    
    $originalWidth = $info[0];
    $originalHeight = $info[1];
    $mimeType = $info['mime'];
    
    // 이미지 타입에 따라 리소스 생성
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = @imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = @imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $sourceImage = @imagecreatefromwebp($sourcePath);
            } else {
                error_log('[resize_and_compress_image] WebP 형식이 지원되지 않습니다.');
                return false;
            }
            break;
        default:
            error_log('[resize_and_compress_image] 지원하지 않는 이미지 형식: ' . $mimeType);
            return false;
    }
    
    if ($sourceImage === false) {
        error_log('[resize_and_compress_image] 이미지 리소스 생성 실패: ' . $sourcePath);
        return false;
    }
    
    // 리사이즈 비율 계산
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight, 1.0);
    $newWidth = (int)($originalWidth * $ratio);
    $newHeight = (int)($originalHeight * $ratio);
    
    // 새 이미지 생성
    $newImage = @imagecreatetruecolor($newWidth, $newHeight);
    if ($newImage === false) {
        error_log('[resize_and_compress_image] 새 이미지 리소스 생성 실패');
        imagedestroy($sourceImage);
        return false;
    }
    
    // PNG/GIF 투명도 처리
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // 이미지 리사이즈
    if (!@imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight)) {
        error_log('[resize_and_compress_image] 이미지 리사이즈 실패');
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        return false;
    }
    
    imagedestroy($sourceImage);
    
    // JPEG 품질을 조정하며 1MB 이하로 압축
    $quality = 85; // 초기 품질
    $minQuality = 50; // 최소 품질
    $step = 5; // 품질 감소 단계
    
    // 항상 JPEG로 저장 (압축률이 좋음)
    $success = false;
    while ($quality >= $minQuality) {
        // 임시 파일에 저장하여 크기 확인
        $tempFile = $destPath . '.tmp';
        if (@imagejpeg($newImage, $tempFile, $quality)) {
            $fileSize = @filesize($tempFile);
            if ($fileSize !== false && $fileSize <= $maxSizeBytes) {
                // 목표 크기 이하이면 최종 파일로 이동
                if (@rename($tempFile, $destPath)) {
                    $success = true;
                    break;
                }
            }
            // 파일이 너무 크면 품질을 낮춰서 재시도
            @unlink($tempFile);
        }
        $quality -= $step;
    }
    
    // 최소 품질로도 1MB를 넘으면 강제로 저장
    if (!$success) {
        if (@imagejpeg($newImage, $destPath, $minQuality)) {
            $success = true;
        } else {
            error_log('[resize_and_compress_image] 이미지 저장 실패: ' . $destPath);
        }
    }
    
    imagedestroy($newImage);
    
    return $success;
}
