<?php
/**
 * 서버 캐시 초기화 스크립트
 */
echo "<h1>캐시 초기화</h1>";
echo "<pre>";

// 1. OpCache 초기화
echo "=== OpCache 초기화 ===\n";
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "✅ OpCache 초기화 완료\n";
    } else {
        echo "❌ OpCache 초기화 실패\n";
    }
} else {
    echo "⚠️  OpCache 사용 안 함\n";
}

// 2. 세션 초기화
echo "\n=== 세션 초기화 ===\n";
session_start();
session_destroy();
echo "✅ 세션 초기화 완료\n";

// 3. 파일 확인
echo "\n=== 파일 존재 확인 ===\n";
$files = [
    'components/admin-layout.php',
    'pages/admin/refund-info.php',
    'api/admin/process-refund.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ " . $file . " (수정: " . date('Y-m-d H:i:s', filemtime($file)) . ")\n";
    } else {
        echo "❌ " . $file . " 없음\n";
    }
}

// 4. admin-layout.php 메뉴 확인
echo "\n=== admin-layout.php 메뉴 확인 ===\n";
if (file_exists('components/admin-layout.php')) {
    $content = file_get_contents('components/admin-layout.php');
    
    // 메뉴 항목 추출
    preg_match_all("/'label'\s*=>\s*'([^']+)'/", $content, $matches);
    if (!empty($matches[1])) {
        echo "현재 메뉴 목록:\n";
        foreach ($matches[1] as $idx => $label) {
            echo "  " . ($idx + 1) . ". " . $label . "\n";
        }
    }
    
    // 특정 메뉴 확인
    if (strpos($content, 'admin/refund-info') !== false) {
        echo "\n✅ 'admin/refund-info' 라우트 있음\n";
    } else {
        echo "\n❌ 'admin/refund-info' 라우트 없음\n";
    }
    
    if (strpos($content, '취소/환불 요청') !== false) {
        echo "✅ '취소/환불 요청' 라벨 있음\n";
    } else {
        echo "❌ '취소/환불 요청' 라벨 없음\n";
    }
}

echo "\n=== 완료 ===\n";
echo "브라우저를 Ctrl + Shift + R로 강력 새로고침하세요.\n";
echo "</pre>";

echo "<h2>다음 단계</h2>";
echo "<ol>";
echo "<li>이 페이지를 닫으세요.</li>";
echo "<li>관리자 페이지로 이동하세요.</li>";
echo "<li><strong>Ctrl + Shift + R</strong>로 강력 새로고침하세요.</li>";
echo "<li>사이드바에서 '취소/환불 요청' 메뉴를 확인하세요.</li>";
echo "</ol>";
?>
