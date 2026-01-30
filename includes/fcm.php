<?php
/**
 * FCM (Firebase Cloud Messaging) 푸시 알림 헬퍼 함수
 */

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config/app.php';
}

/**
 * FCM 푸시 알림 전송
 * @param string|array $tokens FCM 디바이스 토큰 (문자열 또는 배열)
 * @param string $title 알림 제목
 * @param string $body 알림 내용
 * @param array $data 추가 데이터 (선택사항)
 * @return array 성공/실패 결과
 */
function send_fcm_push($tokens, string $title, string $body, array $data = []): array {
    $fcmServerKey = defined('FCM_SERVER_KEY') ? FCM_SERVER_KEY : '';
    
    if (empty($fcmServerKey)) {
        error_log('FCM_SERVER_KEY가 설정되지 않았습니다.');
        return ['success' => false, 'error' => 'FCM 서버 키가 설정되지 않았습니다.'];
    }
    
    // 토큰을 배열로 변환
    $tokenArray = is_array($tokens) ? $tokens : [$tokens];
    $tokenArray = array_filter($tokenArray, function($token) {
        return !empty($token);
    });
    
    if (empty($tokenArray)) {
        return ['success' => false, 'error' => '유효한 토큰이 없습니다.'];
    }
    
    $url = 'https://fcm.googleapis.com/fcm/send';
    $headers = [
        'Authorization: key=' . $fcmServerKey,
        'Content-Type: application/json'
    ];
    
    $results = [];
    
    // 여러 토큰에 대해 배치 전송 (최대 1000개까지)
    $chunks = array_chunk($tokenArray, 1000);
    
    foreach ($chunks as $chunk) {
        $payload = [
            'registration_ids' => $chunk,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => 1
            ],
            'priority' => 'high',
            'data' => array_merge([
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'timestamp' => date('Y-m-d H:i:s')
            ], $data)
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log('FCM 전송 실패: ' . $error);
            $results[] = ['success' => false, 'error' => $error];
        } elseif ($httpCode === 200) {
            $responseData = json_decode($response, true);
            $results[] = [
                'success' => true,
                'response' => $responseData,
                'success_count' => $responseData['success'] ?? 0,
                'failure_count' => $responseData['failure'] ?? 0
            ];
        } else {
            error_log('FCM 전송 실패: HTTP ' . $httpCode . ' - ' . $response);
            $results[] = ['success' => false, 'error' => 'HTTP ' . $httpCode];
        }
    }
    
    return [
        'success' => true,
        'results' => $results,
        'total_sent' => count($tokenArray)
    ];
}

/**
 * 매니저들에게 푸시 알림 전송
 * @param PDO $pdo 데이터베이스 연결
 * @param string $title 알림 제목
 * @param string $body 알림 내용
 * @param array $data 추가 데이터 (선택사항)
 * @param array $managerIds 특정 매니저 ID 배열 (선택사항, 없으면 모든 활성 매니저)
 * @return array 전송 결과
 */
function send_push_to_managers(PDO $pdo, string $title, string $body, array $data = [], array $managerIds = []): array {
    try {
        // 활성화된 디바이스 토큰 조회
        $query = "
            SELECT DISTINCT device_token 
            FROM manager_device_tokens 
            WHERE is_active = 1 AND device_token IS NOT NULL AND device_token != ''
        ";
        $params = [];
        
        if (!empty($managerIds)) {
            $placeholders = implode(',', array_fill(0, count($managerIds), '?'));
            $query .= " AND manager_id IN ($placeholders)";
            $params = $managerIds;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tokens)) {
            return ['success' => false, 'error' => '전송할 토큰이 없습니다.'];
        }
        
        // last_used_at 업데이트
        $updateStmt = $pdo->prepare("
            UPDATE manager_device_tokens 
            SET last_used_at = NOW() 
            WHERE device_token IN (" . implode(',', array_fill(0, count($tokens), '?')) . ")
        ");
        $updateStmt->execute($tokens);
        
        return send_fcm_push($tokens, $title, $body, $data);
    } catch (PDOException $e) {
        error_log('매니저 푸시 전송 DB 오류: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
