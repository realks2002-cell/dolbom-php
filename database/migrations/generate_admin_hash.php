<?php
/**
 * admin123 비밀번호의 bcrypt 해시 생성
 * 이 파일을 실행하면 실제 해시값을 생성합니다.
 */

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "비밀번호: {$password}\n";
echo "해시값: {$hash}\n\n";

echo "SQL 쿼리:\n";
echo "INSERT INTO `admin` (`admin_id`, `password_hash`, `created_at`) \n";
echo "VALUES ('admin', '{$hash}', NOW());\n";
