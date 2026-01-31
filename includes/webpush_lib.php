<?php
/**
 * Web Push 알림 (minishlink/web-push 라이브러리 사용)
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Web Push 알림 전송
 * @param string|array $subscription 구독 정보 (JSON 문자열 또는 배열)
 * @param string $title 알림 제목
 * @param string $body 알림 내용
 * @param array $data 추가 데이터
 * @return array 결과
 */
function send_web_push_lib($subscription, string $title, string $body, array $data = []): array {
    try {
        // VAPID 인증 설정
        $auth = [
            'VAPID' => [
                'subject' => defined('VAPID_SUBJECT') ? VAPID_SUBJECT : 'mailto:admin@example.com',
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ];

        $webPush = new WebPush($auth);

        // 구독 정보 파싱
        if (is_string($subscription)) {
            $subscription = json_decode($subscription, true);
        }

        if (!$subscription || !isset($subscription['endpoint'])) {
            return ['success' => false, 'error' => 'Invalid subscription'];
        }

        // Subscription 객체 생성
        $sub = Subscription::create([
            'endpoint' => $subscription['endpoint'],
            'publicKey' => $subscription['keys']['p256dh'] ?? null,
            'authToken' => $subscription['keys']['auth'] ?? null,
        ]);

        // 페이로드 구성
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => '/assets/icons/icon-192x192.png',
            'badge' => '/assets/icons/icon-192x192.png',
            'data' => array_merge(['timestamp' => date('Y-m-d H:i:s')], $data)
        ]);

        // 푸시 전송
        $webPush->queueNotification($sub, $payload);

        $results = [];
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if ($report->isSuccess()) {
                $results[] = ['success' => true, 'endpoint' => $endpoint];
            } else {
                $results[] = [
                    'success' => false,
                    'endpoint' => $endpoint,
                    'reason' => $report->getReason()
                ];
            }
        }

        $success = !empty($results) && $results[0]['success'];
        return [
            'success' => $success,
            'results' => $results
        ];

    } catch (Exception $e) {
        error_log('Web Push 오류: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 여러 구독에 Web Push 전송
 * @param array $subscriptions 구독 배열
 * @param string $title 제목
 * @param string $body 내용
 * @param array $data 추가 데이터
 * @return array 결과
 */
function send_web_push_batch(array $subscriptions, string $title, string $body, array $data = []): array {
    try {
        $auth = [
            'VAPID' => [
                'subject' => defined('VAPID_SUBJECT') ? VAPID_SUBJECT : 'mailto:admin@example.com',
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ];

        $webPush = new WebPush($auth);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => '/assets/icons/icon-192x192.png',
            'badge' => '/assets/icons/icon-192x192.png',
            'data' => array_merge(['timestamp' => date('Y-m-d H:i:s')], $data)
        ]);

        // 모든 구독을 큐에 추가
        foreach ($subscriptions as $subscription) {
            if (is_string($subscription)) {
                $subscription = json_decode($subscription, true);
            }

            if (!$subscription || !isset($subscription['endpoint'])) {
                continue;
            }

            $sub = Subscription::create([
                'endpoint' => $subscription['endpoint'],
                'publicKey' => $subscription['keys']['p256dh'] ?? null,
                'authToken' => $subscription['keys']['auth'] ?? null,
            ]);

            $webPush->queueNotification($sub, $payload);
        }

        // 일괄 전송
        $successCount = 0;
        $failureCount = 0;
        $results = [];

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if ($report->isSuccess()) {
                $successCount++;
                $results[] = ['success' => true, 'endpoint' => substr($endpoint, 0, 50) . '...'];
            } else {
                $failureCount++;
                $results[] = [
                    'success' => false,
                    'endpoint' => substr($endpoint, 0, 50) . '...',
                    'reason' => $report->getReason()
                ];
                error_log('Web Push 실패: ' . $report->getReason());
            }
        }

        return [
            'success' => $successCount > 0,
            'total' => count($subscriptions),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];

    } catch (Exception $e) {
        error_log('Web Push 배치 오류: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 매니저들에게 Web Push 전송
 * @param PDO $pdo DB 연결
 * @param string $title 제목
 * @param string $body 내용
 * @param array $data 추가 데이터
 * @param array $managerIds 특정 매니저 ID (빈 배열이면 전체)
 * @return array 결과
 */
function send_push_to_managers_lib(PDO $pdo, string $title, string $body, array $data = [], array $managerIds = []): array {
    try {
        // 활성 구독 조회
        $query = "
            SELECT device_token
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

        return send_web_push_batch($subscriptions, $title, $body, $data);

    } catch (PDOException $e) {
        error_log('매니저 푸시 DB 오류: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
