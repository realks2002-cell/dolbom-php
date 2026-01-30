# 카페24 웹호스팅 환경 변경 완료 ✅

## 📝 변경된 파일 목록

### 1. 설정 파일
- ✅ `config/app.php` - 환경 자동 감지, DB 설정 환경 변수 지원
- ✅ `config/hosting.php.example` - **새로 생성** (호스팅 환경 설정 예제 파일)
- ✅ `.env.production.example` - 환경 변수 예제 파일

### 2. Vue.js 앱 설정
- ✅ `manager-app/.env.production` - 프로덕션 API 베이스 URL 설정
- ✅ `manager-app/vite.config.js` - 프로덕션 빌드 설정 추가

### 3. PHP 파일 수정
- ✅ `pages/manager/login.php` - Vue.js 앱 URL을 설정에서 가져오도록 변경
- ✅ `pages/manager/dashboard.php` - Vue.js 앱 URL을 설정에서 가져오도록 변경
- ✅ `pages/manager/recruit.php` - Vue.js 앱 URL을 설정에서 가져오도록 변경

### 4. 서버 설정
- ✅ `.htaccess` - HTTPS 강제, 보안 설정 추가

### 5. 문서
- ✅ `DEPLOYMENT_GUIDE.md` - 배포 가이드 작성
- ✅ `BUILD_FOR_HOSTING.md` - 빌드 가이드 작성

---

## 🚀 배포 전 체크리스트

### 필수 작업

1. **`config/hosting.php` 파일 생성 및 수정** ⚠️ 필수
   - `config/hosting.php.example` 파일을 복사하여 `config/hosting.php` 생성
   - 또는 호스팅 서버에서 직접 생성
   ```php
   // 데이터베이스 정보 입력
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   
   // 도메인 설정
   define('BASE_URL', 'https://your-domain.com');
   
   // 강력한 시크릿 키 생성 (최소 32자)
   define('API_JWT_SECRET', '강력한-랜덤-문자열-생성');
   
   // 토스페이먼츠 실제 키 입력
   define('TOSS_CLIENT_KEY', 'your_toss_client_key');
   define('TOSS_SECRET_KEY', 'your_toss_secret_key');
   ```

2. **`manager-app/.env.production` 파일 수정** ⚠️ 필수
   ```env
   VITE_API_BASE=https://your-domain.com
   ```

3. **Vue.js 앱 빌드** ⚠️ 필수
   ```powershell
   cd manager-app
   npm install
   npm run build
   ```

4. **데이터베이스 스키마 적용**
   - phpMyAdmin에서 `database/schema.sql` 실행
   - 필요한 마이그레이션 파일 실행

---

## 📤 업로드할 파일

### 업로드 필요
- ✅ 모든 PHP 파일 (`*.php`)
- ✅ `config/` 폴더 (hosting.php 포함)
- ✅ `api/` 폴더
- ✅ `pages/` 폴더
- ✅ `components/` 폴더
- ✅ `includes/` 폴더
- ✅ `assets/` 폴더
- ✅ `database/` 폴더 (connect.php, migrations/)
- ✅ `index.php`, `router.php`
- ✅ `.htaccess`
- ✅ `manager-app/dist/` 폴더의 **모든 파일**

### 업로드 불필요
- ❌ `node_modules/`
- ❌ `manager-app/src/` (소스 파일)
- ❌ `manager-app/android/`
- ❌ `.git/`
- ❌ `storage/sessions/*.sess_*` (세션 파일)
- ❌ 개발용 테스트 파일

---

## 🔧 호스팅 환경에서의 동작

### 자동 감지 기능
- **로컬 환경**: `localhost`, `127.0.0.1` → `APP_DEBUG=true`
- **호스팅 환경**: 그 외 도메인 → `APP_DEBUG=false`
- **BASE_URL**: 자동으로 현재 도메인 감지
- **Vue.js 앱 URL**: `BASE_URL/manager-app` 자동 설정

### 환경 변수 우선순위
1. `config/hosting.php`에서 정의된 상수
2. 환경 변수 (`getenv()`)
3. 기본값 (로컬 개발용)

---

## 📍 주요 변경 사항 요약

1. **환경 자동 감지**: 로컬/호스팅 자동 구분
2. **설정 파일 분리**: `hosting.php`로 호스팅 전용 설정 관리
3. **보안 강화**: 프로덕션에서 에러 표시 비활성화, HTTPS 강제
4. **URL 자동 설정**: 도메인 기반으로 자동 설정
5. **빌드 최적화**: 프로덕션 빌드 설정 추가

---

## ⚠️ 주의사항

1. **`config/hosting.php`는 실제 값이 포함되므로 Git에 커밋하지 마세요**
   - `.gitignore`에 추가되어 있음
   - `hosting.php.example`은 Git에 포함됨 (예제용)
   - 호스팅 서버에서 `hosting.php.example`을 복사하여 `hosting.php` 생성 후 수정

2. **API_JWT_SECRET은 반드시 강력한 키로 변경**
   - 최소 32자 이상의 랜덤 문자열
   - 온라인 랜덤 키 생성기 사용 권장

3. **토스페이먼츠 키는 실제 키로 변경**
   - 테스트 키에서 실제 키로 변경 필요

4. **데이터베이스 백업**
   - 배포 전 데이터베이스 백업 권장

---

## 🎯 다음 단계

1. `config/hosting.php` 파일 수정
2. `manager-app/.env.production` 파일 수정
3. Vue.js 앱 빌드 (`npm run build`)
4. FTP로 파일 업로드
5. 데이터베이스 스키마 적용
6. 웹사이트 테스트

자세한 내용은 `DEPLOYMENT_GUIDE.md`를 참고하세요.
