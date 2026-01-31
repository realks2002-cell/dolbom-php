-- device_token 컬럼을 TEXT로 변경 (Web Push 구독 JSON 전체 저장)
-- 실행: 카페24 phpMyAdmin에서 실행

ALTER TABLE `manager_device_tokens`
MODIFY COLUMN `device_token` TEXT NOT NULL COMMENT 'Web Push 구독 정보 (JSON)';
