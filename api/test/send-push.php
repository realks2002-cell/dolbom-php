<?php
/**
 * 푸시 알림 테스트 API
 * POST /api/test/send-push
 * 
 * 사용법:
 * curl -X POST http://localhost:8000/api/test/send-push \
 *   -H "Content-Type: application/json" \
 *   -d '{"device_token":"여기에_디바이스_토큰", "title":"테스트", "body":"테스트 메시지"}'
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
    
    if (empty($body['device_token'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'device_token이 필요합니다.']);
        exit;
    }
    
    $deviceToken = $body['device_token'];
    $title = $body['title'] ?? '테스트 알림';
    $bodyText = $body['body'] ?? '이것은 테스트 메시지입니다.';
    $data = $body['data'] ?? [];
    
    // 단일 토큰으로 푸시 전송
    $result = send_fcm_push($deviceToken, $title, $bodyText, $data);
    
    echo json_encode([
        'success' => true,
        'result' => $result,
        'message' => '푸시 알림이 전송되었습니다.'
    ]);
} catch (Exception $e) {
    error_log('푸시 테스트 오류: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
