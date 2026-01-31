-- 매니저 테이블 생성
CREATE TABLE IF NOT EXISTS `managers` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT '이름',
  `ssn` VARCHAR(20) NOT NULL COMMENT '주민번호',
  `phone` VARCHAR(20) NOT NULL COMMENT '전화번호',
  `address1` VARCHAR(255) NOT NULL COMMENT '주소1',
  `address2` VARCHAR(255) DEFAULT NULL COMMENT '주소2',
  `account_number` VARCHAR(50) NOT NULL COMMENT '계좌번호',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_managers_ssn` (`ssn`),
  KEY `idx_managers_phone` (`phone`),
  KEY `idx_managers_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 테스트 데이터 추가 (선택사항)
-- INSERT INTO `managers` (`name`, `ssn`, `phone`, `address1`, `address2`, `account_number`) 
-- VALUES 
--     ('홍길동', '123456-1234567', '010-1234-5678', '서울시 강남구 테헤란로 123', '101동 101호', '123-456-789012'),
--     ('김철수', '234567-2345678', '010-2345-6789', '경기도 성남시 분당구 정자동 456', '202동 303호', '234-567-890123');
