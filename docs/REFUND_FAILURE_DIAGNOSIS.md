# 서버 환불 실패 원인 및 해결 방법

## 🔍 환불 실패 가능한 원인

### 1. **토스페이먼츠 라이브 키 미설정** (가장 가능성 높음)
```php
// config/hosting.php 확인
define('TOSS_SECRET_KEY', 'test_gsk_...');  // ❌ 테스트 키
define('TOSS_SECRET_KEY', 'live_gsk_...');  // ✅ 라이브 키
```

**문제**:
- 테스트 키로는 실제 결제는 되지만 **환불 API가 작동하지 않음**
- 토스페이먼츠 테스트 환경 제약

**해결**:
1. 토스페이먼츠 개발자센터 로그인
2. 라이브 키 발급 (live_gck_..., live_gsk_...)
3. `config/hosting.php`에 라이브 키 설정

---

### 2. **curl 확장 모듈 비활성화**
```php
// api/bookings/cancel.php에서 curl 사용
$ch = curl_init($url);
```

**확인 방법**:
```php
<?php
// test_curl.php 생성
phpinfo();
// 또는
echo function_exists('curl_init') ? 'curl 사용 가능' : 'curl 사용 불가';
?>
```

**해결**:
- 카페24 관리자 > PHP 설정 > curl 확장 활성화
- 또는 고객센터 문의

---

### 3. **SSL 인증서 문제**
```php
// api/bookings/cancel.php 94-95번 줄
CURLOPT_SSL_VERIFYPEER => true,
CURLOPT_SSL_VERIFYHOST => 2,
```

**문제**:
- 서버의 SSL 인증서가 오래되어 토스페이먼츠 API 접근 불가

**임시 해결** (테스트용):
```php
CURLOPT_SSL_VERIFYPEER => false,  // SSL 검증 비활성화
```

**영구 해결**:
- 서버 SSL 인증서 업데이트
- 카페24 고객센터 문의

---

### 4. **방화벽/아웃바운드 차단**
```
토스페이먼츠 API: https://api.tosspayments.com
```

**문제**:
- 서버에서 외부 API 호출이 차단됨

**확인**:
```php
<?php
// test_api_access.php
$ch = curl_init('https://api.tosspayments.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Error: " . ($error ?: 'None') . "\n";
echo "Response: " . substr($response, 0, 200) . "\n";
?>
```

**해결**:
- 카페24 고객센터에 외부 API 접근 허용 요청
- 방화벽 설정 확인

---

### 5. **payment_key 형식 오류**
```php
// 결제 시 저장된 payment_key 확인
SELECT payment_key FROM payments WHERE status = 'SUCCESS' ORDER BY created_at DESC LIMIT 1;
```

**정상 형식**:
- 테스트: `tgen_20260203105205ptZi6`
- 라이브: `tviva20241231123456ABCDE` (형식 다를 수 있음)

**문제**:
- payment_key가 NULL 또는 잘못된 형식

**해결**:
- `pages/payment/success.php`에서 payment_key 저장 확인
- 결제 승인 응답에서 paymentKey 제대로 받아오는지 확인

---

### 6. **타임아웃 설정**
```php
// api/bookings/cancel.php 96-97번 줄
CURLOPT_TIMEOUT => 30,
CURLOPT_CONNECTTIMEOUT => 10,
```

**문제**:
- 네트워크가 느려서 30초 내에 응답 못 받음

**해결**:
```php
CURLOPT_TIMEOUT => 60,  // 60초로 증가
CURLOPT_CONNECTTIMEOUT => 20,  // 20초로 증가
```

---

### 7. **토스페이먼츠 API 오류 응답**
```php
// 환불 불가 사유 (토스페이먼츠 측)
- 이미 환불된 결제
- 환불 가능 기간 초과
- 부분 취소 불가 결제 수단
- 잘못된 payment_key
```

**확인**:
```php
// api/bookings/cancel.php에 로그 추가
error_log('환불 API 응답: ' . $response);
error_log('HTTP 코드: ' . $httpCode);
```

---

## 🛠️ 진단 스크립트

### 1. 환불 API 테스트 스크립트
```php
<?php
// test_refund_api.php
require_once 'config/app.php';

// 최근 결제 정보 가져오기
$pdo = require 'database/connect.php';
$st = $pdo->query("SELECT payment_key, amount FROM payments WHERE status = 'SUCCESS' ORDER BY created_at DESC LIMIT 1");
$payment = $st->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    die('결제 내역이 없습니다.');
}

echo "Payment Key: " . $payment['payment_key'] . "\n";
echo "Amount: " . $payment['amount'] . "\n\n";

// 환불 API 테스트 (실제 환불하지 않고 API 접근만 테스트)
$url = 'https://api.tosspayments.com/v1/payments/' . urlencode($payment['payment_key']);
$credential = base64_encode(TOSS_SECRET_KEY . ':');

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $credential,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,  // 테스트용
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "cURL Error: " . ($curlError ?: 'None') . "\n";
echo "Response: " . $response . "\n\n";

if ($httpCode === 200) {
    echo "✅ API 접근 성공! 환불 가능합니다.\n";
} else {
    echo "❌ API 접근 실패! 위 응답을 확인하세요.\n";
}
?>
```

### 2. 환경 확인 스크립트
```php
<?php
// check_environment.php
echo "=== 환경 확인 ===\n\n";

// 1. curl 확인
echo "1. curl 확장: ";
echo function_exists('curl_init') ? "✅ 사용 가능\n" : "❌ 사용 불가\n";

// 2. SSL 확인
echo "2. OpenSSL: ";
echo extension_loaded('openssl') ? "✅ 사용 가능\n" : "❌ 사용 불가\n";

// 3. 토스페이먼츠 키 확인
require_once 'config/app.php';
echo "3. TOSS_SECRET_KEY: ";
if (defined('TOSS_SECRET_KEY')) {
    $key = TOSS_SECRET_KEY;
    if (strpos($key, 'test_') === 0) {
        echo "⚠️  테스트 키 사용 중 (환불 불가)\n";
    } else if (strpos($key, 'live_') === 0) {
        echo "✅ 라이브 키 사용 중\n";
    } else {
        echo "❓ 알 수 없는 키 형식\n";
    }
} else {
    echo "❌ 설정 안 됨\n";
}

// 4. 외부 API 접근 테스트
echo "4. 외부 API 접근: ";
$ch = curl_init('https://api.tosspayments.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode > 0) {
    echo "✅ 접근 가능 (HTTP $httpCode)\n";
} else {
    echo "❌ 접근 불가 ($error)\n";
}

echo "\n=== 진단 완료 ===\n";
?>
```

---

## 📝 해결 순서

### 1단계: 환경 확인
```bash
# 서버에 업로드
1. check_environment.php
2. 브라우저에서 접속: https://yourdomain.com/check_environment.php
3. 결과 확인
```

### 2단계: 키 확인 및 변경
```php
// config/hosting.php
define('TOSS_CLIENT_KEY', 'live_gck_xxxxx');  // 라이브 키로 변경
define('TOSS_SECRET_KEY', 'live_gsk_xxxxx');  // 라이브 키로 변경
```

### 3단계: API 테스트
```bash
1. test_refund_api.php 업로드
2. 브라우저에서 접속
3. API 응답 확인
```

### 4단계: 실제 환불 테스트
```bash
1. 소액(1,000원) 결제
2. 즉시 취소
3. 환불 성공 여부 확인
```

---

## 🚨 긴급 해결 방법 (임시)

환불 API가 계속 실패하면, **수동 환불 처리**:

### 방법 1: 토스페이먼츠 관리자 페이지
1. https://developers.tosspayments.com 로그인
2. 결제 내역 > 해당 결제 찾기
3. 수동 환불 처리

### 방법 2: DB 직접 업데이트 (임시)
```sql
-- payments 테이블 수동 업데이트
UPDATE payments 
SET 
    status = 'REFUNDED',
    refund_amount = amount,
    refund_reason = '관리자 수동 환불',
    refunded_at = NOW()
WHERE id = 'payment_id';
```

**주의**: 토스페이먼츠에서도 환불 처리해야 함!

---

## ✅ 최종 체크리스트

- [ ] `hosting.php`에 라이브 키 설정
- [ ] curl 확장 활성화 확인
- [ ] SSL 인증서 확인
- [ ] 외부 API 접근 가능 확인
- [ ] payment_key 정상 저장 확인
- [ ] 소액 결제/환불 테스트
- [ ] 에러 로그 확인

---

## 📞 추가 지원

1. **카페24 고객센터**
   - curl 확장 활성화
   - 외부 API 접근 허용
   - SSL 인증서 업데이트

2. **토스페이먼츠 고객센터**
   - 라이브 키 발급
   - 환불 API 오류 문의
   - 결제/환불 내역 확인

3. **서버 로그 확인**
   ```bash
   # 카페24 로그 위치
   /home/계정명/logs/error_log
   ```
