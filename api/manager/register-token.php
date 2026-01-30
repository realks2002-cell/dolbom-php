<?php
/**
 * 매니저 디바이스 토큰 등록 API
 * POST /api/manager/register-token
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/api/cors.php';
require_once dirname(__DIR__, 2) . '/api/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST 메서드만 지원합니다.']);
    exit;
}

try {
    $body = json_decode(file_get_contents('php://input'), true);
    
    if (empty($body['device_token'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'device_token이 필요합니다.']);
        exit;
    }
    
    $deviceToken = trim($body['device_token']);
    $platform = $body['platform'] ?? 'android';
    $appVersion = $body['app_version'] ?? null;
    
    // 플랫폼 검증
    if (!in_array($platform, ['android', 'ios', 'web'])) {
        $platform = 'android';
    }
    
    $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
    $managerId = $apiUser['id']; // middleware/auth.php에서 설정된 $apiUser
    
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
