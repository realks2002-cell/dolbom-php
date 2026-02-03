-- service_requests 테이블에 guest_name, guest_phone 컬럼 추가
-- phpMyAdmin에서 실행하세요

ALTER TABLE service_requests 
ADD COLUMN guest_name VARCHAR(100) NULL COMMENT '비회원 이름' AFTER customer_id,
ADD COLUMN guest_phone VARCHAR(20) NULL COMMENT '비회원 전화번호' AFTER guest_name;

-- 확인
DESCRIBE service_requests;
