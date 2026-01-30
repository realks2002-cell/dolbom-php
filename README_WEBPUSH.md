# 🔔 Pure Web Push 알림 시스템

Firebase 없이 **순수 Web Push API**만 사용하는 푸시 알림 시스템입니다.

## ✨ 특징

- ✅ **Firebase 불필요** - 외부 의존성 없음
- ✅ **Composer 불필요** - 순수 PHP 구현
- ✅ **완전 무료** - 추가 비용 없음
- ✅ **표준 기반** - Web Push Protocol RFC 8030
- ✅ **브라우저 네이티브** - Chrome, Firefox, Edge, Safari 지원

## 🚀 빠른 시작

### 1. XAMPP 시작

```bash
Apache 시작
MySQL 시작
```

### 2. 대시보드 접속

```
http://localhost/manager/dashboard
```

브라우저에서 알림 권한을 허용하면 자동으로 구독됩니다.

### 3. 테스트 알림 전송

```
http://localhost/test/test-webpush.php
```

"모든 매니저에게 전송" 버튼을 클릭하여 테스트합니다.

## 📁 주요 파일

```
includes/
  ├── webpush.php          # Pure Web Push 핵심 라이브러리
  └── fcm.php              # 헬퍼 함수 (업데이트됨)

config/
  └── app.php              # VAPID 키 설정

pages/manager/
  └── dashboard.php        # 클라이언트 구독 코드

assets/js/
  └── service-worker.js    # 푸시 수신 핸들러

test/
  └── test-webpush.php     # 테스트 페이지

scripts/
  └── simple_vapid_gen.html # VAPID 키 생성 도구

docs/
  └── WEB_PUSH_SETUP.md    # 상세 설정 가이드
```

## 🔧 설정

### VAPID 키 (현재 테스트 키 사용 중)

프로덕션 배포 전에 새로운 VAPID 키를 생성하세요:

1. 브라우저에서 열기:
   ```
   scripts/simple_vapid_gen.html
   ```

2. "VAPID 키 생성하기" 버튼 클릭

3. 생성된 키를 `config/app.php`에 설정:
   ```php
   define('VAPID_PUBLIC_KEY', '생성된_공개_키');
   define('VAPID_PRIVATE_KEY', '생성된_비공개_키');
   define('VAPID_SUBJECT', 'mailto:admin@travel23.mycafe24.com');
   ```

## 💻 사용 예제

### PHP에서 알림 전송

```php
require_once 'includes/fcm.php';

$pdo = require 'database/connect.php';

// 모든 매니저에게 전송
$result = send_push_to_managers(
    $pdo,
    '새로운 서비스 요청',
    '고객님이 서비스를 요청했습니다.',
    ['request_id' => 123, 'type' => 'new_request']
);

if ($result['success']) {
    echo "성공: {$result['success_count']}건\n";
} else {
    echo "실패: {$result['error']}\n";
}
```

### 특정 매니저에게만 전송

```php
$result = send_push_to_managers(
    $pdo,
    '매칭 확정',
    '고객님과 매칭되었습니다.',
    ['request_id' => 123],
    [4, 7, 12] // 매니저 ID 배열
);
```

## 🌐 작동 방식

```
[ 브라우저 ]
     ↓ 1. 구독 요청 (VAPID 공개 키)
[ 푸시 서비스 ] (Chrome/Firefox/Edge)
     ↓ 2. 구독 정보 반환
[ 클라이언트 ]
     ↓ 3. 구독 정보 저장
[ 서버 PHP ]
     ↓ 4. 알림 전송 (VAPID 비공개 키로 인증)
[ 푸시 서비스 ]
     ↓ 5. 알림 전달
[ Service Worker ]
     ↓ 6. 알림 표시
[ 사용자 ]
```

## 🔒 보안

- **VAPID 비공개 키**: 절대 클라이언트에 노출하지 마세요
- **VAPID 공개 키**: 클라이언트에서 사용 (안전함)
- **HTTPS**: 프로덕션에서 필수
- **암호화**: 페이로드는 aes128gcm으로 자동 암호화

## 📱 브라우저 지원

| 브라우저 | 버전 | 지원 |
|---------|------|------|
| Chrome | 42+ | ✅ |
| Firefox | 44+ | ✅ |
| Edge | 17+ | ✅ |
| Safari | 16+ | ✅ |
| Opera | 29+ | ✅ |

## 🐛 문제 해결

### "Web Push 구독 실패"

- VAPID 공개 키 확인
- HTTPS 사용 확인 (또는 localhost)
- 브라우저 알림 권한 확인

### "알림이 표시되지 않음"

- Service Worker 활성화 확인
- 브라우저 알림 권한 확인
- 운영체제 알림 설정 확인

### "Encryption failed"

- PHP OpenSSL 확장 확인: `php -m | grep openssl`
- EC 암호화 지원 확인

## 📚 문서

- [상세 설정 가이드](docs/WEB_PUSH_SETUP.md)
- [푸시 알림 테스트](docs/PUSH_NOTIFICATION_TEST.md)

## 🎯 Firebase와 비교

| 항목 | Firebase FCM | Pure Web Push |
|------|--------------|---------------|
| 설정 | 복잡 (콘솔, API 활성화) | 간단 (VAPID만) |
| 의존성 | Firebase SDK | 없음 |
| 서버 키 | 필요 | 불필요 |
| API 제한 | Google 정책 | 없음 |
| 비용 | 무료 | 무료 |
| 속도 | FCM 경유 | 직접 연결 (더 빠름) |

## 🚀 프로덕션 배포

1. **VAPID 키 재생성**
   ```
   scripts/simple_vapid_gen.html 사용
   ```

2. **환경 변수 설정**
   ```env
   VAPID_PUBLIC_KEY=실제_공개_키
   VAPID_PRIVATE_KEY=실제_비공개_키
   VAPID_SUBJECT=mailto:admin@travel23.mycafe24.com
   ```

3. **HTTPS 설정**
   ```
   SSL 인증서 설치 (Let's Encrypt 권장)
   ```

4. **Service Worker 경로 확인**
   ```
   https://travel23.mycafe24.com/assets/js/service-worker.js
   ```

## 📝 라이선스

이 프로젝트는 표준 Web Push API를 사용합니다.

## 👨‍💻 개발자

- **구현**: Pure PHP (RFC 8030 기반)
- **업데이트**: 2026-01-30
- **버전**: 1.0

---

**테스트 페이지**: http://localhost/test/test-webpush.php  
**매니저 대시보드**: http://localhost/manager/dashboard
