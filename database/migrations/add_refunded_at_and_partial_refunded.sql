-- payments 테이블에 refunded_at 필드 추가 및 PARTIAL_REFUNDED 상태 추가
-- 환불 일시를 저장하고 부분 환불을 지원하기 위함

ALTER TABLE `payments` 
ADD COLUMN `refunded_at` TIMESTAMP NULL DEFAULT NULL AFTER `refund_reason`,
MODIFY COLUMN `status` ENUM('PENDING','SUCCESS','FAILED','CANCELLED','REFUNDED','PARTIAL_REFUNDED') NOT NULL DEFAULT 'PENDING';

-- refunded_at 인덱스 추가 (환불 일시로 조회 시 성능 향상)
ALTER TABLE `payments`
ADD KEY `idx_payments_refunded_at` (`refunded_at`);
