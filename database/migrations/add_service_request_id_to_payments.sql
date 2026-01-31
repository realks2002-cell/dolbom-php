-- payments 테이블에 service_request_id 컬럼 추가
-- 결제 시점에는 아직 booking이 생성되지 않으므로 service_request_id로 연결

ALTER TABLE `payments` 
ADD COLUMN `service_request_id` CHAR(36) NULL AFTER `booking_id`,
ADD KEY `idx_payments_service_request` (`service_request_id`),
ADD CONSTRAINT `fk_payments_service_request` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE;

-- booking_id를 NULL 허용으로 변경 (결제 시점에는 NULL 가능)
ALTER TABLE `payments` 
MODIFY COLUMN `booking_id` CHAR(36) NULL;
