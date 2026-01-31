# 매니저 푸시 알림 테스트 가이드

## 📋 테스트 방법

### 방법 1: 웹 테스트 페이지 사용 (가장 간단)

1. **테스트 페이지 접속**
   ```
   http://localhost:8000/test/push-notification
   ```

2. **DB에서 토큰 확인**
   - phpMyAdmin에서 실행:
   ```sql
   SELECT m.id, m.name, m.phone, mdt.device_token, mdt.platform, mdt.is_active
   FROM managers m
   LEFT JOIN manager_device_tokens mdt ON m.id = mdt.manager_id
   WHERE mdt.is_active = 1;
   ```

3. **토큰 입력 및 테스트**
   - "2. 푸시 알림 전송 테스트" 섹션에서
   - 디바이스 토큰 입력
   - 알림 제목/내용 입력
   - "푸시 알림 전송" 클릭
   - 결과 확인

---

### 방법 2: 실제 결제 플로우 테스트 (실제 시나리오)

1. **매니저 토큰 등록**
   - 매니저 앱에서 로그인 (하이브리드 앱 환경)
   - 또는 직접 DB에 토큰 삽입:
   ```sql
   INSERT INTO manager_device_tokens (manager_id, device_token, platform, is_active)
   VALUES (4, '여기에_FCM_토큰', 'android', 1);
   ```

2. **고객으로 서비스 요청 및 결제**
   - 고객 계정으로 로그인
   - `/requests/new`에서 서비스 요청 작성
   - 결제 완료 (`/payment/success`)
   - **자동으로 매니저들에게 푸시 알림 전송됨**

3. **결과 확인**
   - PHP 에러 로그 확인 (APP_DEBUG가 true인 경우)
   - 매니저 앱에서 알림 수신 확인

---

### 방법 3: cURL로 API 직접 테스트

터미널에서 실행:

```bash
curl -X POST http://localhost:8000/api/test/send-push \
  -H "Content-Type: application/json" \
  -d "{\"device_token\":\"여기에_디바이스_토큰\", \"title\":\"테스트 알림\", \"body\":\"이것은 테스트 메시지입니다.\"}"
```

---

### 방법 4: DB에서 모든 활성 매니저에게 일괄 전송

PHP 스크립트로 테스트:

```php
<?php
require_once 'config/app.php';
require_once 'includes/fcm.php';

$pdo = require 'database/connect.php';

$title = '테스트 알림';
$body = '이것은 테스트 메시지입니다.';
$data = ['type' => 'test'];

$result = send_push_to_managers($pdo, $title, $body, $data);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
```

---

## 🔍 디버깅 방법

### 1. 토큰 확인
```sql
SELECT * FROM manager_device_tokens WHERE is_active = 1;
```

### 2. PHP 에러 로그 확인
- XAMPP: `C:\xampp\php\logs\php_error_log`
- 또는 브라우저에서 `APP_DEBUG = true`일 때 화면에 표시

### 3. FCM 서버 키 확인
- `config/app.php`에서 `FCM_SERVER_KEY` 설정 확인
- Firebase Console에서 서버 키 확인

### 4. 푸시 전송 결과 확인
- 테스트 페이지에서 결과 JSON 확인
- `success`, `results`, `total_sent` 필드 확인

---

## ⚠️ 주의사항

### 실제 FCM 토큰이 필요한 경우

1. **하이브리드 앱 (Capacitor)**
   - Capacitor Push Notifications 플러그인 설치 필요
   - Android/iOS 네이티브 설정 필요
   - 실제 디바이스에서 테스트 필요

2. **웹 브라우저**
   - Firebase SDK 설정 필요
   - Service Worker 등록 필요
   - 브라우저 알림 권한 필요

### 테스트용 가짜 토큰

- FCM 서버는 유효하지 않은 토큰에 대해 에러를 반환합니다
- 실제 토큰 없이는 테스트가 제한적입니다

---

## 🧪 빠른 테스트 체크리스트

- [ ] FCM_SERVER_KEY가 `config/app.php`에 설정되어 있는가?
- [ ] `manager_device_tokens` 테이블이 생성되어 있는가?
- [ ] 매니저 토큰이 DB에 등록되어 있는가?
- [ ] 테스트 페이지(`/test/push-notification`)가 작동하는가?
- [ ] API 엔드포인트(`/api/test/send-push`)가 작동하는가?

---

## 📞 문제 해결

### 푸시가 전송되지 않는 경우

1. **FCM 서버 키 확인**
   ```php
   // config/app.php
   define('FCM_SERVER_KEY', '올바른_서버_키');
   ```

2. **토큰 유효성 확인**
   - DB의 토큰이 실제 FCM 토큰인지 확인
   - 만료된 토큰일 수 있음

3. **에러 로그 확인**
   - PHP 에러 로그에서 FCM API 응답 확인
   - HTTP 상태 코드 확인 (200이어야 함)

4. **네트워크 확인**
   - 서버에서 `https://fcm.googleapis.com` 접근 가능한지 확인

---

**마지막 업데이트**: 2026-01-29
