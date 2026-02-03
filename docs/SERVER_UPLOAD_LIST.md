# 서버 업로드 파일 목록

## 📦 업로드할 파일/폴더

### ✅ 필수 폴더 (전체 업로드)

```
📁 api/                          # API 엔드포인트
   ├── admin/
   │   └── process-refund.php    # ⭐ 새로 추가됨
   ├── auth/
   ├── bookings/
   ├── manager/
   ├── payments/
   ├── requests/
   ├── test/
   ├── address-search.php
   ├── address-suggest.php
   └── cors.php

📁 assets/                       # CSS, JS, 이미지
   ├── css/
   │   ├── custom.css
   │   ├── tailwind.min.css
   │   └── tailwind.output.css
   ├── icons/
   │   ├── icon-192x192.png
   │   ├── icon-512x512.png
   │   └── icon.png
   ├── images/
   │   ├── babycare.jpg
   │   ├── clean.jpg
   │   ├── cook.jpg
   │   ├── hero.jpg
   │   └── seniorcare.jpg
   ├── js/
   │   ├── main.js
   │   └── service-worker.js
   └── manifest.json

📁 components/                   # 공통 컴포넌트
   ├── admin-layout.php          # ⭐ 메뉴 수정됨
   ├── footer.php
   ├── header.php
   ├── layout.php
   └── nav.php

📁 config/                       # 설정 파일
   ├── app.php
   └── hosting.php.example
   ⚠️ hosting.php는 서버에서 직접 생성!

📁 database/                     # DB 연결, 스키마
   ├── connect.php
   ├── schema.sql
   ├── export.php
   ├── download.php
   └── migrations/               # 마이그레이션 파일 (전체)

📁 includes/                     # 헬퍼, 인증
   ├── auth.php
   ├── fcm.php
   ├── helpers.php
   ├── jwt.php
   ├── webpush.php
   ├── webpush_lib.php
   └── webpush_simple.php

📁 pages/                        # 페이지 파일
   ├── admin/
   │   ├── index.php
   │   ├── managers.php
   │   ├── payments.php
   │   ├── refund-info.php       # ⭐ 새로 추가됨
   │   ├── refunds.php
   │   ├── requests.php
   │   ├── revenue.php
   │   └── users.php
   ├── auth/
   │   ├── login.php
   │   ├── logout.php
   │   └── signup.php
   ├── bookings/
   │   ├── index.php
   │   └── review.php
   ├── manager/
   │   ├── applications.php
   │   ├── check-manager.php
   │   ├── dashboard.php
   │   ├── earnings.php
   │   ├── login.php
   │   ├── logout.php
   │   ├── matching.php
   │   ├── profile.php
   │   ├── recruit.php
   │   ├── requests.php
   │   ├── reset-password.php
   │   ├── schedule.php
   │   └── signup.php
   ├── payment/
   │   ├── fail.php
   │   ├── register-card.php
   │   └── success.php
   ├── requests/
   │   ├── detail.php
   │   └── new.php
   ├── test/
   │   └── push-notification.php
   ├── about.php
   ├── faq.php
   ├── index.php
   └── service-guide.php

📁 vendor/                       # Composer 의존성
   (전체 폴더 업로드)

📄 루트 파일
   ├── .htaccess                 # URL 리라이팅 (중요!)
   ├── admin.php
   ├── index.php
   ├── router.php
   └── fix_admin_password.php    # 서버에서 1회 실행 후 삭제

📄 진단 스크립트 (선택)
   ├── check_environment.php     # 서버 환경 확인
   └── test_refund_api.php       # 환불 API 테스트
```

---

## ❌ 업로드하지 말 것

```
❌ .git/                         # Git 저장소
❌ node_modules/                 # Node.js 의존성
❌ test/                         # 테스트 파일
❌ tests/                        # Playwright 테스트
❌ test-results/                 # 테스트 결과
❌ playwright-report/            # 리포트
❌ docs/                         # 문서
❌ tosspayments/                 # 샘플 코드 (50MB)
❌ scripts/                      # 개발 스크립트
❌ database/dolbom_backup_*.sql  # 백업 파일
❌ travel23.sql                  # 백업 파일
❌ landing.html                  # 임시 파일
❌ run-local.bat                 # 로컬 실행 스크립트
❌ package.json (루트)           # Node.js 설정
❌ package-lock.json (루트)      # Node.js 잠금
❌ tailwind.config.js (루트)     # Tailwind 설정
❌ playwright.config.ts          # Playwright 설정
❌ composer-setup.php            # Composer 설치
❌ *.md 파일들                   # 문서 파일
```

---

## 🚀 빠른 업로드 방법

### 방법 1: 선택적 업로드 (권장)

**필수 폴더만 선택**:
```
api/
assets/
components/
config/
database/
includes/
pages/
vendor/
.htaccess
admin.php
index.php
router.php
```

### 방법 2: 압축 후 업로드

**Windows (PowerShell)**:
```powershell
# 필요한 폴더만 압축
Compress-Archive -Path api,assets,components,config,database,includes,pages,vendor,.htaccess,admin.php,index.php,router.php -DestinationPath dolbom-deploy.zip
```

**Linux/Mac**:
```bash
zip -r dolbom-deploy.zip api assets components config database includes pages vendor .htaccess admin.php index.php router.php
```

---

## 📋 업로드 후 작업

### 1. `config/hosting.php` 생성 (서버에서)

```php
<?php
// 카페24 DB 정보 입력
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// 도메인 설정
define('BASE_URL', 'https://yourdomain.com');

// 토스페이먼츠 라이브 키
define('TOSS_CLIENT_KEY', 'live_gck_xxxxx');
define('TOSS_SECRET_KEY', 'live_gsk_xxxxx');

// 디버그 모드 끄기
define('APP_DEBUG', false);
?>
```

### 2. DB 스키마 임포트

```
1. phpMyAdmin 접속
2. database/schema.sql 파일 임포트
3. 테이블 생성 확인
```

### 3. 관리자 계정 생성

```
1. fix_admin_password.php 실행
2. 또는 DB에 직접 INSERT
3. 실행 후 파일 삭제
```

### 4. 권한 설정

```bash
chmod 755 api assets components config database includes pages
chmod 644 *.php
chmod 777 storage (있는 경우)
```

---

## ⭐ 이번에 새로 추가된 파일

```
✅ pages/admin/refund-info.php      # 취소/환불 요청 페이지
✅ api/admin/process-refund.php     # 수동 환불 API
✅ components/admin-layout.php      # 메뉴 수정
```

---

## 🎯 업로드 체크리스트

- [ ] 불필요한 폴더 삭제 (tosspayments, test, docs 등)
- [ ] 필수 폴더 FTP 업로드
- [ ] .htaccess 파일 업로드
- [ ] vendor/ 폴더 업로드
- [ ] hosting.php 서버에서 생성
- [ ] DB 스키마 임포트
- [ ] 관리자 계정 생성
- [ ] 파일 권한 설정
- [ ] 테스트 (회원가입, 결제, 환불)

---

## 💡 팁

1. **FTP 클라이언트 추천**: FileZilla
2. **압축 업로드**: 파일 수가 많으면 압축 후 업로드가 빠름
3. **vendor/ 폴더**: 용량이 크므로 압축 권장
4. **진단 스크립트**: check_environment.php 먼저 업로드하여 환경 확인

---

## 📞 문제 발생 시

1. **500 에러**: hosting.php 설정 확인
2. **404 에러**: .htaccess 업로드 확인
3. **DB 연결 실패**: hosting.php의 DB 정보 확인
4. **환불 실패**: 라이브 키 확인

로컬 서버가 실행되었습니다: `http://localhost:8000/admin/refund-info`
