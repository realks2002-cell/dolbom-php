<?php
/**
 * 회원 주소 정보 확인
 */
require_once 'config/app.php';
require_once 'includes/helpers.php';
require_once 'includes/auth.php';

echo "<h1>회원 주소 정보 확인</h1>";
echo "<pre>";

if ($currentUser) {
    echo "=== 현재 로그인 사용자 ===\n";
    echo "ID: " . $currentUser['id'] . "\n";
    echo "이름: " . $currentUser['name'] . "\n";
    echo "전화번호: " . ($currentUser['phone'] ?? 'NULL') . "\n";
    echo "주소: " . ($currentUser['address'] ?? 'NULL') . "\n";
    echo "상세주소: " . ($currentUser['address_detail'] ?? 'NULL') . "\n";
    
    if (empty($currentUser['address'])) {
        echo "\n⚠️ 주소가 비어있습니다!\n";
        echo "회원가입 시 주소를 입력하지 않았거나, DB에 저장되지 않았습니다.\n";
    } else {
        echo "\n✅ 주소 정보 있음\n";
    }
} else {
    echo "로그인되지 않았습니다.\n";
    echo "테스트하려면 먼저 로그인하세요.\n";
}

echo "\n=== includes/auth.php SELECT 쿼리 확인 ===\n";
$authFile = file_get_contents('includes/auth.php');
if (strpos($authFile, 'address') !== false) {
    echo "✅ auth.php에 'address' 필드 포함됨\n";
} else {
    echo "❌ auth.php에 'address' 필드 없음\n";
}

if (strpos($authFile, 'address_detail') !== false) {
    echo "✅ auth.php에 'address_detail' 필드 포함됨\n";
} else {
    echo "❌ auth.php에 'address_detail' 필드 없음\n";
}

echo "</pre>";

echo "<h2>해결 방법</h2>";
echo "<ul>";
echo "<li>주소가 NULL이면: 회원정보 수정 페이지에서 주소 입력</li>";
echo "<li>auth.php에 필드가 없으면: includes/auth.php 재업로드</li>";
echo "<li>또는 테스트 계정으로 로그인 (주소 있는 계정)</li>";
echo "</ul>";
?>
