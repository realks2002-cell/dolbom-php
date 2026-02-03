<?php
/**
 * FOREIGN KEY 제약조건 확인
 */
require_once 'database/connect.php';
$pdo = require 'database/connect.php';

echo "<h1>FOREIGN KEY 제약조건 확인</h1>";
echo "<pre>";

// managers.id를 참조하는 모든 FK 찾기
$st = $pdo->query("
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME = 'managers'
    AND REFERENCED_COLUMN_NAME = 'id'
");

$fks = $st->fetchAll(PDO::FETCH_ASSOC);

echo "=== managers.id를 참조하는 FK 목록 ===\n\n";

if (count($fks) > 0) {
    foreach ($fks as $fk) {
        echo "테이블: " . $fk['TABLE_NAME'] . "\n";
        echo "  컬럼: " . $fk['COLUMN_NAME'] . "\n";
        echo "  FK 이름: " . $fk['CONSTRAINT_NAME'] . "\n";
        echo "  참조: " . $fk['REFERENCED_TABLE_NAME'] . "." . $fk['REFERENCED_COLUMN_NAME'] . "\n";
        echo "  삭제 SQL: ALTER TABLE " . $fk['TABLE_NAME'] . " DROP FOREIGN KEY " . $fk['CONSTRAINT_NAME'] . ";\n\n";
    }
} else {
    echo "managers.id를 참조하는 FK가 없습니다.\n";
}

echo "\n=== managers 테이블 구조 ===\n";
$st = $pdo->query("DESCRIBE managers");
while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    if ($row['Field'] === 'id') {
        echo "id 컬럼 타입: " . $row['Type'] . "\n";
        echo "현재 타입이 INT 계열이면 CHAR(36)으로 변경 필요\n";
    }
}

echo "</pre>";

echo "<h2>다음 단계</h2>";
echo "<ol>";
echo "<li>위에 표시된 FK 삭제 SQL을 복사하여 실행</li>";
echo "<li>관련 테이블의 manager_id 타입 변경</li>";
echo "<li>managers.id 타입 변경</li>";
echo "<li>FK 재추가</li>";
echo "</ol>";
?>
