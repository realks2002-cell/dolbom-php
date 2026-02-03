-- 서버 DB 스키마 전체 수정
-- phpMyAdmin에서 한 줄씩 실행하세요

-- 1. service_requests 테이블
ALTER TABLE service_requests MODIFY COLUMN lat DECIMAL(10, 8) NULL DEFAULT NULL;
ALTER TABLE service_requests MODIFY COLUMN lng DECIMAL(11, 8) NULL DEFAULT NULL;

-- 2. managers 테이블
ALTER TABLE managers MODIFY COLUMN id CHAR(36) NOT NULL;
ALTER TABLE managers MODIFY COLUMN account_number VARCHAR(50) NULL DEFAULT NULL;
ALTER TABLE managers MODIFY COLUMN password_hash VARCHAR(255) NULL DEFAULT NULL;

-- 3. 확인
SELECT 'Schema 수정 완료' as result;
