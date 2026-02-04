<?php
/**
 * 마이그레이션 실행 스크립트
 * 사용법: php database/run_migration.php migrations/파일명.sql
 */

require_once __DIR__ . '/../config/app.php';

if ($argc < 2) {
    echo "사용법: php run_migration.php migrations/파일명.sql\n";
    exit(1);
}

$migrationFile = __DIR__ . '/' . $argv[1];

if (!file_exists($migrationFile)) {
    echo "오류: 마이그레이션 파일을 찾을 수 없습니다: {$migrationFile}\n";
    exit(1);
}

echo "마이그레이션 파일 읽는 중: {$migrationFile}\n";
$sql = file_get_contents($migrationFile);

try {
    $pdo = require __DIR__ . '/connect.php';
    
    // 여러 개의 SQL 문을 분리하여 실행
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            // 빈 문장과 주석만 있는 줄 제외
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    $pdo->beginTransaction();
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "실행 중: " . substr($statement, 0, 50) . "...\n";
            $pdo->exec($statement);
        }
    }
    
    $pdo->commit();
    echo "✓ 마이그레이션 완료!\n";
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "✗ 오류 발생: " . $e->getMessage() . "\n";
    exit(1);
}
