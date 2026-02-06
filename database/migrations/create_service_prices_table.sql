-- Migration: 서비스별 가격 설정 테이블
-- 생성일: 2026-02-06
-- 설명: 서비스 유형별 시간당 가격을 관리하는 테이블

CREATE TABLE IF NOT EXISTS `service_prices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_type` VARCHAR(50) NOT NULL COMMENT '서비스 유형 (병원 동행, 가사돌봄 등)',
  `price_per_hour` INT UNSIGNED NOT NULL DEFAULT 20000 COMMENT '시간당 가격 (원)',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_service_type` (`service_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 초기 데이터 삽입 (10,000~16,000원 차등)
INSERT INTO `service_prices` (`service_type`, `price_per_hour`) VALUES
  ('병원 동행', 16000),
  ('노인 돌봄', 15000),
  ('생활동행', 14000),
  ('아이 돌봄', 13000),
  ('가사돌봄', 12000),
  ('기타', 10000)
ON DUPLICATE KEY UPDATE `updated_at` = NOW();
