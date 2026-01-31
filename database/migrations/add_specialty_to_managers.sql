-- managers 테이블에 특기(specialty) 필드 추가
ALTER TABLE `managers` 
ADD COLUMN `specialty` VARCHAR(255) DEFAULT NULL COMMENT '특기' AFTER `account_number`;

-- 특기 필드에 인덱스 추가 (검색 성능 향상)
ALTER TABLE `managers` 
ADD KEY `idx_managers_specialty` (`specialty`);
