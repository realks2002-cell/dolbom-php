# 프론트엔드 페이지 목록

브라우저에서 직접 접근 가능한 페이지 파일들의 경로와 설명입니다.

---

## 🏠 공개 페이지 (인증 불필요)

### 메인 홈페이지
- **경로**: `/` 또는 `/index`
- **파일**: `pages/index.php`
- **설명**: 서비스 소개 및 메인 홈페이지

### 매니저 지원 (공개)
- **경로**: `/manager/recruit`
- **파일**: `pages/manager/recruit.php`
- **설명**: 매니저 모집 공고 페이지 (비로그인 접근 가능)

---

## 👤 고객 페이지

### 인증
- **경로**: `/auth/login`
- **파일**: `pages/auth/login.php`
- **설명**: 고객 로그인 페이지

- **경로**: `/auth/signup`
- **파일**: `pages/auth/signup.php`
- **설명**: 고객 회원가입 페이지

- **경로**: `/auth/logout`
- **파일**: `pages/auth/logout.php`
- **설명**: 로그아웃 처리 (리다이렉트)

### 서비스 요청
- **경로**: `/requests/new`
- **파일**: `pages/requests/new.php`
- **설명**: 서비스 요청 작성 페이지 (3단계 폼)
  - Step 1: 서비스 유형 선택
  - Step 2: 일시 및 위치 선택 (VWorld 주소 검색)
  - Step 3: 상세 정보 입력 (전화번호 포함)

- **경로**: `/requests/detail`
- **파일**: `pages/requests/detail.php`
- **설명**: 서비스 요청 상세 보기 페이지

### 예약 관리
- **경로**: `/bookings`
- **파일**: `pages/bookings/index.php`
- **설명**: 고객의 예약 목록 페이지
  - 탭: upcoming (예정), completed (완료), cancelled (취소)
  - 상태 표시: 매칭 완료, 매칭 대기중 등

- **경로**: `/bookings/review`
- **파일**: `pages/bookings/review.php`
- **설명**: 서비스 후기 작성 페이지

### 결제
- **경로**: `/payment/success`
- **파일**: `pages/payment/success.php`
- **설명**: 결제 성공 페이지
  - 토스페이먼츠 결제 승인 처리
  - 서비스 요청 상태를 `CONFIRMED`로 변경
  - 결제 정보 저장
  - **매니저들에게 푸시 알림 전송** ⭐

- **경로**: `/payment/fail`
- **파일**: `pages/payment/fail.php`
- **설명**: 결제 실패 페이지

- **경로**: `/payment/register-card`
- **파일**: `pages/payment/register-card.php`
- **설명**: 카드 등록 페이지 (빌링키 발급)

---

## 👨‍💼 매니저 페이지

### 인증
- **경로**: `/manager/login`
- **파일**: `pages/manager/login.php`
- **설명**: 매니저 로그인 페이지 (전화번호 + 비밀번호)

- **경로**: `/manager/signup`
- **파일**: `pages/manager/signup.php`
- **설명**: 매니저 회원가입 페이지
  - 개인정보 이용 동의 체크박스 포함

- **경로**: `/manager/logout`
- **파일**: `pages/manager/logout.php`
- **설명**: 매니저 로그아웃 처리

### 대시보드 및 관리
- **경로**: `/manager/dashboard`
- **파일**: `pages/manager/dashboard.php`
- **설명**: 매니저 대시보드 (메인 페이지)
  - 탭: 매칭 가능한 요청, 내 매칭 현황
  - 요청 클릭 시 모달 표시
  - "지원하기" 버튼으로 지원 처리
  - 소요시간 표시: 시간/분 형식 (예: 1시간 30분)
  - 근무일시 표시 (서비스 일시)

- **경로**: `/manager/profile`
- **파일**: `pages/manager/profile.php`
- **설명**: 매니저 프로필 관리 페이지

- **경로**: `/manager/requests`
- **파일**: `pages/manager/requests.php`
- **설명**: 서비스 요청 목록 페이지

- **경로**: `/manager/applications`
- **파일**: `pages/manager/applications.php`
- **설명**: 매니저의 지원 내역 페이지

- **경로**: `/manager/schedule`
- **파일**: `pages/manager/schedule.php`
- **설명**: 매니저 일정 관리 페이지

- **경로**: `/manager/earnings`
- **파일**: `pages/manager/earnings.php`
- **설명**: 매니저 수익 내역 페이지

---

## 🔐 관리자 페이지

### 관리자 로그인 (루트)
- **경로**: `/admin.php` (직접 접근)
- **파일**: `admin.php`
- **설명**: 관리자 전용 로그인 페이지 (루트 폴더에 위치)
  - `admins` 테이블 인증

### 관리자 대시보드
- **경로**: `/admin`
- **파일**: `pages/admin/index.php`
- **설명**: 관리자 대시보드 (메인 페이지)
  - 사이드바 메뉴 포함

### 회원 관리
- **경로**: `/admin/users`
- **파일**: `pages/admin/users.php`
- **설명**: 회원 관리 페이지
  - 회원 목록 조회
  - 회원 정보 수정/삭제

### 매니저 관리
- **경로**: `/admin/managers`
- **파일**: `pages/admin/managers.php`
- **설명**: 매니저 관리 페이지
  - 매니저 목록 조회 (`managers` 테이블)
  - 매니저 정보 수정
  - 특기(specialty) 필드 표시

### 예약 및 매칭 관리
- **경로**: `/admin/requests`
- **파일**: `pages/admin/requests.php`
- **설명**: 예약요청 및 매칭 현황 페이지
  - 필터: 전체, 매칭완료, 매칭대기(결제완료), 취소, 서비스 완료
  - 컬럼: 요청일시, 근무일시, 고객, 서비스, 위치, 지원 매니저, 지원일시, 상태, 금액
  - 상태 표시: 매칭완료, 매칭대기(결제완료), 취소, 서비스 완료

### 결제 관리
- **경로**: `/admin/payments`
- **파일**: `pages/admin/payments.php`
- **설명**: 결제 내역 조회 페이지

- **경로**: `/admin/refunds`
- **파일**: `pages/admin/refunds.php`
- **설명**: 결제 취소 처리 페이지
  - 환불 처리 및 `refunded_at` 필드 업데이트

- **경로**: `/admin/refund-info`
- **파일**: `pages/admin/refund-info.php`
- **설명**: 환불정보 상세 조회 페이지
  - 환불된 결제 내역 조회
  - 필터: 전체, 전체환불, 부분환불

### 매출 관리
- **경로**: `/admin/revenue`
- **파일**: `pages/admin/revenue.php`
- **설명**: 일/월 매출 집계 페이지

---

## 🧪 테스트 페이지

### 푸시 알림 테스트
- **경로**: `/test/push-notification`
- **파일**: `pages/test/push-notification.php`
- **설명**: 푸시 알림 테스트 페이지
  - 디바이스 토큰 입력
  - 알림 제목/내용 입력
  - 테스트 푸시 전송
  - 결과 확인

---

## 📋 페이지 접근 권한 요약

| 페이지 그룹 | 인증 필요 | 권한 |
|------------|---------|------|
| 공개 페이지 | ❌ | 없음 |
| 고객 페이지 | ✅ | CUSTOMER 역할 |
| 매니저 페이지 | ✅ | 매니저 세션 (`$_SESSION['manager_id']`) |
| 관리자 페이지 | ✅ | 관리자 세션 (`$_SESSION['admin_db_id']`) |
| 테스트 페이지 | ❌ | 개발 환경에서만 사용 권장 |

---

## 🔗 URL 구조

모든 페이지는 `index.php`를 통해 라우팅됩니다.

**기본 URL 형식**:
```
http://localhost:8000/?route=페이지경로
```

**실제 접근 URL** (`.htaccess` 리라이트 후):
```
http://localhost:8000/페이지경로
```

**예시**:
- `http://localhost:8000/` → `pages/index.php`
- `http://localhost:8000/auth/login` → `pages/auth/login.php`
- `http://localhost:8000/manager/dashboard` → `pages/manager/dashboard.php`
- `http://localhost:8000/admin` → `pages/admin/index.php`

---

## 📝 참고사항

- 모든 페이지는 `index.php`의 라우트 맵(`$map`)에 정의되어 있습니다.
- 인증이 필요한 페이지는 각 페이지 파일 내에서 `require_admin()` 또는 세션 체크를 수행합니다.
- 404 오류는 `index.php`에서 처리됩니다.
- 관리자 로그인만 `admin.php`로 직접 접근합니다.

---

**마지막 업데이트**: 2026-01-29
