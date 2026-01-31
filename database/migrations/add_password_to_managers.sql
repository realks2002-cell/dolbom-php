-- managers 테이블에 password_hash 필드 추가
ALTER TABLE `managers` 
ADD COLUMN `password_hash` VARCHAR(255) NOT NULL COMMENT 'bcrypt' AFTER `account_number`;

-- 기존 데이터가 있다면 임시 비밀번호 설정 (admin123)
-- UPDATE `managers` SET `password_hash` = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy' WHERE `password_hash` = '';
