# 카페24 Pure Web Push 설정 가이드

## ✅ 카페24에서 Pure Web Push 사용 가능!

카페24 웹호스팅에서 Pure Web Push는 **완전히 작동**합니다.

### 왜 작동하는가?

- ✅ **PHP만 사용** - 외부 서비스 불필요
- ✅ **표준 Web API** - 브라우저 네이티브 기능
- ✅ **HTTPS 지원** - 카페24 무료 SSL 사용 가능
- ✅ **OpenSSL 지원** - 카페24 PHP에 기본 포함

## 🚀 카페24 배포 단계

### 1. 파일 업로드

FTP로 다음 파일들을 업로드:

```
/
├── config/
│   └── app.php              # VAPID 키 설정
├── includes/
│   ├── webpush.php          # Pure Web Push 라이브러리
│   └── fcm.php              # 헬퍼 함수
├── pages/manager/
│   └── dashboard.php        # 클라이언트 코드
├── assets/js/
│   └── service-worker.js    # Service Worker
├── api/manager/
│   └── register-token.php   # 구독 등록 API
└── .htaccess                # URL 리라이팅
```

### 2. VAPID 키 생성 (프로덕션용)

#### 방법 1: 브라우저에서 생성 (권장)

1. 업로드 후 브라우저에서 접속:
   ```
   https://travel23.mycafe24.com/scripts/simple_vapid_gen.html
   ```

2. "VAPID 키 생성하기" 클릭

3. 생성된 키를 복사

#### 방법 2: 로컬에서 생성 후 복사

1. 로컬 브라우저에서:
   ```
   http://localhost/dolbom_php/scripts/simple_vapid_gen.html
   ```

2. 생성된 키를 복사하여 서버에 적용

### 3. `config/app.php` 수정

FTP로 `config/app.php` 편집:

```php
// VAPID 키 (Pure Web Push용)
define('VAPID_PUBLIC_KEY', '생성된_공개_키_87자');
define('VAPID_PRIVATE_KEY', '생성된_비공개_키_더_긴_문자열');
define('VAPID_SUBJECT', 'mailto:admin@travel23.mycafe24.com');
```

**⚠️ 주의:**
- 비공개 키는 절대 클라이언트에 노출하지 마세요
- 각 환경마다 다른 키를 사용하세요 (개발/프로덕션)

### 4. HTTPS 설정 (필수)

Web Push는 HTTPS에서만 작동합니다 (localhost 제외).

**카페24 무료 SSL 설정:**

1. 카페24 관리자 로그인
2. 나의 서비스 관리 > 쇼핑몰 관리
3. 기본 설정 관리 > 보안(SSL) 인증서 관리
4. 무료 인증서 신청
5. 인증서 적용 후 HTTPS 접속 확인:
   ```
   https://travel23.mycafe24.com
   ```

### 5. PHP 버전 확인

**카페24 관리자에서 확인:**

1. 웹 FTP > PHP 버전 관리
2. **PHP 8.0 이상** 권장 (현재 PHP 8.2 사용 중)
3. **OpenSSL 확장** 활성화 확인 (기본적으로 활성화됨)

### 6. 데이터베이스 설정

`config/app.php`에서 카페24 DB 정보 설정:

```php
define('DB_HOST', 'localhost'); // 카페24는 보통 localhost
define('DB_NAME', 'your_cafe24_db_name');
define('DB_USER', 'your_cafe24_db_user');
define('DB_PASS', 'your_cafe24_db_password');
```

**DB 정보 확인:**
- 카페24 관리자 > 호스팅 관리 > 데이터베이스 관리

### 7. 테이블 생성

phpMyAdmin 또는 DB 관리 도구에서 실행:

```sql
-- manager_device_tokens 테이블이 없으면 생성
CREATE TABLE IF NOT EXISTS manager_device_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    manager_id INT NOT NULL,
    device_token TEXT NOT NULL,
    platform VARCHAR(20) DEFAULT 'web',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
    UNIQUE KEY unique_manager_token (manager_id, device_token(255)),
    FOREIGN KEY (manager_id) REFERENCES managers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 🧪 카페24에서 테스트

### 1. 대시보드 접속

```
https://travel23.mycafe24.com/manager/dashboard
```

- 브라우저 알림 권한 허용
- 개발자 도구 Console 확인:
  - "Service Worker 등록 성공"
  - "Web Push 구독 성공"
  - "푸시 토큰 등록 성공"

### 2. 테스트 페이지 접속

```
https://travel23.mycafe24.com/test/test-webpush.php
```

- 활성 구독 목록 확인
- 테스트 알림 전송
- 브라우저에서 알림 수신 확인

## 🔧 카페24 특화 설정

### .htaccess 최적화

카페24에서 `.htaccess` 확인:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # HTTPS 강제 (카페24 프로덕션)
    RewriteCond %{HTTPS} off
    RewriteCond %{HTTP_HOST} !^localhost [NC]
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # URL 리라이팅
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?route=$1 [L,QSA]
</IfModule>

# Service Worker 캐싱 방지
<FilesMatch "service-worker\.js$">
    Header set Cache-Control "no-store, no-cache, must-revalidate, max-age=0"
    Header set Pragma "no-cache"
</FilesMatch>
```

### PHP 메모리 제한

카페24 기본 설정으로 충분하지만, 필요시 `.htaccess`에 추가:

```apache
<IfModule mod_php.c>
    php_value memory_limit 256M
    php_value max_execution_time 300
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
</IfModule>
```

## 🐛 카페24 문제 해결

### 1. "OpenSSL 오류"

**증상:** Encryption failed, OpenSSL error

**해결:**
```php
// config/app.php 상단에 추가
ini_set('openssl.cafile', '/path/to/cacert.pem');
```

대부분의 경우 카페24는 기본적으로 OpenSSL이 설정되어 있어 불필요합니다.

### 2. "Service Worker 등록 실패"

**증상:** Service Worker registration failed

**해결:**
1. HTTPS 사용 확인
2. Service Worker 경로 확인:
   ```
   https://travel23.mycafe24.com/assets/js/service-worker.js
   ```
3. 파일이 업로드되었는지 확인

### 3. "구독 등록 401 오류"

**증상:** 푸시 토큰 등록 실패 401 Unauthorized

**해결:**
- `api/manager/register-token.php`에서 세션 인증 확인
- 매니저 로그인 상태 확인

### 4. "알림이 표시되지 않음"

**증상:** 서버 전송 성공했지만 알림 안 뜸

**해결:**
1. 브라우저 알림 권한 확인
2. Service Worker 활성화 확인
3. VAPID 공개 키가 올바른지 확인
4. PHP 에러 로그 확인:
   ```
   /home/hosting계정/www/logs/error_log
   ```

## 📊 카페24 성능 최적화

### 1. Service Worker 캐싱

`service-worker.js`에서 카페24 환경에 맞게 캐싱:

```javascript
const CACHE_NAME = 'hangbok77-manager-v1';
const urlsToCache = [
  '/manager/dashboard',
  '/assets/css/custom.css',
  '/assets/icons/icon-192x192.png',
  '/assets/icons/icon-512x512.png'
  // CDN은 제외 (네트워크로 로드)
];
```

### 2. 구독 정보 압축

대량의 구독이 있는 경우 배치 전송 최적화:

```php
// includes/fcm.php 수정 가능
// 한 번에 100개씩 전송
$chunks = array_chunk($subscriptions, 100);
```

## 🔐 카페24 보안 권장사항

### 1. 환경 변수 보호

`.htaccess`에 추가:

```apache
<FilesMatch "(\.env|\.log|\.ini|config\.php)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 2. VAPID 비공개 키 보호

- `config/app.php` 파일 권한: `644` 또는 `600`
- 웹에서 직접 접근 불가능하도록 설정

### 3. API 엔드포인트 보호

`api/manager/register-token.php`에서:
- 세션 인증 필수
- CSRF 토큰 검증 (선택사항)
- Rate limiting (필요시)

## 📱 카페24 모바일 최적화

카페24 호스팅에서 모바일 PWA 최적화:

1. **Gzip 압축 활성화**
   ```apache
   <IfModule mod_deflate.c>
       AddOutputFilterByType DEFLATE text/html text/plain text/css application/javascript
   </IfModule>
   ```

2. **브라우저 캐싱**
   ```apache
   <IfModule mod_expires.c>
       ExpiresActive On
       ExpiresByType image/png "access plus 1 month"
       ExpiresByType application/javascript "access plus 1 week"
   </IfModule>
   ```

## 🎯 카페24 체크리스트

배포 전 확인:

- [ ] VAPID 키 생성 및 설정
- [ ] HTTPS 설정 완료
- [ ] DB 테이블 생성
- [ ] .htaccess 업로드
- [ ] Service Worker 업로드
- [ ] PHP 버전 8.0+ 확인
- [ ] OpenSSL 확장 활성화 확인
- [ ] 매니저 대시보드 접속 테스트
- [ ] 푸시 알림 테스트
- [ ] 에러 로그 확인

## 💡 카페24 장점

Pure Web Push를 카페24에서 사용하는 이유:

1. **추가 비용 없음** - 외부 서비스 불필요
2. **빠른 속도** - 서버 직접 연결
3. **완전한 제어** - 모든 코드가 서버에 있음
4. **제한 없음** - 발송 횟수 제한 없음
5. **간단한 관리** - Firebase 콘솔 불필요

## 📞 지원

문제 발생 시:

1. PHP 에러 로그 확인: `/home/hosting계정/www/logs/error_log`
2. 브라우저 Console 확인
3. `docs/WEB_PUSH_SETUP.md` 참고

---

**배포 URL 예시:**
- 대시보드: https://travel23.mycafe24.com/manager/dashboard
- 테스트: https://travel23.mycafe24.com/test/test-webpush.php
- VAPID 생성: https://travel23.mycafe24.com/scripts/simple_vapid_gen.html

**마지막 업데이트:** 2026-01-30
