<?php
/**
 * 매니저 디바이스 토큰 등록 API
 * POST /api/manager/register-token
 * 세션 기반 인증 또는 Bearer 토큰 인증 지원
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/api/cors.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

init_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST 메서드만 지원합니다.']);
    exit;
}

// 인증 확인: 세션 또는 Bearer 토큰
$managerId = null;

// 1. 세션 기반 인증 확인 (대시보드에서 호출 시)
if (!empty($_SESSION['manager_id'])) {
    $managerId = $_SESSION['manager_id'];
} else {
    // 2. Bearer 토큰 인증 확인 (API에서 호출 시)
    require_once dirname(__DIR__, 2) . '/api/middleware/auth.php';
    if ($apiUser && isset($apiUser['id'])) {
        $managerId = $apiUser['id'];
    }
}

if (!$managerId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => '인증이 필요합니다.']);
    exit;
}

try {
    $body = json_decode(file_get_contents('php://input'), true);
    
    if (empty($body['device_token'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'device_token이 필요합니다.']);
        exit;
    }
    
    // Web Push의 경우 subscription 전체를 저장
    if (isset($body['subscription'])) {
        // subscription 객체가 있으면 전체를 저장
        $deviceToken = $body['subscription'];
        if (is_array($deviceToken)) {
            $deviceToken = json_encode($deviceToken);
        }
    } else {
        // 없으면 endpoint만 저장 (하위 호환성)
        $deviceToken = trim($body['device_token']);
    }
    
    $platform = $body['platform'] ?? 'android';
    $appVersion = $body['app_version'] ?? null;
    
    // 플랫폼 검증
    if (!in_array($platform, ['android', 'ios', 'web'])) {
        $platform = 'android';
    }
    
    $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
    
    // 기존 토큰 확인 및 업데이트 또는 새로 등록
    $checkStmt = $pdo->prepare('SELECT id FROM manager_device_tokens WHERE manager_id = ? AND device_token = ?');
    $checkStmt->execute([$managerId, $deviceToken]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        // 기존 토큰 업데이트
        $updateStmt = $pdo->prepare('
            UPDATE manager_device_tokens 
            SET platform = ?, app_version = ?, is_active = 1, last_used_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ');
        $updateStmt->execute([$platform, $appVersion, $existing['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => '토큰이 업데이트되었습니다.',
            'token_id' => $existing['id']
        ]);
    } else {
        // 새 토큰 등록
        $insertStmt = $pdo->prepare('
            INSERT INTO manager_device_tokens (manager_id, device_token, platform, app_version, is_active, last_used_at)
            VALUES (?, ?, ?, ?, 1, NOW())
        ');
        $insertStmt->execute([$managerId, $deviceToken, $platform, $appVersion]);
        
        echo json_encode([
            'success' => true,
            'message' => '토큰이 등록되었습니다.',
            'token_id' => $pdo->lastInsertId()
        ]);
    }
} catch (PDOException $e) {
    error_log('토큰 등록 DB 오류: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '토큰 등록 중 오류가 발생했습니다.']);
} catch (Exception $e) {
    error_log('토큰 등록 오류: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '토큰 등록 중 오류가 발생했습니다.']);
}
