-- admins 테이블 생성
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` VARCHAR(50) NOT NULL COMMENT '로그인 ID',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'bcrypt',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admins_admin_id` (`admin_id`),
  KEY `idx_admins_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 관리자 계정 추가 (admin/admin123)
-- 주의: 아래 해시값은 예시입니다. 실제로는 test_admin_hash.php를 실행하여 올바른 해시값을 생성하세요.
-- INSERT INTO `admins` (`admin_id`, `password_hash`, `created_at`) 
-- VALUES (
--     'admin',
--     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
--     NOW()
-- );

-- 올바른 해시값 생성 방법:
-- 1. 브라우저에서 http://localhost:8000/test_admin_hash.php 접속
-- 2. 생성된 해시값을 복사하여 아래 SQL 실행
-- 3. 또는 update_admin_password.sql 파일 사용
