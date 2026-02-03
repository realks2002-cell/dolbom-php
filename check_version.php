<?php
/**
 * 파일 버전 확인 스크립트
 * 서버와 로컬 파일이 동일한지 확인
 */
echo "<h1>파일 버전 확인</h1>";
echo "<pre>";

$files = [
    'components/admin-layout.php',
    'pages/admin/refund-info.php',
    'api/admin/process-refund.php',
    'pages/requests/new.php',
    'pages/auth/signup.php',
    'components/header.php',
];

echo "=== 파일 수정 시간 확인 ===\n\n";

foreach ($files as $file) {
    if (file_exists($file)) {
        $mtime = filemtime($file);
        $size = filesize($file);
        echo str_pad($file, 40) . ": ";
        echo date('Y-m-d H:i:s', $mtime) . " (" . number_format($size) . " bytes)\n";
    } else {
        echo str_pad($file, 40) . ": ❌ 파일 없음\n";
    }
}

echo "\n=== 현재 시간 ===\n";
echo date('Y-m-d H:i:s') . "\n";

echo "\n=== admin-layout.php 메뉴 확인 ===\n";
if (file_exists('components/admin-layout.php')) {
    $content = file_get_contents('components/admin-layout.php');
    if (strpos($content, 'admin/refund-info') !== false) {
        echo "✅ 'admin/refund-info' 메뉴 있음\n";
    } else {
        echo "❌ 'admin/refund-info' 메뉴 없음\n";
    }
    
    if (strpos($content, 'admin/refund-requests') !== false) {
        echo "⚠️  'admin/refund-requests' 메뉴 있음 (중복)\n";
    }
    
    if (strpos($content, '취소/환불 요청') !== false) {
        echo "✅ '취소/환불 요청' 라벨 있음\n";
    }
}

echo "\n</pre>";

echo "<h2>조치 사항</h2>";
echo "<ul>";
echo "<li>파일 날짜가 오래되었으면 FTP로 재업로드하세요.</li>";
echo "<li>메뉴가 없으면 admin-layout.php를 재업로드하세요.</li>";
echo "<li>브라우저 캐시를 삭제하세요 (Ctrl + Shift + R).</li>";
echo "</ul>";
?>
