# 카페24 웹호스팅 배포 체크리스트

## ✅ 배포 가능 여부: **가능**

---

## 📋 필수 확인 사항

### 1. PHP 버전 요구사항
- **필요 버전**: PHP 7.4 이상 (권장: PHP 8.0+)
- **카페24 지원**: ✓ PHP 7.4, 8.0, 8.1 지원
- **확인 방법**: 카페24 관리자 > 호스팅 관리 > PHP 버전 설정

### 2. 필수 PHP 확장 모듈
```
✓ PDO (MySQL/MariaDB 연결)
✓ curl (토스페이먼츠 API, VWorld API 호출)
✓ json (JSON 처리)
✓ mbstring (한글 처리)
✓ openssl (HTTPS, API 통신)
```
**카페24**: 모두 기본 지원 ✓

### 3. 데이터베이스
- **필요**: MySQL 5.7+ 또는 MariaDB 10.3+
- **카페24**: MariaDB 10.3+ 제공 ✓
- **작업**:
  1. 카페24 관리자에서 DB 생성
  2. `database/schema.sql` 임포트
  3. `hosting.php` 파일에 DB 정보 설정

---

## 🔧 배포 전 필수 작업

### 1. `hosting.php` 파일 생성 (중요!)

**위치**: `config/hosting.php`

```php
<?php
/**
 * 카페24 호스팅 환경 설정
 * 이 파일은 Git에 포함하지 마세요 (.gitignore에 추가)
 */

// 환경 설정
define('APP_DEBUG', false); // 프로덕션: false
define('APP_ENV', 'production');

// 데이터베이스 설정 (카페24 관리자에서 확인)
define('DB_HOST', 'localhost'); // 또는 카페24에서 제공한 DB 호스트
define('DB_NAME', 'your_db_name'); // 생성한 DB 이름
define('DB_USER', 'your_db_user'); // DB 사용자명
define('DB_PASS', 'your_db_password'); // DB 비밀번호
define('DB_CHARSET', 'utf8mb4');

// Base URL (도메인)
define('BASE_URL', 'https://yourdomain.com'); // 실제 도메인으로 변경

// 토스페이먼츠 라이브 키 (테스트 키 → 라이브 키로 변경 필수!)
define('TOSS_CLIENT_KEY', 'live_gck_xxxxx'); // 실제 라이브 키
define('TOSS_SECRET_KEY', 'live_gsk_xxxxx'); // 실제 라이브 키

// API JWT 시크릿 (강력한 키로 변경!)
define('API_JWT_SECRET', 'CHANGE-TO-STRONG-RANDOM-KEY-HERE');

// VAPID 키 (scripts/generate_vapid_keys.php로 생성)
define('VAPID_PUBLIC_KEY', 'your_vapid_public_key');
define('VAPID_PRIVATE_KEY', 'your_vapid_private_key');
define('VAPID_SUBJECT', 'mailto:admin@yourdomain.com');

// Vue.js 매니저 앱 URL
define('VITE_APP_URL', 'https://yourdomain.com/manager-app');
```

### 2. `.gitignore` 업데이트

```
# 호스팅 설정 파일 (민감 정보 포함)
config/hosting.php

# 환경 변수 파일
.env
.env.local
.env.production

# 벤더 폴더 (composer)
vendor/

# 로그 파일
*.log
logs/
```

### 3. 토스페이먼츠 키 변경 (중요!)

**현재 상태**: 테스트 키 사용 중
```php
TOSS_CLIENT_KEY: test_gck_docs_Ovk5rk1EwkEbP0W43n07xlzm
TOSS_SECRET_KEY: test_gsk_docs_OaPz8L5KdmQXkzRz3y47BMw6
```

**변경 필요**:
1. 토스페이먼츠 개발자센터 로그인
2. 라이브 키 발급 (live_gck_..., live_gsk_...)
3. `hosting.php`에 라이브 키 설정

**⚠️ 경고**: 테스트 키로는 실제 결제/환불 불가!

---

## 📁 파일 업로드 방법

### 1. FTP 업로드
```
카페24 FTP 정보:
- 호스트: yourdomain.com
- 포트: 21
- 사용자: FTP 계정
- 비밀번호: FTP 비밀번호

업로드 위치:
/www/ 또는 /public_html/
```

### 2. 업로드할 파일/폴더
```
✓ api/
✓ assets/
✓ components/
✓ config/
✓ database/ (schema.sql 포함)
✓ includes/
✓ pages/
✓ vendor/ (composer install 후)
✓ index.php
✓ router.php
✓ admin.php
✓ .htaccess (URL 리라이팅)
```

### 3. 업로드하지 말 것
```
✗ config/hosting.php (서버에서 직접 생성)
✗ .env
✗ .git/
✗ node_modules/
✗ test/
✗ 개발용 스크립트
```

---

## 🔐 보안 설정

### 1. 파일 권한 설정
```bash
# 디렉토리: 755
chmod 755 api/ assets/ components/ config/ database/ includes/ pages/

# PHP 파일: 644
chmod 644 *.php api/*.php pages/**/*.php

# 쓰기 권한 필요 (로그, 캐시)
chmod 777 logs/ (있는 경우)
```

### 2. `.htaccess` 확인

**위치**: 루트 디렉토리

```apache
# URL 리라이팅
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # 실제 파일/디렉토리가 아니면 index.php로
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>

# 보안 헤더
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# PHP 설정
php_value upload_max_filesize 20M
php_value post_max_size 20M
php_value max_execution_time 300
php_value memory_limit 256M

# 민감한 파일 접근 차단
<FilesMatch "^(hosting\.php|\.env|\.git)">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

## 🗄️ 데이터베이스 설정

### 1. DB 생성 및 임포트
```sql
-- 1. 카페24 관리자에서 DB 생성
-- 2. phpMyAdmin 접속
-- 3. database/schema.sql 임포트
-- 4. 관리자 계정 생성 (fix_admin_password.php 실행)
```

### 2. 초기 데이터 확인
```sql
-- 관리자 계정 확인
SELECT * FROM admins;

-- 테이블 확인
SHOW TABLES;
```

---

## ⚠️ 알려진 문제 및 해결 방법

### 1. 환불 API 실패
**원인**: 테스트 키 사용 또는 네트워크 문제
**해결**:
- 라이브 키로 변경
- curl 확장 모듈 활성화 확인
- 방화벽 설정 확인

### 2. 한글 깨짐
**원인**: DB 인코딩 문제
**해결**:
```sql
ALTER DATABASE your_db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE table_name CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. 세션 문제
**원인**: 세션 저장 경로 권한
**해결**:
```php
// config/app.php 또는 hosting.php에 추가
ini_set('session.save_path', '/home/your_account/tmp');
```

### 4. composer 의존성
**카페24에서 composer 사용 불가 시**:
- 로컬에서 `composer install` 실행
- `vendor/` 폴더 전체 FTP 업로드

---

## ✅ 배포 후 체크리스트

### 1. 기본 기능 테스트
- [ ] 메인 페이지 접속
- [ ] 회원가입 (고객/매니저)
- [ ] 로그인
- [ ] 서비스 신청
- [ ] 결제 (실제 카드로 소액 테스트)
- [ ] 환불 테스트
- [ ] 관리자 페이지 접속

### 2. API 테스트
- [ ] VWorld 주소 검색
- [ ] 토스페이먼츠 결제
- [ ] 토스페이먼츠 환불
- [ ] 푸시 알림 (매니저 앱)

### 3. 보안 체크
- [ ] `hosting.php` 외부 접근 차단 확인
- [ ] 관리자 페이지 비밀번호 강화
- [ ] HTTPS 적용 확인
- [ ] SQL 인젝션 방어 확인

### 4. 성능 체크
- [ ] 페이지 로딩 속도
- [ ] DB 쿼리 최적화
- [ ] 이미지 최적화

---

## 🚀 배포 순서

1. **로컬에서 준비**
   ```bash
   composer install
   # vendor/ 폴더 생성 확인
   ```

2. **카페24 DB 생성**
   - 관리자 페이지에서 DB 생성
   - DB 정보 메모

3. **hosting.php 작성**
   - DB 정보 입력
   - 토스페이먼츠 라이브 키 입력
   - 도메인 설정

4. **FTP 업로드**
   - 모든 파일 업로드
   - `hosting.php`는 서버에서 직접 생성

5. **DB 스키마 임포트**
   - phpMyAdmin에서 `schema.sql` 실행

6. **관리자 계정 생성**
   - `fix_admin_password.php` 실행
   - 또는 DB에 직접 INSERT

7. **테스트**
   - 모든 기능 테스트
   - 실제 결제/환불 소액 테스트

8. **모니터링**
   - 에러 로그 확인
   - 사용자 피드백 수집

---

## 📞 문제 발생 시

1. **에러 로그 확인**
   ```
   카페24: /home/계정명/logs/
   또는 phpMyAdmin > 상태 > 에러 로그
   ```

2. **디버그 모드 활성화**
   ```php
   // hosting.php에서
   define('APP_DEBUG', true);
   ```

3. **카페24 고객센터 문의**
   - PHP 버전 문제
   - 확장 모듈 활성화
   - 서버 설정

---

## 🎯 결론

**카페24 웹호스팅 배포 가능 ✅**

**주의사항**:
1. 테스트 키 → 라이브 키 변경 필수
2. `hosting.php` 파일 생성 필수
3. DB 정보 정확히 입력
4. 소액 결제로 충분히 테스트
5. 보안 설정 확인

**예상 소요 시간**: 1-2시간
