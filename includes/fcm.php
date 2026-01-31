<?php
/**
 * Web Push 알림 헬퍼 함수
 * minishlink/web-push 라이브러리 사용
 */

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config/app.php';
}

// 라이브러리 기반 구현 로드
$libPath = dirname(__FILE__) . '/webpush_lib.php';
$vendorPath = dirname(__DIR__) . '/vendor/autoload.php';

// vendor가 있으면 라이브러리 사용, 없으면 기존 코드 사용 (fallback)
if (file_exists($vendorPath)) {
    require_once $libPath;
    define('USE_WEBPUSH_LIB', true);
} else {
    // 기존 코드 (fallback)
    require_once dirname(__FILE__) . '/webpush.php';
    require_once dirname(__FILE__) . '/webpush_simple.php';
    define('USE_WEBPUSH_LIB', false);
}

/**
 * Web Push 알림 전송
 * @param string|array $subscriptions 구독 정보
 * @param string $title 알림 제목
 * @param string $body 알림 내용
 * @param array $data 추가 데이터
 * @return array 결과
 */
function send_web_push($subscriptions, string $title, string $body, array $data = []): array {
    // VAPID 키 확인
    if (!defined('VAPID_PUBLIC_KEY') || !defined('VAPID_PRIVATE_KEY')) {
        error_log('VAPID 키가 설정되지 않았습니다.');
        return ['success' => false, 'error' => 'VAPID 키가 설정되지 않았습니다.'];
    }

    if (USE_WEBPUSH_LIB) {
        // 라이브러리 사용
        if (is_array($subscriptions) && isset($subscriptions['endpoint'])) {
            // 단일 구독
            return send_web_push_lib($subscriptions, $title, $body, $data);
        } elseif (is_array($subscriptions)) {
            // 복수 구독
            return send_web_push_batch($subscriptions, $title, $body, $data);
        } else {
            // 문자열 (JSON)
            return send_web_push_lib($subscriptions, $title, $body, $data);
        }
    } else {
        // 기존 코드 (fallback)
        $subscriptionArray = is_array($subscriptions) ? $subscriptions : [$subscriptions];
        $subscriptionArray = array_filter($subscriptionArray, fn($sub) => !empty($sub));

        if (empty($subscriptionArray)) {
            return ['success' => false, 'error' => '유효한 구독이 없습니다.'];
        }

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        $payload = [
            'title' => $title,
            'body' => $body,
            'icon' => '/assets/icons/icon-192x192.png',
            'badge' => '/assets/icons/icon-192x192.png',
            'data' => array_merge(['timestamp' => date('Y-m-d H:i:s')], $data)
        ];

        foreach ($subscriptionArray as $subscription) {
            $result = send_web_push_notification(
                $subscription,
                $payload,
                VAPID_PUBLIC_KEY,
                VAPID_PRIVATE_KEY,
                defined('VAPID_SUBJECT') ? VAPID_SUBJECT : 'mailto:admin@localhost'
            );

            $results[] = $result;
            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        return [
            'success' => $successCount > 0,
            'results' => $results,
            'success_count' => $successCount,
            'failure_count' => $failureCount
        ];
    }
}

/**
 * 매니저들에게 Web Push 알림 전송
 * @param PDO $pdo DB 연결
 * @param string $title 알림 제목
 * @param string $body 알림 내용
 * @param array $data 추가 데이터
 * @param array $managerIds 특정 매니저 ID (빈 배열이면 전체)
 * @return array 결과
 */
function send_push_to_managers(PDO $pdo, string $title, string $body, array $data = [], array $managerIds = []): array {
    if (USE_WEBPUSH_LIB) {
        return send_push_to_managers_lib($pdo, $title, $body, $data, $managerIds);
    } else {
        // 기존 fallback
        return send_web_push_to_managers_simple($pdo, $title, $body, $data, $managerIds);
    }
}

// FCM 호환성 별칭
function send_fcm_push($subscriptions, string $title, string $body, array $data = []): array {
    return send_web_push($subscriptions, $title, $body, $data);
}
