# Vercel 배포 빠른 시작

## 📦 1단계: GitHub에 푸시

```powershell
cd c:\xampp\htdocs\dolbom_php
git add .
git commit -m "Vercel 배포를 위한 PWA 설정"
git push origin main
```

## 🚀 2단계: Vercel 배포

1. **Vercel 웹사이트 접속**
   - https://vercel.com
   - GitHub 계정으로 로그인

2. **New Project 클릭**

3. **GitHub 저장소 선택**
   - `dolbom` 저장소 선택 (또는 현재 저장소명)

4. **프로젝트 설정**
   - **Root Directory**: `manager-app` 입력 ✅ 매우 중요!
   - **Framework Preset**: Vite
   - **Build Command**: `npm run build`
   - **Output Directory**: `dist`

5. **Environment Variables 추가**
   ```
   Key: VITE_API_BASE
   Value: https://travel23.mycafe24.com
   ```

6. **Deploy 클릭**

## 🔧 3단계: 배포 후 설정

### Vercel 도메인 확인
배포 완료 후 Vercel 대시보드에서 도메인 확인:
- 예: `https://hangbok77-manager.vercel.app`
- 또는 `https://hangbok77-manager-xxxx.vercel.app`

### 카페24 설정 업데이트

`config/hosting.php` 파일에서 실제 Vercel 도메인으로 변경:

```php
// API_CORS_ORIGINS
define('API_CORS_ORIGINS', 'https://실제-vercel-도메인.vercel.app');

// VITE_APP_URL
define('VITE_APP_URL', 'https://실제-vercel-도메인.vercel.app');
```

카페24 서버에 업로드:
```
업로드 파일: config/hosting.php
```

## ✅ 4단계: 테스트

1. **Vercel 앱 접속**
   - `https://your-app.vercel.app` 접속
   - PWA가 정상 로드되는지 확인

2. **로그인 테스트**
   - 전화번호와 비밀번호로 로그인
   - API 호출이 정상 작동하는지 확인

3. **스마트폰에서 PWA 설치**
   - Chrome/Edge 브라우저에서 접속
   - 주소창의 "설치" 아이콘 클릭
   - 홈 화면에 추가

4. **기능 테스트**
   - 매칭 요청 조회
   - 지원하기 기능
   - 일정 확인 등

## 🔄 자동 배포

GitHub에 푸시하면 Vercel이 자동으로 빌드/배포:
```powershell
git add .
git commit -m "매니저 앱 수정"
git push origin main
```

Vercel 대시보드에서 배포 상태 확인 가능

## 🌐 커스텀 도메인 (선택)

1. **도메인 준비**
   - 예: `manager.hangbok77.com`

2. **Vercel에서 설정**
   - Project → Settings → Domains
   - 도메인 추가

3. **DNS 설정**
   - CNAME 레코드 추가
   - Host: `manager`
   - Value: `cname.vercel-dns.com`

4. **카페24 설정 업데이트**
   ```php
   define('VITE_APP_URL', 'https://manager.hangbok77.com');
   define('API_CORS_ORIGINS', 'https://manager.hangbok77.com');
   ```

## 🚨 문제 해결

### CORS 오류 발생 시
- 카페24 `hosting.php`의 `API_CORS_ORIGINS` 확인
- Vercel 도메인이 포함되어 있는지 확인

### API 호출 실패 시
- `VITE_API_BASE` 환경 변수 확인
- 카페24 PHP 서버가 정상 작동하는지 확인

### PWA 설치 안 됨
- HTTPS 확인 (Vercel은 자동 제공)
- manifest.webmanifest 파일 확인
- Service Worker 등록 확인

## 📊 비교: 카페24 vs Vercel

| 항목 | 카페24 `/manager-app/` | Vercel (권장) |
|------|------------------------|---------------|
| 설정 복잡도 | 높음 (.htaccess 필요) | 낮음 (자동) |
| HTTPS | 별도 설정 필요 | 자동 제공 ✅ |
| PWA 지원 | 복잡함 | 완벽 지원 ✅ |
| 배포 속도 | 수동 업로드 | 자동 배포 ✅ |
| 비용 | 포함됨 | 무료 ✅ |
| 속도 | 일반 | CDN 빠름 ✅ |

## 💡 권장 사항

**Vercel 배포를 강력히 권장**합니다:
- `.htaccess` 문제 없음
- HTTPS 자동 제공
- PWA 완벽 지원
- 자동 배포
- 무료

카페24는 PHP 백엔드만 호스팅하고, PWA는 Vercel에 배포하는 것이 베스트입니다.
