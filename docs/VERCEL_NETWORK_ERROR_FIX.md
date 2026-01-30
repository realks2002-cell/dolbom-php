# Vercel 네트워크 에러 해결 가이드

## 🔴 문제 상황

Vercel에서 앱을 열면 로그인 화면은 나오지만 **네트워크 에러**가 발생합니다.

## ✅ 해결 방법

### 1단계: 카페24 서버에 수정된 파일 업로드

다음 파일들을 카페24 서버에 업로드하세요:

```
📁 업로드할 파일:
├── config/hosting.php          (CORS 설정 수정됨)
└── api/cors.php                 (와일드카드 패턴 지원 추가)
```

### 2단계: Vercel 환경 변수 확인

Vercel 대시보드에서 환경 변수가 제대로 설정되었는지 확인:

1. **Vercel 프로젝트** → **Settings** → **Environment Variables**
2. 다음 변수가 있는지 확인:
   - **Key**: `VITE_API_BASE`
   - **Value**: `https://travel23.mycafe24.com`

3. 없으면 추가하고 **Redeploy** 클릭

### 3단계: Vercel 도메인 확인 및 설정

#### Vercel 도메인 확인
1. Vercel 대시보드 → 프로젝트 클릭
2. **Domains** 탭에서 도메인 확인
   - 예: `https://dolbom-vercel-xxxxx.vercel.app`

#### hosting.php 업데이트 (선택사항)
실제 Vercel 도메인을 알면 `hosting.php`에 명시적으로 추가할 수 있습니다:

```php
// 예시: 실제 도메인이 https://dolbom-vercel-abc123.vercel.app 인 경우
define('API_CORS_ORIGINS', 'https://travel23.mycafe24.com,https://dolbom-vercel-abc123.vercel.app,https://*.vercel.app');
define('VITE_APP_URL', 'https://dolbom-vercel-abc123.vercel.app');
```

**하지만** 와일드카드 패턴(`https://*.vercel.app`)이 이미 설정되어 있으므로, 추가 설정 없이도 작동합니다.

### 4단계: 테스트

1. **Vercel 앱 접속**
   - `https://your-app.vercel.app` 접속

2. **브라우저 개발자 도구 열기**
   - F12 또는 우클릭 → 검사
   - **Console** 탭 확인

3. **로그인 시도**
   - 전화번호와 비밀번호 입력
   - **Network** 탭에서 API 호출 확인
   - CORS 오류가 사라졌는지 확인

## 🔍 문제 진단

### CORS 오류가 계속 발생하는 경우

1. **브라우저 콘솔 확인**
   ```
   Access to fetch at 'https://travel23.mycafe24.com/api/manager/login' 
   from origin 'https://your-app.vercel.app' has been blocked by CORS policy
   ```

2. **카페24 서버 확인**
   - `config/hosting.php` 파일이 업로드되었는지 확인
   - 파일 내용에 `https://*.vercel.app` 패턴이 있는지 확인

3. **Vercel 환경 변수 확인**
   - `VITE_API_BASE`가 `https://travel23.mycafe24.com`으로 설정되어 있는지 확인
   - 설정 후 **Redeploy** 필요

### 네트워크 오류 (CORS 아님)

1. **API 서버 상태 확인**
   - `https://travel23.mycafe24.com/api/manager/login` 직접 접속
   - JSON 응답이 나오는지 확인

2. **Vercel 환경 변수 확인**
   - 빌드 로그에서 `VITE_API_BASE` 값 확인
   - 빌드 시 환경 변수가 제대로 주입되었는지 확인

## 📋 체크리스트

- [ ] `config/hosting.php` 업로드 (CORS 설정)
- [ ] `api/cors.php` 업로드 (와일드카드 패턴 지원)
- [ ] Vercel 환경 변수 `VITE_API_BASE` 설정 확인
- [ ] Vercel 프로젝트 재배포 (환경 변수 변경 시)
- [ ] 브라우저에서 로그인 테스트
- [ ] 개발자 도구에서 CORS 오류 확인

## 💡 참고

- **와일드카드 패턴**: `https://*.vercel.app`은 모든 Vercel 서브도메인을 허용합니다.
- **환경 변수**: Vercel에서 환경 변수를 변경하면 **반드시 재배포**해야 합니다.
- **캐시**: 브라우저 캐시를 지우고 다시 시도해보세요 (Ctrl+Shift+R).
