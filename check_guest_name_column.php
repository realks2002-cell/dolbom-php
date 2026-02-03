<?php
/**
 * service_requests 테이블 구조 확인
 */
require_once 'database/connect.php';
$pdo = require 'database/connect.php';

echo "<h1>service_requests 테이블 구조 확인</h1>";
echo "<pre>";

echo "=== 테이블 컬럼 목록 ===\n";
$st = $pdo->query("DESCRIBE service_requests");
$columns = [];
while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    $columns[] = $row['Field'];
    echo $row['Field'] . " | " . $row['Type'] . " | Null: " . $row['Null'] . "\n";
}

echo "\n=== guest_name 컬럼 확인 ===\n";
if (in_array('guest_name', $columns)) {
    echo "✅ guest_name 컬럼 있음\n";
} else {
    echo "❌ guest_name 컬럼 없음\n";
    echo "\n해결 방법: guest_name 컬럼 추가 필요\n";
    echo "ALTER TABLE service_requests ADD COLUMN guest_name VARCHAR(100) NULL AFTER customer_id;\n";
}

echo "\n=== guest_phone 컬럼 확인 ===\n";
if (in_array('guest_phone', $columns)) {
    echo "✅ guest_phone 컬럼 있음\n";
} else {
    echo "❌ guest_phone 컬럼 없음\n";
    echo "\n해결 방법: guest_phone 컬럼 추가 필요\n";
    echo "ALTER TABLE service_requests ADD COLUMN guest_phone VARCHAR(20) NULL AFTER guest_name;\n";
}

echo "\n</pre>";

echo "<h2>조치 사항</h2>";
echo "<p>guest_name, guest_phone 컬럼이 없으면 아래 SQL을 실행하세요:</p>";
echo "<textarea style='width:100%; height:150px; font-family:monospace;'>";
echo "-- phpMyAdmin에서 실행\n";
echo "ALTER TABLE service_requests \n";
echo "ADD COLUMN guest_name VARCHAR(100) NULL AFTER customer_id,\n";
echo "ADD COLUMN guest_phone VARCHAR(20) NULL AFTER guest_name;\n";
echo "</textarea>";
?>
