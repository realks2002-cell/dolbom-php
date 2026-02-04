-- service_requests 테이블에 비회원 정보 컬럼 추가 및 customer_id NULL 허용
-- 실행일: 2026-02-04

-- 1. customer_id를 NULL 허용으로 변경 (비회원 지원)
ALTER TABLE `service_requests` 
MODIFY COLUMN `customer_id` CHAR(36) NULL;

-- 2. 비회원 정보 컬럼 추가
ALTER TABLE `service_requests`
ADD COLUMN `guest_name` VARCHAR(100) NULL COMMENT '비회원 이름' AFTER `customer_id`,
ADD COLUMN `guest_phone` VARCHAR(20) NULL COMMENT '비회원 전화번호' AFTER `guest_name`,
ADD COLUMN `guest_address` VARCHAR(255) NULL COMMENT '비회원 주소' AFTER `guest_phone`,
ADD COLUMN `guest_address_detail` VARCHAR(255) NULL COMMENT '비회원 상세 주소' AFTER `guest_address`;

-- 3. 외래키 제약조건 수정 (NULL 허용)
-- 기존 외래키 삭제
ALTER TABLE `service_requests`
DROP FOREIGN KEY `fk_service_requests_customer`;

-- NULL 허용 외래키로 재생성
ALTER TABLE `service_requests`
ADD CONSTRAINT `fk_service_requests_customer` 
FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- 4. 인덱스 추가 (비회원 검색용)
ALTER TABLE `service_requests`
ADD INDEX `idx_service_requests_guest_phone` (`guest_phone`);
