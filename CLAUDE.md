# CLAUDE.md

이 파일은 Claude Code (claude.ai/code)가 이 저장소에서 코드 작업 시 참고하는 가이드입니다.

## 프로젝트 개요

**행복안심동행 (Hangbok77)** - 병원 동행 및 돌봄 서비스 매칭 플랫폼. 의료/노인 돌봄 서비스가 필요한 고객과 서비스 제공자(매니저)를 연결하는 양면 마켓플레이스.

## 기술 스택

- **백엔드:** PHP 7.4+ (절차적, 프레임워크 없음), MySQL/MariaDB with PDO
- **프론트엔드:** PHP 템플릿 + Tailwind CSS (고객/관리자용)
- **매니저 앱:** Vue.js 3 SPA, Vue Router 4, Vite 번들러
- **모바일:** Capacitor 6 (하이브리드 iOS/Android 앱)
- **결제:** 토스페이먼츠 (Toss Payments)
- **푸시 알림:** Web Push (minishlink/web-push) 및 FCM

## 개발 명령어

### PHP 백엔드
```bash
php -S localhost:8000                    # 로컬 개발 서버 시작
php database/run_migration.php           # 데이터베이스 마이그레이션 실행
```

### 매니저 앱 (Vue.js)
```bash
cd manager-app
npm install
npm run dev                              # 개발 서버 (포트 3000, /api를 :8000으로 프록시)
npm run build                            # 프로덕션 빌드
npm run build:hosting                    # Cafe24용 빌드 (/manager-app/ 베이스 경로)
```

### E2E 테스트 (Playwright)
```bash
npm test                                 # 전체 테스트 실행
npm run test:ui                          # UI 모드
npm run test:headed                      # 브라우저 표시 모드
```

### Tailwind CSS
```bash
npm run build:css                        # Tailwind 컴파일
npm run watch:css                        # 감시 모드
```

## 아키텍처

### 라우팅 (Front Controller 패턴)
- `index.php` - 메인 라우터, `?route=` 파라미터로 모든 요청 처리
- `.htaccess` - 깔끔한 URL (`/path`)을 `?route=path`로 리라이트
- `admin.php` - 관리자 전용 로그인 진입점

### 디렉토리 구조
- `api/` - REST API 엔드포인트 (JSON 응답, CORS는 `api/cors.php`에서 처리)
- `pages/` - 페이지 컨트롤러 (HTML이 포함된 PHP 템플릿)
- `components/` - 재사용 가능한 PHP 컴포넌트 (헤더, 푸터, 레이아웃)
- `includes/` - 헬퍼 함수 및 유틸리티
- `config/app.php` - 모든 설정 (DB, API 키, CORS 오리진)
- `database/` - 스키마, 마이그레이션, 연결 (`connect.php`)
- `manager-app/` - 매니저용 Vue.js SPA

### 인증
- **고객/관리자:** 세션 기반 (`includes/auth.php`)
  - `includes/helpers.php`의 `init_session()` 사용
  - 관리자 확인은 `require_admin()`
- **매니저 API:** JWT 토큰 (`includes/jwt.php`)
  - 미들웨어: `api/middleware/auth.php`
  - 인증 성공 시 `$apiUser` 전역 변수 설정
  - 30일 토큰 만료, Bearer 헤더

### 데이터베이스 연결
```php
$pdo = require __DIR__ . '/database/connect.php';
```
모든 쿼리에 PDO prepared statements 사용.

## 주요 데이터베이스 테이블

- `users` - 고객 계정 (role: CUSTOMER, ADMIN)
- `managers` - 매니저 프로필 (users 테이블과 별도)
- `service_requests` - 서비스 요청, 상태: PENDING → CONFIRMED → MATCHING → COMPLETED
- `applications` - 매니저의 요청 지원 내역
- `bookings` - 확정된 서비스 예약
- `payments` - 토스페이먼츠 연동 결제 기록
- `manager_device_tokens` - 푸시 알림 토큰

## API 패턴

### 공개 엔드포인트
- `POST /api/auth/login` - 고객 로그인
- `POST /api/manager/login` - 매니저 로그인 (JWT 반환)

### 보호된 매니저 엔드포인트 (JWT Bearer Token 필요)
- `GET /api/manager/me` - 현재 매니저 정보
- `GET /api/manager/requests` - 이용 가능한 요청 목록
- `POST /api/manager/apply` - 요청에 지원
- `POST /api/manager/register-token` - 푸시 토큰 등록

### 관리자 엔드포인트
- `POST /api/admin/confirm-designated-matching` - 매니저 배정 확인

## 서비스 흐름

1. 고객이 `/requests/new`에서 요청 생성 (3단계 폼)
2. `/payment/success`에서 토스 결제 처리
3. 요청 상태: PENDING → CONFIRMED, 매니저들에게 푸시 전송
4. 매니저가 앱에서 지원, 상태 → MATCHING
5. 관리자 확인, 예약 생성
6. 서비스 완료, 상태 → COMPLETED

## 설정 (config/app.php)

주요 상수:
- `DB_*` - 데이터베이스 연결
- `TOSS_CLIENT_KEY`, `TOSS_SECRET_KEY` - 결제
- `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` - Web Push
- `API_JWT_SECRET` - JWT 서명
- `API_CORS_ORIGINS` - 허용된 오리진 배열
- `VWORLD_API_KEY` - 한국 주소 검색

## 언어

코드는 영어, UI와 주석은 주로 한국어.
