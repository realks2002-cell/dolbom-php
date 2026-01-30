-- admin123 비밀번호로 업데이트
-- 이 SQL을 실행하기 전에 test_admin_hash.php를 실행하여 올바른 해시값을 생성하세요.

-- 방법 1: 기존 계정 비밀번호 업데이트
UPDATE `admins` 
SET `password_hash` = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy' 
WHERE `admin_id` = 'admin';

-- 방법 2: 계정이 없으면 새로 생성
INSERT INTO `admins` (`admin_id`, `password_hash`, `created_at`) 
VALUES (
    'admin',
    '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
    NOW()
)
ON DUPLICATE KEY UPDATE 
    `password_hash` = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy';

-- 참고: 위 해시값은 admin123의 bcrypt 해시입니다.
-- 실제로는 test_admin_hash.php를 실행하여 새로운 해시값을 생성하고 사용하세요.
