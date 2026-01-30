# 카페24 업로드 파일 목록

## 📦 Pure Web Push 알림 시스템 업로드 파일

### 🔴 필수 파일 (반드시 업로드)

#### 1. 핵심 라이브러리
```
includes/
  ├── webpush.php          ⭐ Pure Web Push 핵심 라이브러리 (NEW)
  └── fcm.php              ⭐ 업데이트된 헬퍼 함수 (UPDATED)
```

#### 2. 설정 파일
```
config/
  └── app.php              ⭐ VAPID 키 설정 포함 (UPDATED)
```

#### 3. 매니저 대시보드
```
pages/manager/
  └── dashboard.php        ⭐ 클라이언트 구독 코드 포함 (UPDATED)
```

#### 4. Service Worker
```
assets/js/
  └── service-worker.js    ⭐ 푸시 수신 핸들러 (EXISTING)
```

#### 5. API 엔드포인트
```
api/manager/
  └── register-token.php   ⭐ 구독 등록 API (UPDATED)
```

#### 6. 아이콘 파일
```
assets/icons/
  ├── icon-192x192.png     ⭐ PWA 아이콘 192x192
  └── icon-512x512.png     ⭐ PWA 아이콘 512x512
```

### 🟡 권장 파일 (테스트용)

#### 7. 테스트 페이지
```
test/
  └── test-webpush.php     💡 테스트 페이지 (NEW)
```

#### 8. VAPID 키 생성 도구
```
scripts/
  └── simple_vapid_gen.html 💡 VAPID 키 생성 도구 (NEW)
```

### 🟢 문서 파일 (선택사항)

```
docs/
  ├── WEB_PUSH_SETUP.md           📚 상세 설정 가이드
  ├── CAFE24_WEBPUSH_SETUP.md     📚 카페24 전용 가이드
  └── PUSH_NOTIFICATION_TEST.md   📚 테스트 가이드

README_WEBPUSH.md                 📚 사용 설명서
```

### 📋 기존 프로젝트 파일 (이미 있어야 함)

```
/
├── index.php                # 라우팅 진입점
├── .htaccess                # URL 리라이팅
├── database/
│   └── connect.php          # DB 연결
├── pages/
│   └── manager/
│       └── login.php        # 매니저 로그인
└── api/
    └── middleware/
        └── auth.php         # 인증 미들웨어
```

---

## 🚀 FTP 업로드 순서

### 1단계: 핵심 파일 업로드

```bash
/includes/webpush.php          → /home/hosting계정/www/includes/
/includes/fcm.php              → /home/hosting계정/www/includes/
/config/app.php                → /home/hosting계정/www/config/
```

### 2단계: 프론트엔드 파일 업로드

```bash
/pages/manager/dashboard.php   → /home/hosting계정/www/pages/manager/
/assets/js/service-worker.js   → /home/hosting계정/www/assets/js/
/assets/icons/*                → /home/hosting계정/www/assets/icons/
```

### 3단계: API 파일 업로드

```bash
/api/manager/register-token.php → /home/hosting계정/www/api/manager/
```

### 4단계: 테스트 파일 업로드 (선택)

```bash
/test/test-webpush.php         → /home/hosting계정/www/test/
/scripts/simple_vapid_gen.html → /home/hosting계정/www/scripts/
```

---

## 📝 업로드 전 체크리스트

### ✅ 로컬에서 확인

- [ ] `config/app.php`에 VAPID 키 설정 확인
- [ ] `database/connect.php`에 카페24 DB 정보 확인
- [ ] `.htaccess` 파일 확인
- [ ] 아이콘 파일 존재 확인

### ✅ 카페24 준비사항

- [ ] FTP 계정 정보 확인
- [ ] MySQL 데이터베이스 생성
- [ ] HTTPS (SSL) 인증서 설정
- [ ] PHP 버전 8.0+ 확인

---

## 🛠️ FTP 클라이언트 설정

### FileZilla 설정 예시

```
호스트: ftp.cafe24.com
포트: 21
프로토콜: FTP - 파일 전송 프로토콜
암호화: 명시적 FTP over TLS 필요 시
로그온 유형: 일반
사용자: hosting계정명
비밀번호: FTP 비밀번호
```

### 업로드 경로

```
로컬: c:\xampp\htdocs\dolbom_php\
원격: /home/hosting계정/www/
```

---

## 📂 전체 파일 구조 (업로드 후)

```
/home/hosting계정/www/
├── index.php
├── .htaccess
├── config/
│   └── app.php                    ⭐ VAPID 키 설정
├── includes/
│   ├── webpush.php                ⭐ NEW
│   ├── fcm.php                    ⭐ UPDATED
│   ├── helpers.php
│   └── auth.php
├── pages/
│   └── manager/
│       ├── dashboard.php          ⭐ UPDATED
│       └── login.php
├── api/
│   └── manager/
│       ├── register-token.php     ⭐ UPDATED
│       └── me.php
├── assets/
│   ├── js/
│   │   └── service-worker.js      ⭐ EXISTING
│   ├── icons/
│   │   ├── icon-192x192.png       ⭐ NEW
│   │   └── icon-512x512.png       ⭐ NEW
│   └── manifest.json
├── test/
│   └── test-webpush.php           💡 NEW (테스트용)
├── scripts/
│   └── simple_vapid_gen.html      💡 NEW (키 생성용)
└── database/
    └── connect.php
```

---

## ⚙️ 업로드 후 설정

### 1. VAPID 키 생성 및 설정

브라우저에서 접속:
```
https://travel23.mycafe24.com/scripts/simple_vapid_gen.html
```

생성된 키를 `config/app.php`에 설정:
```php
define('VAPID_PUBLIC_KEY', '생성된_공개_키');
define('VAPID_PRIVATE_KEY', '생성된_비공개_키');
define('VAPID_SUBJECT', 'mailto:admin@travel23.mycafe24.com');
```

### 2. 파일 권한 설정

FTP 클라이언트에서 권한 변경:
```
config/app.php          → 644 또는 600
includes/*.php          → 644
pages/**/*.php          → 644
api/**/*.php            → 644
assets/js/*.js          → 644
```

### 3. 데이터베이스 테이블 생성

phpMyAdmin 접속 후 실행:
```sql
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

---

## 🧪 업로드 확인

### 1. 파일 접근 테스트

브라우저에서 확인:
```
https://travel23.mycafe24.com/assets/js/service-worker.js     ← 200 OK
https://travel23.mycafe24.com/assets/icons/icon-192x192.png   ← 이미지 표시
https://travel23.mycafe24.com/scripts/simple_vapid_gen.html   ← 페이지 표시
```

### 2. API 테스트

```
https://travel23.mycafe24.com/api/manager/me
→ 로그인 필요 또는 데이터 반환
```

### 3. 대시보드 접속

```
https://travel23.mycafe24.com/manager/dashboard
→ 매니저 대시보드 표시
→ Service Worker 등록 확인
→ 푸시 구독 확인
```

### 4. 테스트 페이지

```
https://travel23.mycafe24.com/test/test-webpush.php
→ VAPID 키 상태 확인
→ 활성 구독 목록 확인
→ 테스트 알림 전송
```

---

## 🚨 주의사항

### ⚠️ 보안

1. **VAPID 비공개 키 노출 금지**
   - `config/app.php` 파일 권한 600 또는 644
   - Git에 커밋하지 마세요
   - 환경 변수 사용 권장

2. **민감한 파일 보호**
   ```apache
   # .htaccess에 추가
   <FilesMatch "(\.env|\.log|config\.php)$">
       Order allow,deny
       Deny from all
   </FilesMatch>
   ```

3. **데이터베이스 비밀번호**
   - 강력한 비밀번호 사용
   - 정기적으로 변경

### ⚠️ 성능

1. **캐싱 설정**
   - Service Worker 캐싱 활용
   - .htaccess 브라우저 캐싱 설정

2. **Gzip 압축**
   ```apache
   <IfModule mod_deflate.c>
       AddOutputFilterByType DEFLATE text/html text/css application/javascript
   </IfModule>
   ```

### ⚠️ 디버깅

업로드 후 문제 발생 시:

1. PHP 에러 로그 확인:
   ```
   /home/hosting계정/www/logs/error_log
   ```

2. 브라우저 Console 확인:
   ```
   F12 → Console
   ```

3. Service Worker 상태 확인:
   ```
   F12 → Application → Service Workers
   ```

---

## 📋 빠른 업로드 체크리스트

```
□ includes/webpush.php
□ includes/fcm.php
□ config/app.php (VAPID 키 설정)
□ pages/manager/dashboard.php
□ assets/js/service-worker.js
□ assets/icons/icon-192x192.png
□ assets/icons/icon-512x512.png
□ api/manager/register-token.php
□ test/test-webpush.php (테스트용)
□ scripts/simple_vapid_gen.html (키 생성용)
□ .htaccess
□ index.php
□ database/connect.php
```

---

## 🎯 업로드 완료 후 즉시 테스트

```bash
# 1. VAPID 키 생성
https://travel23.mycafe24.com/scripts/simple_vapid_gen.html

# 2. 대시보드 접속 (로그인 후)
https://travel23.mycafe24.com/manager/dashboard

# 3. 알림 권한 허용
브라우저에서 "허용" 클릭

# 4. 테스트 알림 전송
https://travel23.mycafe24.com/test/test-webpush.php
```

---

**작성일:** 2026-01-30  
**프로젝트:** Hangbok77 Pure Web Push  
**환경:** 카페24 웹호스팅
