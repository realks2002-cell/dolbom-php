<?php
/**
 * VAPID 키 쌍 생성 스크립트 (CLI 버전)
 */

// OpenSSL로 VAPID 키 생성
function generateVapidKeys() {
    // EC prime256v1 (P-256) 키 생성
    $config = [
        "private_key_type" => OPENSSL_KEYTYPE_EC,
        "curve_name" => "prime256v1",
    ];
    
    $res = openssl_pkey_new($config);
    if (!$res) {
        return ['error' => 'Failed to generate key: ' . openssl_error_string()];
    }
    
    // 비공개 키 추출
    $success = openssl_pkey_export($res, $privateKeyPem);
    if (!$success) {
        return ['error' => 'Failed to export private key: ' . openssl_error_string()];
    }
    
    // 공개 키 추출
    $keyDetails = openssl_pkey_get_details($res);
    if (!$keyDetails) {
        return ['error' => 'Failed to get key details: ' . openssl_error_string()];
    }
    
    $publicKeyPem = $keyDetails['key'];
    
    // PEM에서 실제 키 데이터 추출
    $privateKeyDer = extractKeyFromPem($privateKeyPem);
    $publicKeyDer = extractPublicKeyFromPem($publicKeyPem);
    
    // Base64 URL-safe 인코딩
    $privateKeyBase64 = base64UrlEncode($privateKeyDer);
    $publicKeyBase64 = base64UrlEncode($publicKeyDer);
    
    return [
        'publicKey' => $publicKeyBase64,
        'privateKey' => $privateKeyBase64,
    ];
}

// PEM에서 키 데이터 추출
function extractKeyFromPem($pem) {
    $lines = explode("\n", $pem);
    $data = '';
    foreach ($lines as $line) {
        if (strpos($line, '-----') === false) {
            $data .= $line;
        }
    }
    return base64_decode($data);
}

// 공개 키 PEM에서 실제 EC 포인트 추출 (65바이트)
function extractPublicKeyFromPem($pem) {
    $der = extractKeyFromPem($pem);
    // EC 공개 키는 DER 인코딩의 마지막 65바이트
    return substr($der, -65);
}

// Base64 URL-safe 인코딩
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// 키 생성 및 출력
$keys = generateVapidKeys();

if (isset($keys['error'])) {
    echo "ERROR: " . $keys['error'] . "\n";
    exit(1);
}

echo "PUBLIC_KEY=" . $keys['publicKey'] . "\n";
echo "PRIVATE_KEY=" . $keys['privateKey'] . "\n";
