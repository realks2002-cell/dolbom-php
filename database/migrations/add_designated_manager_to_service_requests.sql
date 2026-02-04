-- Migration: service_requests 테이블에 designated_manager_id 컬럼 추가
-- 목적: 고객이 직접 지정한 도우미 정보를 저장
-- 실행: phpMyAdmin 또는 mysql 클라이언트에서 실행

ALTER TABLE `service_requests`
  ADD COLUMN `designated_manager_id` CHAR(36) DEFAULT NULL COMMENT '고객이 지정한 도우미 ID (선택사항)' AFTER `customer_id`,
  ADD INDEX `idx_service_requests_designated_manager` (`designated_manager_id`);

-- Foreign Key 추가 (managers 테이블 참조)
ALTER TABLE `service_requests`
  ADD CONSTRAINT `fk_service_requests_designated_manager` 
  FOREIGN KEY (`designated_manager_id`) 
  REFERENCES `managers` (`id`) 
  ON DELETE SET NULL;
