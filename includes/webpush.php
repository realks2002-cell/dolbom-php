<?php
/**
 * Pure Web Push 구현 (라이브러리 없이)
 * Web Push Protocol RFC 8030 기반
 */

/**
 * Web Push 알림 전송
 * @param array $subscription 브라우저 구독 정보 (endpoint, keys)
 * @param array $payload 전송할 데이터
 * @param string $vapidPublicKey VAPID 공개 키
 * @param string $vapidPrivateKey VAPID 비공개 키
 * @param string $subject VAPID subject (mailto: 또는 https://)
 * @return array 전송 결과
 */
function send_web_push_notification($subscription, $payload, $vapidPublicKey, $vapidPrivateKey, $subject) {
    try {
        // 구독 정보 파싱
        if (is_string($subscription)) {
            $subscription = json_decode($subscription, true);
        }
        
        if (!isset($subscription['endpoint']) || !isset($subscription['keys'])) {
            return ['success' => false, 'error' => 'Invalid subscription format'];
        }
        
        $endpoint = $subscription['endpoint'];
        $p256dh = $subscription['keys']['p256dh'] ?? null;
        $auth = $subscription['keys']['auth'] ?? null;
        
        if (!$p256dh || !$auth) {
            return ['success' => false, 'error' => 'Missing encryption keys'];
        }
        
        // 페이로드를 JSON으로 인코딩
        $payloadJson = is_string($payload) ? $payload : json_encode($payload);
        
        // 메시지 암호화
        $encrypted = encryptPayload($payloadJson, $p256dh, $auth);
        
        if (!$encrypted) {
            error_log('암호화 실패 - p256dh: ' . substr($p256dh, 0, 20) . '..., auth: ' . substr($auth, 0, 20) . '...');
            return ['success' => false, 'error' => 'Encryption failed - check error log'];
        }
        
        // VAPID 헤더 생성
        $vapidHeaders = generateVapidHeaders($endpoint, $vapidPublicKey, $vapidPrivateKey, $subject);
        
        if (!$vapidHeaders) {
            error_log('VAPID 생성 실패 - endpoint: ' . $endpoint);
            return ['success' => false, 'error' => 'VAPID generation failed - check error log'];
        }
        
        // HTTP 헤더 구성
        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'Content-Length: ' . strlen($encrypted['ciphertext']),
            'TTL: 86400', // 24시간
            'Urgency: high',
            'Authorization: vapid t=' . $vapidHeaders['jwt'] . ', k=' . $vapidPublicKey,
        ];
        
        // cURL로 전송
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encrypted['ciphertext']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log('cURL 오류: ' . $error);
            return ['success' => false, 'error' => $error];
        }
        
        // HTTP 201 Created 또는 200 OK면 성공
        if ($httpCode === 201 || $httpCode === 200) {
            return ['success' => true, 'httpCode' => $httpCode];
        }
        
        error_log('HTTP 오류 ' . $httpCode . ': ' . $response);
        return [
            'success' => false,
            'error' => 'HTTP ' . $httpCode,
            'response' => $response
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 페이로드 암호화 (aes128gcm)
 * @param string $payload 원본 데이터
 * @param string $userPublicKey 사용자 공개 키 (base64)
 * @param string $userAuth 사용자 인증 시크릿 (base64)
 * @return array|false 암호화된 데이터
 */
function encryptPayload($payload, $userPublicKey, $userAuth) {
    try {
        // Base64 디코딩
        $userPublicKeyBinary = base64UrlDecode($userPublicKey);
        $userAuthBinary = base64UrlDecode($userAuth);
        
        // 16바이트 salt 생성
        $salt = random_bytes(16);
        
        // 로컬 키 쌍 생성 (EC prime256v1)
        $localKeyResource = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1'
        ]);
        
        if (!$localKeyResource) {
            return false;
        }
        
        // 공개 키 추출
        $localKeyDetails = openssl_pkey_get_details($localKeyResource);
        $localPublicKey = $localKeyDetails['ec']['x'] . $localKeyDetails['ec']['y'];
        
        // 비공개 키 추출
        openssl_pkey_export($localKeyResource, $localPrivateKeyPem);
        
        // ECDH로 공유 비밀 생성
        $sharedSecret = openssl_pkey_derive($userPublicKeyBinary, $localKeyResource);
        
        if ($sharedSecret === false) {
            return false;
        }
        
        // PRK (Pseudo-Random Key) 생성
        $prk = hash_hkdf('sha256', $sharedSecret, 32, $userAuthBinary . $userPublicKeyBinary . $localPublicKey, 'Content-Encoding: auth' . "\x00");
        
        // CEK (Content Encryption Key) 생성
        $cek = hash_hkdf('sha256', $prk, 16, "Content-Encoding: aes128gcm\x00", $salt);
        
        // Nonce 생성
        $nonce = hash_hkdf('sha256', $prk, 12, "Content-Encoding: nonce\x00", $salt);
        
        // 데이터에 패딩 추가
        $paddedPayload = $payload . "\x02";
        
        // AES-128-GCM 암호화
        $ciphertext = openssl_encrypt(
            $paddedPayload,
            'aes-128-gcm',
            $cek,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );
        
        if ($ciphertext === false) {
            return false;
        }
        
        // 최종 메시지 조립: salt + 공개키 길이 + 공개키 + 암호문 + 태그
        $message = $salt . 
                   pack('N', strlen($localPublicKey)) . 
                   $localPublicKey . 
                   $ciphertext . 
                   $tag;
        
        return [
            'ciphertext' => $message,
            'salt' => base64_encode($salt),
            'publicKey' => base64UrlEncode($localPublicKey)
        ];
        
    } catch (Exception $e) {
        error_log('Encryption error: ' . $e->getMessage());
        return false;
    }
}

/**
 * VAPID JWT 생성
 * @param string $endpoint 푸시 서비스 엔드포인트
 * @param string $vapidPublicKey 공개 키
 * @param string $vapidPrivateKey 비공개 키
 * @param string $subject subject (mailto: or https://)
 * @return array|false JWT 토큰
 */
function generateVapidHeaders($endpoint, $vapidPublicKey, $vapidPrivateKey, $subject) {
    try {
        // 엔드포인트에서 오리진 추출
        $parsedUrl = parse_url($endpoint);
        $audience = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        
        // JWT 헤더
        $header = [
            'typ' => 'JWT',
            'alg' => 'ES256'
        ];
        
        // JWT 페이로드
        $payload = [
            'aud' => $audience,
            'exp' => time() + (12 * 60 * 60), // 12시간
            'sub' => $subject
        ];
        
        // Base64 URL 인코딩
        $headerEncoded = base64UrlEncode(json_encode($header));
        $payloadEncoded = base64UrlEncode(json_encode($payload));
        
        $data = $headerEncoded . '.' . $payloadEncoded;
        
        // 서명 생성 (ES256)
        $privateKeyBinary = base64UrlDecode($vapidPrivateKey);
        
        // PEM 형식으로 변환
        $privateKeyPem = createPrivateKeyPem($privateKeyBinary);
        $privateKeyResource = openssl_pkey_get_private($privateKeyPem);
        
        if (!$privateKeyResource) {
            return false;
        }
        
        // 서명
        $signature = '';
        $success = openssl_sign($data, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
        
        if (!$success) {
            return false;
        }
        
        // JWT 조립
        $jwt = $data . '.' . base64UrlEncode($signature);
        
        return [
            'jwt' => $jwt,
            'publicKey' => $vapidPublicKey
        ];
        
    } catch (Exception $e) {
        error_log('VAPID generation error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Base64 URL-safe 디코딩
 */
function base64UrlDecode($data) {
    $padding = strlen($data) % 4;
    if ($padding) {
        $data .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Base64 URL-safe 인코딩
 */
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * 비공개 키를 PEM 형식으로 변환
 */
function createPrivateKeyPem($keyBinary) {
    $base64 = base64_encode($keyBinary);
    $pem = "-----BEGIN EC PRIVATE KEY-----\n";
    $pem .= chunk_split($base64, 64, "\n");
    $pem .= "-----END EC PRIVATE KEY-----\n";
    return $pem;
}

/**
 * 매니저들에게 Web Push 알림 전송
 * @param PDO $pdo 데이터베이스 연결
 * @param string $title 알림 제목
 * @param string $body 알림 내용
 * @param array $data 추가 데이터
 * @param array $managerIds 특정 매니저 ID 배열
 * @return array 전송 결과
 */
function send_web_push_to_managers(PDO $pdo, string $title, string $body, array $data = [], array $managerIds = []): array {
    try {
        // VAPID 키 확인
        if (!defined('VAPID_PUBLIC_KEY') || !defined('VAPID_PRIVATE_KEY')) {
            return ['success' => false, 'error' => 'VAPID keys not configured'];
        }
        
        // 활성화된 구독 조회
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
        
        // 페이로드 구성
        $payload = [
            'title' => $title,
            'body' => $body,
            'icon' => '/assets/icons/icon-192x192.png',
            'badge' => '/assets/icons/icon-192x192.png',
            'data' => array_merge(['timestamp' => date('Y-m-d H:i:s')], $data)
        ];
        
        $subject = 'mailto:admin@' . $_SERVER['HTTP_HOST'];
        
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        
        // 각 구독에 전송
        foreach ($subscriptions as $subscription) {
            $result = send_web_push_notification(
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
                error_log('Web Push 전송 실패: ' . ($result['error'] ?? 'Unknown error'));
            }
        }
        
        return [
            'success' => true,
            'total' => count($subscriptions),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];
        
    } catch (PDOException $e) {
        error_log('매니저 Web Push 전송 DB 오류: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
