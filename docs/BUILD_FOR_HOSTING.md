# 호스팅 배포용 빌드 가이드

## 📦 Vue.js 앱 빌드 방법

### 1. 환경 변수 설정
`manager-app/.env.production` 파일을 열고 실제 도메인으로 수정:

```env
VITE_API_BASE=https://your-domain.com
```

### 2. 빌드 실행

**방법 1: 환경 변수 사용 (권장)**
```powershell
cd manager-app
npm install

# Windows PowerShell:
$env:BASE="/manager-app/"; npm run build

# Linux/Mac:
BASE=/manager-app/ npm run build
```

**방법 2: package.json 스크립트 사용**
```powershell
cd manager-app
npm install
npm run build:hosting
```

**중요**: 
- 빌드 시 `BASE=/manager-app/` 환경 변수를 설정해야 합니다
- 이렇게 하면 빌드된 `index.html`의 스크립트 경로가 `/manager-app/assets/...`로 시작합니다
- 빌드 후 `dist/index.html` 파일을 열어서 스크립트 경로가 `/manager-app/assets/...`로 시작하는지 확인하세요

### 3. 빌드 결과 확인
빌드 완료 후 `manager-app/dist/` 폴더가 생성됩니다.

**빌드된 파일 확인:**
1. `dist/index.html` 파일을 메모장으로 열기
2. 스크립트 경로가 `/manager-app/assets/...`로 시작하는지 확인
   - ✅ 올바름: `<script type="module" src="/manager-app/assets/index-xxxxx.js"></script>`
   - ❌ 잘못됨: `<script type="module" src="/assets/index-xxxxx.js"></script>`

**⚠️ 주의**: 로컬에서 `file://` 프로토콜로 직접 열면 CORS 오류가 발생합니다. 이는 정상입니다. 호스팅에 업로드하면 정상 작동합니다.

### 4. 업로드할 파일
`manager-app/dist/` 폴더의 **모든 파일**을 호스팅의 `manager-app/` 디렉토리에 업로드합니다.

**필수 파일**:
- `manager-app/dist/` 폴더의 모든 파일
- `manager-app/.htaccess` 파일 (Vue Router 라우팅용)

---

## 📁 업로드 구조

호스팅 서버의 디렉토리 구조:

```
www/ (또는 public_html/)
├── index.php
├── router.php
├── .htaccess
├── config/
│   ├── app.php
│   └── hosting.php (수정 필요!)
├── manager-app/
│   ├── index.html
│   ├── .htaccess  ← 중요! (Vue Router 라우팅용)
│   ├── assets/
│   │   ├── index-xxxxx.js
│   │   └── index-xxxxx.css
│   └── ... (dist 폴더의 모든 파일)
└── ... (기타 PHP 파일들)
```

---

## ⚠️ 중요 사항

1. **`config/hosting.php` 파일 수정 필수**
   - 데이터베이스 정보
   - BASE_URL
   - API_JWT_SECRET (강력한 키로 변경)
   - TOSS 키 (실제 키로 변경)

2. **Vue.js 앱은 빌드된 파일만 업로드**
   - `manager-app/src/` 폴더는 업로드하지 않음
   - `manager-app/dist/` 폴더의 내용만 업로드

3. **환경 변수 확인**
   - `manager-app/.env.production` 파일의 `VITE_API_BASE` 확인

4. **파일 권한**
   - `storage/sessions/` 디렉토리는 쓰기 권한 필요 (755 또는 777)

5. **카페24 호스팅 특별 사항**
   - Apache 서버이므로 `.htaccess` 파일이 자동으로 작동합니다
   - `mod_rewrite` 모듈이 활성화되어 있어야 합니다 (일반적으로 활성화됨)
   - 빌드 시 `BASE=/manager-app/` 환경 변수를 설정해야 합니다
   - 빌드된 `index.html`의 스크립트 경로가 `/manager-app/assets/...`로 시작해야 합니다

6. **404 오류 발생 시**
   - `TROUBLESHOOTING_404.md` 파일 참고
   - 브라우저 개발자 도구(F12) → Network 탭에서 어떤 파일이 404인지 확인
   - 빌드된 `dist/index.html` 파일의 스크립트 경로 확인
