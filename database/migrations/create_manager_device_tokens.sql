-- 매니저 디바이스 토큰 저장 테이블
-- FCM 푸시 알림을 위한 디바이스 토큰 관리

CREATE TABLE IF NOT EXISTS `manager_device_tokens` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `manager_id` INT(10) UNSIGNED NOT NULL COMMENT '매니저 ID',
  `device_token` VARCHAR(255) NOT NULL COMMENT 'FCM 디바이스 토큰',
  `platform` ENUM('android', 'ios', 'web') NOT NULL DEFAULT 'android' COMMENT '플랫폼',
  `app_version` VARCHAR(50) DEFAULT NULL COMMENT '앱 버전',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
  `last_used_at` TIMESTAMP NULL DEFAULT NULL COMMENT '마지막 사용 일시',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_manager_device_token` (`manager_id`, `device_token`),
  KEY `idx_manager_device_manager` (`manager_id`),
  KEY `idx_manager_device_active` (`is_active`),
  KEY `idx_manager_device_token` (`device_token`),
  CONSTRAINT `fk_manager_device_tokens_manager` FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='매니저 디바이스 토큰';
