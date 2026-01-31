<?php
/**
 * 모든 활성 매니저에게 푸시 알림 전송 테스트 API
 * POST /api/test/send-push-to-all
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/fcm.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST 메서드만 지원합니다.']);
    exit;
}

try {
    $body = json_decode(file_get_contents('php://input'), true);
    
    $title = $body['title'] ?? '테스트 알림';
    $bodyText = $body['body'] ?? '이것은 테스트 메시지입니다.';
    $data = $body['data'] ?? [];
    
    $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
    
    // 모든 활성 매니저에게 전송
    $result = send_push_to_managers($pdo, $title, $bodyText, $data);
    
    echo json_encode([
        'success' => true,
        'result' => $result,
        'message' => '모든 활성 매니저에게 푸시 알림이 전송되었습니다.'
    ]);
} catch (Exception $e) {
    error_log('푸시 테스트 오류: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
