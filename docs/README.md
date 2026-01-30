# Hangbok77 프로젝트 구조 가이드

병원 동행 및 돌봄 서비스 매칭 플랫폼 - 전체 파일 및 폴더 구조 상세 설명

---

## 📁 프로젝트 구조 개요

```
dolbom_php/
├── api/                    # REST API 엔드포인트
├── assets/                 # 정적 리소스 (CSS, JS)
├── components/             # 재사용 가능한 PHP 컴포넌트
├── config/                 # 설정 파일
├── database/               # 데이터베이스 스키마 및 마이그레이션
├── docs/                   # 문서
├── includes/               # 공통 헬퍼 함수 및 라이브러리
├── manager-app/            # 매니저용 Vue.js 앱 (하이브리드 앱)
├── pages/                  # 페이지 컨트롤러 (MVC의 View)
├── storage/                # 세션 및 임시 파일 저장소
├── tosspayments/           # 토스페이먼츠 SDK
├── .htaccess               # Apache URL 리라이트 규칙
├── admin.php               # 관리자 전용 로그인 페이지
├── index.php               # 메인 라우터 (Front Controller)
└── README.md               # 이 파일
```

---

## 📂 상세 디렉토리 구조

### 🔌 `/api` - REST API 엔드포인트

API 요청을 처리하는 엔드포인트들입니다. JSON 응답을 반환합니다.

#### `/api/address-search.php`
- **역할**: VWorld API를 통한 주소 검색
- **메서드**: GET
- **파라미터**: `keyword` (검색어)
- **반환**: 주소 검색 결과 JSON

#### `/api/address-suggest.php`
- **역할**: 주소 자동완성 (VWorld API)
- **메서드**: GET
- **파라미터**: `keyword` (검색어)
- **반환**: 주소 제안 목록 JSON

#### `/api/auth/login.php`
- **역할**: 매니저 앱용 JWT 인증 로그인
- **메서드**: POST
- **파라미터**: `email`, `password`
- **반환**: JWT 토큰 및 사용자 정보

#### `/api/bookings/cancel.php`
- **역할**: 고객 예약 취소 및 환불 처리
- **메서드**: POST
- **파라미터**: `booking_id`, `reason`
- **기능**: 
  - 예약 상태를 `CANCELLED`로 변경
  - 토스페이먼츠 환불 API 호출
  - `payments` 테이블에 환불 정보 저장

#### `/api/manager/me.php`
- **역할**: 현재 로그인한 매니저 정보 조회
- **메서드**: GET
- **인증**: Bearer Token (JWT) 필수
- **반환**: 매니저 정보 JSON

#### `/api/manager/requests.php`
- **역할**: 매니저가 볼 수 있는 서비스 요청 목록
- **메서드**: GET
- **인증**: Bearer Token 필수
- **반환**: 매칭 가능한 서비스 요청 목록

#### `/api/manager/applications.php`
- **역할**: 매니저의 지원 내역 조회
- **메서드**: GET
- **인증**: Bearer Token 필수
- **반환**: 매니저가 지원한 요청 목록

#### `/api/manager/apply.php`
- **역할**: 매니저가 서비스 요청에 지원
- **메서드**: POST
- **파라미터**: `request_id`
- **기능**:
  - `applications` 테이블에 지원 정보 저장
  - `service_requests.status`를 `CONFIRMED` → `MATCHING`으로 변경

#### `/api/manager/schedule.php`
- **역할**: 매니저의 일정 조회
- **메서드**: GET
- **인증**: Bearer Token 필수
- **반환**: 매칭 완료된 서비스 일정 목록

#### `/api/manager/register-token.php` ⭐ **푸시 알림**
- **역할**: 매니저 디바이스 FCM 토큰 등록/업데이트
- **메서드**: POST
- **파라미터**: `device_token`, `platform`, `app_version`
- **기능**: `manager_device_tokens` 테이블에 토큰 저장
- **인증**: Bearer Token 필수

#### `/api/payments/refund.php`
- **역할**: 관리자 환불 처리
- **메서드**: POST
- **파라미터**: `payment_id`, `reason`
- **기능**: 토스페이먼츠 환불 API 호출 및 DB 업데이트

#### `/api/requests/save-temp.php`
- **역할**: 서비스 요청 임시 저장 (결제 전)
- **메서드**: POST
- **파라미터**: 서비스 요청 정보 전체
- **기능**: `service_requests` 테이블에 `PENDING` 상태로 저장

#### `/api/test/send-push.php` ⭐ **푸시 알림 테스트**
- **역할**: 푸시 알림 테스트용 엔드포인트
- **메서드**: POST
- **파라미터**: `device_token`, `title`, `body`
- **사용**: 로컬 개발 환경에서 푸시 알림 테스트

#### `/api/cors.php`
- **역할**: CORS 헤더 설정
- **사용**: 모든 API 엔드포인트에서 `require_once`로 포함
- **설정**: `config/app.php`의 `API_CORS_ORIGINS` 상수 사용

#### `/api/middleware/auth.php`
- **역할**: JWT 토큰 인증 미들웨어
- **사용**: 매니저 앱 API에서 `require_once`로 포함
- **기능**: 
  - Authorization 헤더에서 토큰 추출
  - JWT 검증
  - `$apiUser` 전역 변수에 사용자 정보 설정

---

### 🎨 `/assets` - 정적 리소스

#### `/assets/css/custom.css`
- **역할**: 커스텀 CSS 스타일
- **사용**: Tailwind CSS로 처리되지 않는 특수 스타일

#### `/assets/js/main.js`
- **역할**: 공통 JavaScript 함수
- **사용**: 여러 페이지에서 공통으로 사용되는 JS 코드

---

### 🧩 `/components` - 재사용 가능한 PHP 컴포넌트

공통 UI 컴포넌트를 모아둔 폴더입니다. `include` 또는 `require`로 불러와 사용합니다.

#### `/components/header.php`
- **역할**: 메인 사이트 헤더 (네비게이션 바)
- **포함**: 로고, 메뉴, 로그인/회원가입 링크
- **사용**: 고객용 페이지에서 사용

#### `/components/footer.php`
- **역할**: 사이트 푸터
- **포함**: 저작권 정보, 연락처 등

#### `/components/nav.php`
- **역할**: 네비게이션 메뉴 컴포넌트
- **사용**: 헤더 내부에서 사용

#### `/components/layout.php`
- **역할**: 기본 레이아웃 래퍼
- **포함**: HTML 기본 구조, 헤더/푸터 포함

#### `/components/admin-layout.php`
- **역할**: 관리자 페이지 레이아웃
- **포함**: 사이드바 메뉴, 관리자 전용 네비게이션
- **메뉴 항목**:
  - 회원 관리
  - 매니저 관리
  - 예약요청 및 매칭 현황
  - 결제 내역 조회
  - 결제 취소
  - 환불정보
  - 일/월 매출 집계

---

### ⚙️ `/config` - 설정 파일

#### `/config/app.php`
- **역할**: 앱 전역 설정
- **포함**:
  - `APP_DEBUG`: 디버그 모드 설정
  - `BASE_URL`: 기본 URL 설정
  - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`: 데이터베이스 연결 정보
  - `VWORLD_API_KEY`: VWorld 주소 검색 API 키
  - `TOSS_CLIENT_KEY`, `TOSS_SECRET_KEY`: 토스페이먼츠 키
  - `FCM_SERVER_KEY`: Firebase Cloud Messaging 서버 키 ⭐
  - `API_JWT_SECRET`: JWT 토큰 서명 키
  - `API_CORS_ORIGINS`: CORS 허용 오리진

---

### 🗄️ `/database` - 데이터베이스

#### `/database/schema.sql`
- **역할**: 전체 데이터베이스 스키마 정의
- **테이블**:
  - `users`: 회원 정보
  - `managers`: 매니저 정보
  - `admins`: 관리자 정보
  - `service_requests`: 서비스 요청
  - `applications`: 매니저 지원 내역
  - `payments`: 결제 정보
  - `manager_device_tokens`: 매니저 FCM 토큰 ⭐
  - 기타...

#### `/database/connect.php`
- **역할**: PDO 데이터베이스 연결 객체 반환
- **사용**: `$pdo = require 'database/connect.php';`
- **설정**: `config/app.php`의 DB 상수 사용

#### `/database/migrations/` - 마이그레이션 파일들

데이터베이스 스키마 변경 및 데이터 마이그레이션 스크립트들입니다.

**주요 마이그레이션**:
- `create_manager_device_tokens.sql` ⭐: FCM 토큰 테이블 생성
- `create_manager_device_tokens.php`: 마이그레이션 실행 파일
- `add_phone_to_service_requests.sql`: 서비스 요청에 전화번호 필드 추가
- `add_refunded_at_and_partial_refunded.sql`: 환불 정보 필드 추가
- `add_specialty_to_managers.sql`: 매니저 특기 필드 추가
- `generate_random_service_requests.php`: 테스트용 랜덤 데이터 생성
- 기타...

---

### 📚 `/docs` - 문서

#### `/docs/prd.md`
- **역할**: 프로젝트 요구사항 명세서 (PRD)
- **포함**: 기능 명세, 비즈니스 로직, API 명세 등

#### `/docs/matching_status_tables.md`
- **역할**: 매칭 상태 관련 테이블 구조 설명
- **포함**: `service_requests`, `applications`, `payments` 테이블 관계

---

### 🔧 `/includes` - 공통 헬퍼 함수

#### `/includes/helpers.php`
- **역할**: 공통 유틸리티 함수
- **함수**:
  - `init_session()`: 세션 초기화
  - `uuid4()`: UUID v4 생성
  - `redirect($path)`: 리다이렉트
  - `require_admin()`: 관리자 권한 체크

#### `/includes/auth.php`
- **역할**: 인증 관련 함수
- **기능**: 
  - 세션 기반 인증 체크
  - `$currentUser`, `$userRole` 전역 변수 설정

#### `/includes/fcm.php` ⭐ **푸시 알림**
- **역할**: FCM 푸시 알림 전송 헬퍼 함수
- **함수**:
  - `send_fcm_push($tokens, $title, $body, $data)`: 단일/다중 토큰에 푸시 전송
  - `send_push_to_managers($pdo, $title, $body, $data, $managerIds)`: 매니저들에게 일괄 전송

#### `/includes/jwt.php`
- **역할**: JWT 토큰 생성 및 검증
- **기능**: 매니저 앱 API 인증에 사용

---

### 📱 `/manager-app` - 매니저용 Vue.js 앱

하이브리드 앱으로 빌드될 매니저 전용 앱입니다.

#### 구조
```
manager-app/
├── src/
│   ├── api.js              # API 클라이언트 함수
│   ├── router.js           # Vue Router 설정
│   ├── App.vue             # 루트 컴포넌트
│   ├── views/
│   │   ├── Layout.vue      # 레이아웃 (헤더, 네비게이션)
│   │   ├── Login.vue       # 로그인 페이지
│   │   ├── Requests.vue    # 새 요청 목록
│   │   ├── Applications.vue # 지원 현황
│   │   ├── Schedule.vue    # 내 일정
│   │   └── Settings.vue    # 설정 (푸시 알림) ⭐
│   └── ...
├── package.json            # npm 의존성
├── vite.config.js         # Vite 빌드 설정
└── vercel.json            # Vercel 배포 설정
```

#### 주요 파일

**`/manager-app/src/api.js`**
- API 호출 함수들 (`fetchMe`, `fetchRequests`, `registerDeviceToken` 등)
- JWT 토큰 관리 (`setToken`, `getToken`)
- 로컬 스토리지 사용자 정보 관리

**`/manager-app/src/router.js`**
- Vue Router 라우트 정의
- 인증 가드 (`beforeEach`)
- 라우트: `/login`, `/requests`, `/applications`, `/schedule`, `/settings`

**`/manager-app/src/views/Layout.vue`** ⭐
- 자동 FCM 토큰 등록 로직
- Capacitor Push Notifications 플러그인 연동
- 헤더 네비게이션 (새 요청, 지원 현황, 내 일정, 설정)

**`/manager-app/src/views/Settings.vue`** ⭐
- 푸시 알림 설정 페이지
- 알림 활성화/비활성화 토글
- 토큰 등록 상태 표시

---

### 📄 `/pages` - 페이지 컨트롤러

MVC 패턴의 View 역할을 하는 페이지 파일들입니다.

#### `/pages/index.php`
- **역할**: 메인 홈페이지
- **기능**: 서비스 소개, 주요 기능 안내

#### `/pages/auth/` - 인증 페이지
- `login.php`: 고객 로그인 페이지
- `logout.php`: 로그아웃 처리
- `signup.php`: 고객 회원가입 페이지

#### `/pages/requests/` - 서비스 요청
- `new.php`: 서비스 요청 작성 (3단계 폼)
  - Step 1: 서비스 유형 선택
  - Step 2: 일시 및 위치 선택 (VWorld 주소 검색)
  - Step 3: 상세 정보 입력 (전화번호 포함)
- `detail.php`: 서비스 요청 상세 보기

#### `/pages/bookings/` - 예약 관리
- `index.php`: 고객의 예약 목록 (탭: upcoming, completed, cancelled)
- `review.php`: 서비스 후기 작성

#### `/pages/payment/` - 결제
- `success.php`: 결제 성공 페이지 ⭐
  - 토스페이먼츠 결제 승인 처리
  - `service_requests` 상태를 `CONFIRMED`로 변경
  - `payments` 테이블에 결제 정보 저장
  - **매니저들에게 푸시 알림 전송** ⭐
- `fail.php`: 결제 실패 페이지
- `register-card.php`: 카드 등록 페이지

#### `/pages/manager/` - 매니저 페이지
- `login.php`: 매니저 로그인 (전화번호 + 비밀번호)
- `signup.php`: 매니저 회원가입
- `logout.php`: 매니저 로그아웃
- `dashboard.php`: 매니저 대시보드
  - 탭: 매칭 가능한 요청, 내 매칭 현황
  - 요청 클릭 시 모달 표시
  - "지원하기" 버튼으로 지원 처리
- `recruit.php`: 매니저 지원 페이지 (공개)
- `profile.php`: 매니저 프로필 관리
- `requests.php`: 서비스 요청 목록
- `applications.php`: 지원 내역
- `schedule.php`: 일정 관리
- `earnings.php`: 수익 내역

#### `/pages/admin/` - 관리자 페이지
- `index.php`: 관리자 대시보드
- `users.php`: 회원 관리
- `managers.php`: 매니저 관리
- `requests.php`: 예약요청 및 매칭 현황
  - 필터: 전체, 매칭완료, 매칭대기, 취소, 서비스 완료
  - 컬럼: 요청일시, 근무일시, 고객, 서비스, 위치, 지원 매니저, 지원일시, 상태, 금액
- `payments.php`: 결제 내역 조회
- `refunds.php`: 결제 취소 처리
- `refund-info.php`: 환불정보 상세 조회
- `revenue.php`: 일/월 매출 집계

#### `/pages/test/` - 테스트 페이지
- `push-notification.php` ⭐: 푸시 알림 테스트 페이지
  - 디바이스 토큰 입력
  - 알림 제목/내용 입력
  - 테스트 푸시 전송

---

### 💾 `/storage` - 저장소

#### `/storage/sessions/`
- **역할**: PHP 세션 파일 저장 디렉토리
- **이유**: XAMPP의 기본 tmp 디렉토리 권한 이슈 회피
- **설정**: `includes/helpers.php`의 `init_session()`에서 사용

---

### 💳 `/tosspayments` - 토스페이먼츠 SDK

토스페이먼츠 공식 SDK 파일들이 포함된 디렉토리입니다.

---

## 🔑 핵심 파일 설명

### `index.php` - 메인 라우터 (Front Controller)
- **역할**: 모든 요청의 진입점
- **기능**: 
  - `route` GET 파라미터로 라우팅
  - URL → 파일 매핑
  - 404 처리

### `admin.php` - 관리자 전용 로그인
- **역할**: 관리자 전용 로그인 페이지 (루트에 위치)
- **기능**: `admins` 테이블 인증

### `.htaccess` - Apache 설정
- **역할**: URL 리라이트 규칙
- **기능**: `/route` 형태의 깔끔한 URL로 변환

---

## 🔔 푸시 알림 시스템 구조 ⭐

### 데이터베이스
- **테이블**: `manager_device_tokens`
  - `manager_id`: 매니저 ID
  - `device_token`: FCM 디바이스 토큰
  - `platform`: 플랫폼 (android, ios, web)
  - `is_active`: 활성화 여부

### 백엔드 (PHP)
1. **`includes/fcm.php`**: 푸시 전송 헬퍼 함수
2. **`api/manager/register-token.php`**: 토큰 등록 API
3. **`pages/payment/success.php`**: 결제 완료 시 푸시 전송
4. **`config/app.php`**: FCM_SERVER_KEY 설정

### 프론트엔드 (Vue.js)
1. **`manager-app/src/views/Layout.vue`**: 자동 토큰 등록
2. **`manager-app/src/views/Settings.vue`**: 알림 설정 페이지
3. **`manager-app/src/api.js`**: `registerDeviceToken` 함수

### 테스트
- **`api/test/send-push.php`**: 테스트 API
- **`pages/test/push-notification.php`**: 테스트 페이지

---

## 🚀 실행 방법

### 1. 데이터베이스 설정
```sql
-- schema.sql 파일을 phpMyAdmin에서 Import
-- 또는 마이그레이션 파일들을 순서대로 실행
```

### 2. 설정 파일 수정
```php
// config/app.php
define('FCM_SERVER_KEY', '여기에_FCM_서버_키');
```

### 3. 웹 서버 실행
```bash
# XAMPP 사용 시
# Apache와 MySQL 시작

# 또는 PHP 내장 서버
php -S localhost:8000
```

### 4. 매니저 앱 실행
```bash
cd manager-app
npm install
npm run dev
```

---

## 📝 주요 기능 흐름

### 서비스 요청 → 결제 → 푸시 알림
1. 고객이 `/requests/new`에서 서비스 요청 작성
2. 결제 완료 (`/payment/success`)
3. `pages/payment/success.php`에서:
   - 결제 정보 저장
   - `service_requests.status` → `CONFIRMED`
   - `send_push_to_managers()` 호출 ⭐
4. 매니저 앱에서 푸시 알림 수신

### 매니저 지원 흐름
1. 매니저가 `/manager/dashboard`에서 요청 확인
2. 요청 클릭 → 모달 표시
3. "지원하기" 클릭 → `/api/manager/apply` 호출
4. `applications` 테이블에 저장
5. `service_requests.status` → `MATCHING`

---

## 🔒 보안 고려사항

- 세션 기반 인증: 고객/관리자
- JWT 토큰 인증: 매니저 앱 API
- 비밀번호 해싱: `password_hash()` 사용
- SQL 인젝션 방지: PDO Prepared Statements
- XSS 방지: `htmlspecialchars()` 사용
- CORS 설정: `api/cors.php`

---

## 📦 의존성

### PHP
- PHP 7.4+
- PDO (MySQL/MariaDB)
- cURL (FCM 전송용)
- JSON 확장

### 매니저 앱
- Node.js 16+
- Vue.js 3
- Vue Router 4
- Tailwind CSS
- Vite

### 하이브리드 앱 (선택)
- Capacitor
- @capacitor/push-notifications

---

## 🧪 테스트

### 푸시 알림 테스트
1. `http://localhost:8000/test/push-notification` 접속
2. DB에서 토큰 확인: `SELECT * FROM manager_device_tokens WHERE is_active = 1`
3. 토큰 입력 후 테스트 전송

### API 테스트
```bash
# cURL 예시
curl -X POST http://localhost:8000/api/test/send-push \
  -H "Content-Type: application/json" \
  -d '{"device_token":"토큰", "title":"테스트", "body":"메시지"}'
```

---

## 📞 문의 및 지원

프로젝트 관련 문의사항은 개발팀에 연락하세요.

---

**마지막 업데이트**: 2026-01-29
