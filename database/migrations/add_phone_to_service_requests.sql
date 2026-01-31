-- service_requests 테이블에 phone 필드 추가
-- 서비스 요청 시 고객 연락처 저장

ALTER TABLE `service_requests` 
ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL COMMENT '고객 연락처' AFTER `address_detail`;

-- phone 인덱스 추가 (연락처로 검색 시 성능 향상)
ALTER TABLE `service_requests`
ADD KEY `idx_service_requests_phone` (`phone`);
