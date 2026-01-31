<?php
/**
 * VAPID 키 쌍 생성 스크립트
 * 브라우저에서 실행: http://localhost/scripts/generate_vapid_keys.php
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
        'publicKeyPem' => $publicKeyPem,
        'privateKeyPem' => $privateKeyPem
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
    // 0x04 (1바이트) + X 좌표 (32바이트) + Y 좌표 (32바이트)
    return substr($der, -65);
}

// Base64 URL-safe 인코딩
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VAPID 키 생성</title>
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .key-section {
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .key-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .key-value {
            background: #fff;
            padding: 12px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            word-break: break-all;
            border: 1px solid #ddd;
            color: #333;
        }
        .info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #2196F3;
        }
        .info h3 {
            margin-top: 0;
            color: #1976D2;
        }
        .code {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 VAPID 키 생성</h1>
        
        <?php
        $keys = generateVapidKeys();
        
        if (isset($keys['error'])) {
            echo '<div class="error">';
            echo '<strong>오류:</strong> ' . htmlspecialchars($keys['error']);
            echo '</div>';
        } else {
            ?>
            <div class="key-section">
                <div class="key-label">공개 키 (Public Key)</div>
                <div class="key-value"><?= htmlspecialchars($keys['publicKey']) ?></div>
            </div>
            
            <div class="key-section">
                <div class="key-label">비공개 키 (Private Key)</div>
                <div class="key-value"><?= htmlspecialchars($keys['privateKey']) ?></div>
            </div>
            
            <div class="info">
                <h3>📝 설정 방법</h3>
                <p><strong>1. config/app.php</strong>에 다음 내용을 추가하세요:</p>
                <div class="code">
// VAPID 키 (Web Push 전용)<br>
define('VAPID_PUBLIC_KEY', '<?= $keys['publicKey'] ?>');<br>
define('VAPID_PRIVATE_KEY', '<?= $keys['privateKey'] ?>');
                </div>
                
                <p style="margin-top: 20px;"><strong>2. dashboard.php</strong>의 JavaScript에서:</p>
                <div class="code">
const vapidPublicKey = '<?= $keys['publicKey'] ?>';
                </div>
                
                <p style="margin-top: 20px;"><strong>⚠️ 주의사항:</strong></p>
                <ul>
                    <li>비공개 키는 절대 클라이언트에 노출하지 마세요</li>
                    <li>공개 키만 브라우저에서 사용합니다</li>
                    <li>이 키들을 안전하게 저장하세요</li>
                </ul>
            </div>
            <?php
        }
        ?>
    </div>
</body>
</html>
