# Hangbok77 매니저 앱

매니저용 웹 앱 (Vue 3 + Vite + Tailwind).  
Vercel 배포 후 Capacitor로 감싸 하이브리드 앱 전환 가능.

## 로컬 실행

1. 루트에서 PHP 서버 실행 (매니저 API용):
   ```bash
   php -S localhost:8000 router.php
   ```
2. 매니저 앱 의존성 설치 및 실행:
   ```bash
   cd manager-app
   npm install
   npm run dev
   ```
3. http://localhost:5173 접속. `/api` 요청은 8000으로 프록시됨.

## Vercel 배포

1. Vercel에서 **manager-app** 폴더를 루트로 하는 프로젝트 생성.
2. 환경 변수 설정:
   - `VITE_API_BASE`: 웹호스팅 PHP API 주소 (예: `https://api.example.com`)
3. 배포 시 `npm run build` → `dist` 출력. rewrites로 SPA 라우팅 처리.

## API (PHP 백엔드)

- `POST /api/auth/login` — 매니저 로그인 → `{ token, user }`
- `GET /api/manager/me` — Bearer 필수, 현재 매니저
- `GET /api/manager/requests` — 새 요청 목록
- `GET /api/manager/applications` — 지원 현황
- `GET /api/manager/schedule` — 내 일정

CORS는 `config/app.php`의 `API_CORS_ORIGINS`로 제어.  
Vercel 도메인을 등록해야 함 (또는 개발 시 `*`).
