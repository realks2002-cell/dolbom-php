<?php
/**
 * 서버 메뉴 디버깅 스크립트
 */
echo "<h1>관리자 메뉴 디버깅</h1>";
echo "<pre>";

// 1. 파일 존재 확인
echo "=== 1. 파일 존재 확인 ===\n";
$files = [
    'components/admin-layout.php',
    'pages/admin/refund-info.php',
    'api/admin/process-refund.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        $mtime = date('Y-m-d H:i:s', filemtime($file));
        echo "✅ " . str_pad($file, 40) . " (" . number_format($size) . " bytes, " . $mtime . ")\n";
    } else {
        echo "❌ " . str_pad($file, 40) . " 파일 없음!\n";
    }
}

// 2. admin-layout.php 내용 확인
echo "\n=== 2. admin-layout.php 메뉴 배열 확인 ===\n";
if (file_exists('components/admin-layout.php')) {
    $content = file_get_contents('components/admin-layout.php');
    
    // $menuItems 배열 추출
    if (preg_match('/\$menuItems\s*=\s*\[(.*?)\];/s', $content, $match)) {
        echo "메뉴 배열 발견:\n";
        
        // 각 메뉴 항목 추출
        preg_match_all("/\['route'\s*=>\s*'([^']+)'.*?'label'\s*=>\s*'([^']+)'/s", $match[1], $matches, PREG_SET_ORDER);
        
        if (!empty($matches)) {
            foreach ($matches as $idx => $m) {
                echo "  " . ($idx + 1) . ". route: " . str_pad($m[1], 25) . " label: " . $m[2] . "\n";
            }
        } else {
            echo "  ❌ 메뉴 항목을 파싱할 수 없습니다.\n";
        }
    } else {
        echo "❌ \$menuItems 배열을 찾을 수 없습니다.\n";
    }
    
    // 특정 문자열 검색
    echo "\n=== 3. 특정 메뉴 검색 ===\n";
    $searches = [
        'admin/refund-info',
        'admin/refund-requests',
        '취소/환불 요청',
        '환불정보',
    ];
    
    foreach ($searches as $search) {
        $found = strpos($content, $search) !== false;
        echo ($found ? "✅" : "❌") . " '" . $search . "' " . ($found ? "있음" : "없음") . "\n";
    }
    
    // 4. 파일 크기 확인
    echo "\n=== 4. 파일 크기 비교 ===\n";
    echo "현재 파일 크기: " . number_format(strlen($content)) . " bytes\n";
    echo "예상 크기: 약 5,000-6,000 bytes\n";
    
    if (strlen($content) < 3000) {
        echo "⚠️  파일이 너무 작습니다. 업로드가 불완전할 수 있습니다.\n";
    }
    
} else {
    echo "❌ components/admin-layout.php 파일이 없습니다!\n";
}

// 5. 로컬 파일과 비교
echo "\n=== 5. 로컬 파일 내용 (참고) ===\n";
echo "로컬에는 다음 메뉴가 있어야 합니다:\n";
echo "  1. 대시보드\n";
echo "  2. 회원 관리\n";
echo "  3. 매니저 관리\n";
echo "  4. 예약요청 및 매칭 현황\n";
echo "  5. 결제 내역 조회\n";
echo "  6. 취소/환불 요청 (route: admin/refund-info)\n";
echo "  7. 일/월 매출 집계\n";

echo "\n</pre>";

echo "<h2>문제 해결</h2>";
echo "<ul>";
echo "<li><strong>파일이 없거나 크기가 작으면</strong>: FTP로 재업로드</li>";
echo "<li><strong>'admin/refund-info'가 없으면</strong>: admin-layout.php 재업로드</li>";
echo "<li><strong>파일은 정상인데 메뉴가 안 보이면</strong>: 브라우저 캐시 문제</li>";
echo "</ul>";

echo "<h2>즉시 시도</h2>";
echo "<ol>";
echo "<li><a href='clear_cache.php'>캐시 초기화</a> 실행</li>";
echo "<li>브라우저 완전 종료 후 재시작</li>";
echo "<li>시크릿 모드로 다시 접속</li>";
echo "<li>다른 브라우저로 테스트</li>";
echo "</ol>";
?>
