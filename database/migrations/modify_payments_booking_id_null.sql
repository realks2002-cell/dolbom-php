-- payments 테이블의 booking_id를 NULL 허용으로 변경
-- (결제 시점에는 아직 booking이 생성되지 않으므로)

ALTER TABLE `payments` 
MODIFY COLUMN `booking_id` CHAR(36) NULL;
