<?php
/**
 * 전화번호로 매니저 토큰 등록/조회 테스트 API
 * POST /api/test/register-token-by-phone
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST 메서드만 지원합니다.']);
    exit;
}

try {
    $body = json_decode(file_get_contents('php://input'), true);
    
    if (empty($body['phone'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'phone이 필요합니다.']);
        exit;
    }
    
    $phone = trim($body['phone']);
    $deviceToken = trim($body['device_token'] ?? '');
    $platform = $body['platform'] ?? 'android';
    
    // 전화번호 정규화
    $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
    
    $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
    
    // 전화번호로 매니저 찾기
    $st = $pdo->prepare('SELECT id, name, phone FROM managers');
    $st->execute();
    $managers = $st->fetchAll();
    
    $manager = null;
    foreach ($managers as $m) {
        $dbPhone = preg_replace('/[^0-9]/', '', $m['phone']);
        if ($dbPhone === $normalizedPhone) {
            $manager = $m;
            break;
        }
    }
    
    if (!$manager) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => '해당 전화번호로 등록된 매니저를 찾을 수 없습니다.']);
        exit;
    }
    
    // 토큰이 제공된 경우 등록/업데이트
    if (!empty($deviceToken)) {
        // 기존 토큰 확인
        $checkStmt = $pdo->prepare('SELECT id FROM manager_device_tokens WHERE manager_id = ? AND device_token = ?');
        $checkStmt->execute([$manager['id'], $deviceToken]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            // 업데이트
            $updateStmt = $pdo->prepare('
                UPDATE manager_device_tokens 
                SET platform = ?, is_active = 1, last_used_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ');
            $updateStmt->execute([$platform, $existing['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => '토큰이 업데이트되었습니다.',
                'manager' => [
                    'id' => $manager['id'],
                    'name' => $manager['name'],
                    'phone' => $manager['phone']
                ],
                'token_id' => $existing['id']
            ]);
        } else {
            // 새로 등록
            $insertStmt = $pdo->prepare('
                INSERT INTO manager_device_tokens (manager_id, device_token, platform, is_active, last_used_at)
                VALUES (?, ?, ?, 1, NOW())
            ');
            $insertStmt->execute([$manager['id'], $deviceToken, $platform]);
            
            echo json_encode([
                'success' => true,
                'message' => '토큰이 등록되었습니다.',
                'manager' => [
                    'id' => $manager['id'],
                    'name' => $manager['name'],
                    'phone' => $manager['phone']
                ],
                'token_id' => $pdo->lastInsertId()
            ]);
        }
    } else {
        // 토큰이 없으면 기존 토큰 조회만
        $tokenStmt = $pdo->prepare('SELECT id, device_token, platform, is_active, created_at FROM manager_device_tokens WHERE manager_id = ? ORDER BY created_at DESC');
        $tokenStmt->execute([$manager['id']]);
        $tokens = $tokenStmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'manager' => [
                'id' => $manager['id'],
                'name' => $manager['name'],
                'phone' => $manager['phone']
            ],
            'tokens' => $tokens
        ]);
    }
} catch (PDOException $e) {
    error_log('토큰 등록 DB 오류: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB 오류: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('토큰 등록 오류: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
