# Vercel 배포 가이드 (PWA)

## 🎯 배포 전략

- **Vercel**: Vue.js PWA 앱 호스팅
- **카페24**: PHP 백엔드 API 호스팅

## 📋 배포 전 준비

### 1. 환경 변수 설정

`manager-app/.env.production` 파일 확인:
```env
VITE_API_BASE=https://travel23.mycafe24.com
```

### 2. vite.config.js 확인
- `base: '/'` (Vercel은 루트로 배포)
- PWA manifest의 `scope`, `start_url`도 `/`로 설정됨

### 3. 카페24 CORS 설정

`config/hosting.php` 파일에 Vercel 도메인 추가:
```php
if (!defined('API_CORS_ORIGINS')) {
    // Vercel 도메인 추가 (배포 후 실제 도메인으로 변경)
    define('API_CORS_ORIGINS', 'https://your-app.vercel.app,https://travel23.mycafe24.com');
}
```

배포 후 Vercel에서 할당한 실제 도메인으로 변경하세요.

## 🚀 Vercel 배포 방법

### 방법 1: GitHub 연동 (권장)

1. **GitHub에 코드 푸시**
   ```powershell
   cd c:\xampp\htdocs\dolbom_php
   git add manager-app/
   git commit -m "Vercel 배포를 위한 PWA 설정 수정"
   git push origin main
   ```

2. **Vercel 프로젝트 생성**
   - Vercel 웹사이트 접속: https://vercel.com
   - "New Project" 클릭
   - GitHub 저장소 선택 (dolbom)

3. **프로젝트 설정**
   - **Root Directory**: `manager-app` 입력 (중요!)
   - **Framework Preset**: Vite 선택
   - **Build Command**: `npm run build` (기본값)
   - **Output Directory**: `dist` (기본값)

4. **Environment Variables 설정**
   - Key: `VITE_API_BASE`
   - Value: `https://travel23.mycafe24.com`

5. **Deploy 클릭**

### 방법 2: Vercel CLI

```powershell
# Vercel CLI 설치 (한 번만)
npm install -g vercel

# 프로젝트 폴더로 이동
cd c:\xampp\htdocs\dolbom_php\manager-app

# Vercel 배포
vercel

# 프로덕션 배포
vercel --prod
```

## 📝 배포 후 작업

### 1. Vercel 도메인 확인
배포 완료 후 Vercel이 할당한 도메인 확인:
- 예: `https://hangbok77-manager.vercel.app`

### 2. 카페24 CORS 설정 업데이트
`config/hosting.php` 파일의 `API_CORS_ORIGINS`를 실제 Vercel 도메인으로 변경:
```php
define('API_CORS_ORIGINS', 'https://hangbok77-manager.vercel.app');
```

### 3. 카페24 VITE_APP_URL 설정 업데이트
`config/hosting.php`:
```php
if (!defined('VITE_APP_URL')) {
    define('VITE_APP_URL', 'https://hangbok77-manager.vercel.app');
}
```

### 4. PHP 매니저 로그인 리다이렉트 수정
`pages/manager/login.php`에서 이미 `VITE_APP_URL` 상수를 사용하고 있으므로, 위 설정만 변경하면 자동으로 Vercel로 리다이렉트됩니다.

## 🔄 아키텍처

```
[사용자 스마트폰]
        ↓
[Vercel - PWA 앱]
(https://your-app.vercel.app)
        ↓ API 호출
[카페24 - PHP 백엔드]
(https://travel23.mycafe24.com)
        ↓
   [MariaDB]
```

## ✅ 장점

1. **PWA 완벽 지원**
   - HTTPS 자동 제공
   - Service Worker 정상 작동
   - 스마트폰 설치 가능

2. **빠른 속도**
   - Vercel의 글로벌 CDN
   - 자동 최적화

3. **무료 사용**
   - Vercel Free 플랜으로 충분
   - 대역폭 100GB/월

4. **자동 배포**
   - GitHub 푸시 시 자동 빌드/배포
   - `.htaccess` 설정 불필요

## 🔧 추가 설정 (선택)

### 커스텀 도메인 연결 (Vercel)
1. Vercel 프로젝트 → Settings → Domains
2. 커스텀 도메인 추가 (예: `manager.hangbok77.com`)
3. DNS 설정 (CNAME 레코드 추가)

## 📋 체크리스트

- [ ] `manager-app/.env.production` 수정 (VITE_API_BASE)
- [ ] `vite.config.js` 수정 (base: '/')
- [ ] GitHub에 코드 푸시
- [ ] Vercel 프로젝트 생성 (Root Directory: manager-app)
- [ ] Environment Variables 설정 (VITE_API_BASE)
- [ ] 배포 완료 후 Vercel 도메인 확인
- [ ] 카페24 `hosting.php` 수정 (API_CORS_ORIGINS, VITE_APP_URL)
- [ ] 카페24 서버에 수정된 `hosting.php` 업로드
- [ ] 테스트: Vercel 앱에서 로그인 및 API 호출 확인

## 🚨 주의사항

1. **API CORS 설정 필수**
   - 카페24의 `hosting.php`에 Vercel 도메인 추가 필수
   - 추가하지 않으면 API 호출이 CORS 오류 발생

2. **환경 변수 확인**
   - Vercel에서 `VITE_API_BASE` 환경 변수 설정 확인
   - 빌드 시 자동으로 적용됨

3. **브라우저 테스트**
   - 스마트폰에서 Vercel 앱 접속
   - PWA 설치 옵션 확인
   - 로그인 및 API 호출 테스트
