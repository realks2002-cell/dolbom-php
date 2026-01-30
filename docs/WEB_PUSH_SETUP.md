# Pure Web Push 알림 설정 가이드

## ✅ 완료된 작업

Firebase 없이 **Pure Web Push API**를 사용하여 푸시 알림을 구현했습니다.

### 구현된 파일
- ✅ `includes/webpush.php` - Web Push 핵심 라이브러리
- ✅ `includes/fcm.php` - 업데이트된 헬퍼 함수
- ✅ `config/app.php` - VAPID 키 설정
- ✅ `pages/manager/dashboard.php` - 클라이언트 구독 코드
- ✅ `assets/js/service-worker.js` - 푸시 수신 핸들러

## 🔧 설정 방법

### 1. VAPID 키 확인/생성

현재 테스트용 VAPID 키가 설정되어 있습니다. 프로덕션에서는 새로운 키를 생성하세요.

**브라우저에서 키 생성:**
```
scripts/simple_vapid_gen.html 파일을 브라우저로 열기
```

**생성된 키를 `config/app.php`에 설정:**
```php
define('VAPID_PUBLIC_KEY', '생성된_공개_키');
define('VAPID_PRIVATE_KEY', '생성된_비공개_키');
define('VAPID_SUBJECT', 'mailto:admin@yourdomain.com');
```

### 2. 테스트

1. **XAMPP 시작**
   ```
   Apache 실행
   MySQL 실행
   ```

2. **대시보드 접속**
   ```
   http://localhost/manager/dashboard
   ```

3. **푸시 알림 권한 확인**
   - 브라우저 개발자 도구 > Console 확인
   - "Web Push 구독 성공" 메시지 확인

4. **테스트 알림 전송**
   - 고객이 서비스 요청하면 자동으로 매니저들에게 알림 전송
   - 또는 `test/push-notification` 페이지에서 수동 테스트

## 📊 작동 방식

```
브라우저 → Web Push API → 푸시 서비스 (Chrome/Firefox) 
                              ↓
서버 (PHP) ← 데이터베이스 ← 구독 정보
    ↓
VAPID 인증 → 푸시 서비스 → 브라우저 알림
```

### 핵심 컴포넌트

1. **클라이언트 (dashboard.php)**
   - Service Worker 등록
   - 푸시 구독 (PushManager API)
   - VAPID 공개 키 사용
   - 구독 정보를 서버에 전송

2. **서버 (webpush.php)**
   - 구독 정보 저장
   - 페이로드 암호화 (aes128gcm)
   - VAPID JWT 생성
   - 푸시 서비스에 HTTP 요청

3. **Service Worker (service-worker.js)**
   - 푸시 이벤트 수신
   - 알림 표시
   - 클릭 이벤트 처리

## 🔐 보안

- ✅ VAPID 비공개 키는 서버에만 저장
- ✅ 공개 키만 클라이언트에 노출
- ✅ HTTPS 필수 (프로덕션)
- ✅ 페이로드 암호화 (aes128gcm)

## 🌐 브라우저 지원

| 브라우저 | 지원 여부 |
|---------|----------|
| Chrome 42+ | ✅ |
| Firefox 44+ | ✅ |
| Edge 17+ | ✅ |
| Safari 16+ | ✅ |
| Opera 29+ | ✅ |

## 🎯 Firebase와의 차이점

| 항목 | Firebase FCM | Pure Web Push |
|------|--------------|---------------|
| 설정 복잡도 | 높음 | 낮음 |
| 외부 의존성 | Firebase/Google | 없음 |
| API 활성화 | 필요 | 불필요 |
| 서버 키 | 필요 | 불필요 (VAPID만) |
| 비용 | 무료 | 무료 |
| 작동 방식 | FCM 서버 경유 | 브라우저 직접 |

## 🐛 문제 해결

### 푸시 구독 실패

**증상:** "Web Push 구독 실패: NotAllowedError"

**해결:**
1. VAPID 공개 키 확인
2. HTTPS 사용 확인 (또는 localhost)
3. 브라우저 알림 권한 확인
4. Service Worker 등록 확인

### 알림이 표시되지 않음

**증상:** 서버에서 전송 성공했지만 알림 안 뜸

**해결:**
1. Service Worker 활성화 확인
2. 브라우저 알림 권한 확인
3. 운영체제 알림 설정 확인
4. 개발자 도구 Console 에러 확인

### 암호화 오류

**증상:** "Encryption failed"

**해결:**
1. PHP OpenSSL 확장 확인
2. `php -m | grep openssl`
3. EC 암호화 지원 확인

## 📝 API 사용 예제

### PHP에서 알림 전송

```php
require_once 'includes/fcm.php';

$pdo = require 'database/connect.php';

$title = '새로운 서비스 요청';
$body = '고객님이 서비스를 요청했습니다.';
$data = [
    'request_id' => 123,
    'type' => 'new_request'
];

$result = send_push_to_managers($pdo, $title, $body, $data);

if ($result['success']) {
    echo "전송 성공: {$result['success_count']}건\n";
} else {
    echo "전송 실패: {$result['error']}\n";
}
```

### JavaScript에서 구독 확인

```javascript
navigator.serviceWorker.ready.then(function(registration) {
    return registration.pushManager.getSubscription();
}).then(function(subscription) {
    if (subscription) {
        console.log('구독 중:', subscription.endpoint);
    } else {
        console.log('구독 안 됨');
    }
});
```

## 🚀 프로덕션 배포

### 1. VAPID 키 재생성
```
새로운 VAPID 키 쌍 생성 (scripts/simple_vapid_gen.html)
```

### 2. 환경 변수 설정
```env
VAPID_PUBLIC_KEY=실제_공개_키
VAPID_PRIVATE_KEY=실제_비공개_키
VAPID_SUBJECT=mailto:admin@travel23.mycafe24.com
```

### 3. HTTPS 설정
```
SSL 인증서 설치 (Let's Encrypt 권장)
```

### 4. Service Worker 경로 확인
```
/assets/js/service-worker.js 접근 가능 확인
```

## 📞 지원

문제가 발생하면:
1. `docs/PUSH_NOTIFICATION_TEST.md` 참고
2. 브라우저 개발자 도구 Console 확인
3. PHP 에러 로그 확인 (`C:\xampp\php\logs\php_error_log`)

---

**마지막 업데이트:** 2026-01-30  
**버전:** Pure Web Push 1.0
