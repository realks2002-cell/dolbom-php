<?php
/**
 * 버전 확인용 임시 파일
 * URL: /version-check
 */
require_once __DIR__ . '/config/app.php';

header('Content-Type: application/json; charset=utf-8');

$checksums = [];

// pages/requests/new.php 파일 체크
$file1 = __DIR__ . '/pages/requests/new.php';
if (file_exists($file1)) {
    $content1 = file_get_contents($file1);
    $checksums['requests_new_php'] = [
        'size' => filesize($file1),
        'md5' => md5($content1),
        'modified' => date('Y-m-d H:i:s', filemtime($file1)),
        'has_designated_manager_id' => strpos($content1, 'designated_manager_id: formData.get') !== false ? 'YES ✅' : 'NO ❌'
    ];
}

// pages/admin/designated-matching.php 파일 체크
$file2 = __DIR__ . '/pages/admin/designated-matching.php';
if (file_exists($file2)) {
    $content2 = file_get_contents($file2);
    $checksums['designated_matching_php'] = [
        'size' => filesize($file2),
        'md5' => md5($content2),
        'modified' => date('Y-m-d H:i:s', filemtime($file2)),
        'has_debug' => strpos($content2, '최근 요청 10개') !== false ? 'YES ✅' : 'NO ❌'
    ];
}

echo json_encode([
    'ok' => true,
    'server_time' => date('Y-m-d H:i:s'),
    'files' => $checksums
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
