-- 관리자 계정 추가
-- admin_id: admin
-- password: admin123

INSERT INTO `admins` (`admin_id`, `password_hash`, `created_at`) 
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123의 bcrypt 해시
    NOW()
);

-- 참고: 위 해시값은 예시입니다. 실제로는 아래 PHP 스크립트로 생성한 해시값을 사용하세요.
-- <?php
-- echo password_hash('admin123', PASSWORD_DEFAULT);
-- ?>
