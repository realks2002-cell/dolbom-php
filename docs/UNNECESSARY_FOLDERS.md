# 배포 시 불필요한 폴더 목록

## ❌ 삭제해도 되는 폴더 (배포 불필요)

### 1. **개발/테스트 관련**
```
❌ test/                    # 테스트 파일
❌ tests/                   # Playwright 테스트
❌ test-results/            # 테스트 결과
❌ playwright-report/       # Playwright 리포트
❌ playwright.config.ts     # Playwright 설정
```

### 2. **문서 폴더**
```
❌ docs/                    # 개발 문서 (README, 가이드 등)
   - 필요하면 일부만 유지
   - 대부분 개발용 문서
```

### 3. **토스페이먼츠 샘플 코드**
```
❌ tosspayments/            # 토스페이먼츠 샘플 코드 (531개 파일!)
   - 실제 사용하지 않는 예제 코드
   - 용량만 차지 (매우 큼)
```

### 4. **Node.js 관련 (매니저 앱 제외)**
```
❌ package.json             # 루트의 Node.js 설정 (사용 안 함)
❌ package-lock.json        # 루트의 Node.js 잠금 파일
❌ tailwind.config.js       # 루트의 Tailwind 설정 (빌드 완료)
```

### 5. **데이터베이스 백업 파일**
```
❌ database/dolbom_backup_*.sql  # 로컬 백업 파일 (4개)
❌ travel23.sql                  # 백업 SQL 파일
```

### 6. **임시/개발 파일**
```
❌ landing.html             # 임시 랜딩 페이지
❌ run-local.bat            # 로컬 실행 스크립트
❌ fix_admin_password.php   # 임시 스크립트 (서버에서 실행 후 삭제)
❌ composer-setup.php       # Composer 설치 스크립트
```

### 7. **Git 관련**
```
❌ .git/                    # Git 저장소 (FTP 업로드 시 자동 제외)
❌ .gitignore               # Git 설정 (배포 불필요)
```

### 8. **스크립트 폴더 (일부)**
```
❌ scripts/generate_vapid_*.html  # VAPID 키 생성 스크립트
   - 이미 생성 완료
   - 서버에서 재생성 필요 없음
```

---

## ⚠️ 주의: 삭제하면 안 되는 폴더

### ✅ **필수 폴더 (반드시 업로드)**
```
✅ api/                     # API 엔드포인트
✅ assets/                  # CSS, JS, 이미지
✅ components/              # 공통 컴포넌트
✅ config/                  # 설정 파일
✅ database/                # DB 연결, 스키마
   ✅ connect.php
   ✅ schema.sql
   ✅ migrations/           # 마이그레이션 파일
✅ includes/                # 헬퍼, 인증
✅ pages/                   # 페이지 파일
✅ vendor/                  # Composer 의존성
✅ index.php                # 진입점
✅ router.php               # 라우터
✅ admin.php                # 관리자 로그인
✅ .htaccess                # URL 리라이팅
```

### ⚠️ **선택적 폴더**

#### **manager-app/** (매니저 앱)
```
⚠️ manager-app/
   - Vue.js 매니저 앱
   - 빌드 후 dist/ 폴더만 업로드
   - 또는 Vercel에 별도 배포
```

**옵션 1**: 빌드 후 `dist/` 폴더만 업로드
```bash
cd manager-app
npm run build
# dist/ 폴더를 서버의 /manager-app/에 업로드
```

**옵션 2**: Vercel에 별도 배포 (권장)
- manager-app을 별도 저장소로 분리
- Vercel에 배포
- `config/hosting.php`에서 `VITE_APP_URL` 설정

#### **storage/** 폴더
```
⚠️ storage/
   - 업로드 파일 저장용
   - 빈 폴더라도 유지 (권한 777)
```

---

## 📊 용량 비교

### 삭제 전
```
전체 용량: ~200MB (추정)
- tosspayments/: ~50MB
- node_modules/: ~100MB (manager-app)
- vendor/: ~20MB
- 기타: ~30MB
```

### 삭제 후
```
배포 용량: ~50MB (추정)
- vendor/: ~20MB
- assets/: ~5MB
- 기타 PHP 파일: ~25MB
```

---

## 🗑️ 삭제 명령어

### Windows (PowerShell)
```powershell
# 테스트 폴더
Remove-Item -Recurse -Force test, tests, test-results, playwright-report

# 문서 폴더
Remove-Item -Recurse -Force docs

# 토스페이먼츠 샘플
Remove-Item -Recurse -Force tosspayments

# 백업 파일
Remove-Item database\dolbom_backup_*.sql
Remove-Item travel23.sql

# 임시 파일
Remove-Item landing.html, run-local.bat, composer-setup.php
Remove-Item package.json, package-lock.json, tailwind.config.js
Remove-Item playwright.config.ts

# 스크립트
Remove-Item -Recurse -Force scripts
```

### Linux/Mac
```bash
# 테스트 폴더
rm -rf test tests test-results playwright-report

# 문서 폴더
rm -rf docs

# 토스페이먼츠 샘플
rm -rf tosspayments

# 백업 파일
rm database/dolbom_backup_*.sql
rm travel23.sql

# 임시 파일
rm landing.html run-local.bat composer-setup.php
rm package.json package-lock.json tailwind.config.js
rm playwright.config.ts

# 스크립트
rm -rf scripts
```

---

## 📦 최종 업로드 폴더 구조

```
/www/ (또는 /public_html/)
├── api/
├── assets/
│   ├── css/
│   ├── icons/
│   ├── images/
│   └── js/
├── components/
├── config/
│   ├── app.php
│   └── hosting.php (서버에서 생성)
├── database/
│   ├── connect.php
│   ├── schema.sql
│   └── migrations/
├── includes/
├── pages/
├── vendor/
├── .htaccess
├── admin.php
├── index.php
└── router.php
```

---

## ✅ 체크리스트

배포 전 확인:
- [ ] 불필요한 폴더 삭제
- [ ] `hosting.php` 생성
- [ ] `vendor/` 폴더 포함 (composer install 후)
- [ ] `.htaccess` 파일 포함
- [ ] `database/schema.sql` 포함
- [ ] 이미지 파일 포함 (`assets/images/`)
- [ ] 테스트 키 → 라이브 키 변경

---

## 💡 팁

1. **용량 절약**: `tosspayments/` 폴더만 삭제해도 ~50MB 절약
2. **속도 향상**: 불필요한 파일 제거로 FTP 업로드 시간 단축
3. **보안**: 테스트/개발 파일 제거로 보안 강화
4. **관리**: 필수 파일만 유지하여 유지보수 용이

---

## 🚀 권장 배포 방법

1. **로컬에서 정리**
   ```bash
   # 불필요한 폴더 삭제
   # composer install 실행
   ```

2. **압축**
   ```bash
   # 필요한 폴더만 압축
   zip -r dolbom-deploy.zip api assets components config database includes pages vendor .htaccess admin.php index.php router.php
   ```

3. **FTP 업로드**
   - 압축 파일 업로드
   - 서버에서 압축 해제
   - `hosting.php` 생성

4. **권한 설정**
   ```bash
   chmod 755 api assets components config database includes pages
   chmod 644 *.php
   chmod 777 storage
   ```
