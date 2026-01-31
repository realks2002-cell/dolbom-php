# 매칭대기(결제완료) 상태 - 테이블 및 필드 정보

## 주요 테이블

### 1. `service_requests` 테이블
**역할**: 서비스 요청 정보 및 상태 관리

**주요 필드**:
- `id` (CHAR(36)): 서비스 요청 고유 ID (Primary Key)
- `customer_id` (CHAR(36)): 고객 ID (Foreign Key → users.id)
- `status` (ENUM): 요청 상태
  - `'PENDING'` → 대기중
  - `'MATCHING'` → 매칭완료
  - `'CONFIRMED'` → **매칭대기(결제완료)** ⭐
  - `'COMPLETED'` → 완료
  - `'CANCELLED'` → 취소
- `estimated_price` (INT UNSIGNED): 예상 금액
- `service_type` (VARCHAR(50)): 서비스 유형
- `service_date` (DATE): 서비스 날짜
- `start_time` (TIME): 시작 시간
- `address` (VARCHAR(255)): 주소
- `created_at` (TIMESTAMP): 요청 생성일시
- `updated_at` (TIMESTAMP): 최종 수정일시

**인덱스**:
- `idx_service_requests_status`: status 필드 인덱스 (상태별 조회 성능 향상)

---

### 2. `payments` 테이블
**역할**: 결제 정보 저장

**주요 필드**:
- `id` (CHAR(36)): 결제 고유 ID (Primary Key)
- `service_request_id` (CHAR(36) NULL): 서비스 요청 ID (Foreign Key → service_requests.id)
- `booking_id` (CHAR(36) NULL): 예약 ID (Foreign Key → bookings.id)
- `customer_id` (CHAR(36)): 고객 ID (Foreign Key → users.id)
- `amount` (INT UNSIGNED): 결제 금액
- `payment_method` (VARCHAR(50)): 결제 수단
- `payment_key` (VARCHAR(255) NULL): 토스페이먼츠 결제 키
- `status` (ENUM): 결제 상태
  - `'PENDING'` → 대기중
  - `'SUCCESS'` → **결제 완료** ⭐
  - `'FAILED'` → 실패
  - `'CANCELLED'` → 취소
  - `'REFUNDED'` → 환불됨
- `paid_at` (TIMESTAMP NULL): 결제 완료 일시
- `created_at` (TIMESTAMP): 결제 생성일시

**인덱스**:
- `idx_payments_service_request`: service_request_id 인덱스
- `idx_payments_status`: status 필드 인덱스

---

### 3. `applications` 테이블
**역할**: 매니저 지원 정보

**주요 필드**:
- `id` (CHAR(36)): 지원 고유 ID (Primary Key)
- `request_id` (CHAR(36)): 서비스 요청 ID (Foreign Key → service_requests.id)
- `manager_id` (INT UNSIGNED): 매니저 ID (Foreign Key → managers.id)
- `status` (ENUM): 지원 상태
  - `'PENDING'` → 대기중
  - `'ACCEPTED'` → 수락됨
  - `'REJECTED'` → 거절됨
- `message` (VARCHAR(500) NULL): 지원 메시지
- `created_at` (TIMESTAMP): 지원 일시

**제약조건**:
- `UNIQUE(request_id, manager_id)`: 동일 요청에 중복 지원 방지

---

## 상태 흐름

```
1. 고객 서비스 요청
   → service_requests.status = 'PENDING'

2. 고객 결제 완료
   → payments.status = 'SUCCESS'
   → payments.service_request_id = service_requests.id
   → service_requests.status = 'CONFIRMED' ⭐ (매칭대기(결제완료))

3. 매니저 지원
   → applications 테이블에 지원 정보 저장
   → service_requests.status = 'MATCHING' (매칭완료)

4. 고객이 매니저 선택
   → applications.status = 'ACCEPTED'
   → bookings 테이블에 예약 생성
```

---

## 조회 쿼리 예시

### 매칭대기(결제완료) 상태인 요청 조회
```sql
SELECT sr.*, u.name as customer_name, p.amount, p.paid_at
FROM service_requests sr
JOIN users u ON u.id = sr.customer_id
LEFT JOIN payments p ON p.service_request_id = sr.id AND p.status = 'SUCCESS'
WHERE sr.status = 'CONFIRMED'
ORDER BY sr.created_at DESC;
```

### 결제 완료 여부 확인
```sql
SELECT sr.id, sr.status, p.status as payment_status, p.amount
FROM service_requests sr
LEFT JOIN payments p ON p.service_request_id = sr.id
WHERE sr.id = '요청ID';
```
