<?php
/**
 * 간단한 Web Push 구현 (테스트용)
 * 복잡한 암호화 대신 FCM 엔드포인트를 직접 사용
 */

/**
 * Web Push Protocol로 직접 전송 (표준 방식)
 */
function send_web_push_notification_simple($subscription, $payload, $vapidPublicKey, $vapidPrivateKey, $subject) {
    try {
        if (is_string($subscription)) {
            $subscription = json_decode($subscription, true);
        }
        
        $endpoint = $subscription['endpoint'];
        $payloadData = is_string($payload) ? json_decode($payload, true) : $payload;
        $payloadJson = json_encode($payloadData);
        
        // VAPID Authorization 헤더 생성
        $vapidHeader = generateSimpleVapidHeader($endpoint, $vapidPublicKey, $vapidPrivateKey, $subject);
        
        if (!$vapidHeader) {
            return ['success' => false, 'error' => 'VAPID header generation failed'];
        }
        
        // 단순 전송 (암호화 없이)
        $headers = [
            'Content-Type: application/json',
            'TTL: 86400',
            'Urgency: high',
            'Authorization: ' . $vapidHeader,
        ];
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log('Web Push 전송 실패: ' . $error);
            return ['success' => false, 'error' => $error];
        }
        
        if ($httpCode === 200 || $httpCode === 201) {
            return ['success' => true, 'httpCode' => $httpCode, 'response' => $response];
        }
        
        error_log('Web Push 응답 코드: ' . $httpCode . ', 내용: ' . $response);
        return ['success' => false, 'error' => 'HTTP ' . $httpCode, 'response' => $response];
        
    } catch (Exception $e) {
        error_log('Web Push 전송 예외: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 간단한 VAPID 헤더 생성
 */
function generateSimpleVapidHeader($endpoint, $publicKey, $privateKey, $subject) {
    try {
        $parsedUrl = parse_url($endpoint);
        $audience = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        
        // JWT 페이로드
        $jwtPayload = [
            'aud' => $audience,
            'exp' => time() + 43200, // 12시간
            'sub' => $subject
        ];
        
        // JWT 생성 (간단 버전 - 서명 없이)
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $payload = base64_encode(json_encode($jwtPayload));
        
        // 실제로는 서명이 필요하지만, 일단 테스트
        $jwt = $header . '.' . $payload . '.';
        
        return 'vapid t=' . $jwt . ', k=' . $publicKey;
        
    } catch (Exception $e) {
        error_log('VAPID 헤더 생성 실패: ' . $e->getMessage());
        return false;
    }
}

/**
 * 매니저들에게 간단 방식으로 전송
 */
function send_web_push_to_managers_simple(PDO $pdo, string $title, string $body, array $data = [], array $managerIds = []): array {
    try {
        if (!defined('VAPID_PUBLIC_KEY') || !defined('VAPID_PRIVATE_KEY')) {
            return ['success' => false, 'error' => 'VAPID keys not configured'];
        }
        
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
        $subscriptions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($subscriptions)) {
            return ['success' => false, 'error' => '전송할 구독이 없습니다.'];
        }
        
        $payload = [
            'title' => $title,
            'body' => $body,
            'icon' => '/assets/icons/icon-192x192.png',
            'badge' => '/assets/icons/icon-192x192.png',
            'data' => array_merge(['timestamp' => date('Y-m-d H:i:s')], $data)
        ];
        
        $subject = 'mailto:admin@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($subscriptions as $subscription) {
            $result = send_web_push_notification_simple(
                $subscription,
                $payload,
                VAPID_PUBLIC_KEY,
                VAPID_PRIVATE_KEY,
                $subject
            );
            
            $results[] = $result;
            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
                error_log('Web Push 전송 실패: ' . ($result['error'] ?? 'Unknown'));
                error_log('응답: ' . json_encode($result));
            }
        }
        
        return [
            'success' => $successCount > 0,
            'total' => count($subscriptions),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];
        
    } catch (PDOException $e) {
        error_log('DB 오류: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
