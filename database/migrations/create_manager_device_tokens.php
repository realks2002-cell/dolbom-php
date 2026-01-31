<?php
/**
 * manager_device_tokens 테이블 생성 마이그레이션
 * 브라우저에서 실행: http://localhost:8000/database/migrations/create_manager_device_tokens.php
 */
require_once dirname(__DIR__, 2) . '/config/app.php';

$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

$sql = file_get_contents(__DIR__ . '/create_manager_device_tokens.sql');

try {
    $pdo->exec($sql);
    echo '<h1>✅ 마이그레이션 성공</h1>';
    echo '<p>manager_device_tokens 테이블이 생성되었습니다.</p>';
} catch (PDOException $e) {
    echo '<h1>❌ 마이그레이션 실패</h1>';
    echo '<p style="color: red;">오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo '<p>테이블이 이미 존재합니다. 문제없습니다.</p>';
    }
}
