<?php
/**
 * 데이터베이스 백업 파일 다운로드
 * 
 * 보안: 프로덕션에서는 관리자 인증 추가 권장
 */

require_once dirname(__DIR__) . '/config/app.php';

$fileName = $_GET['file'] ?? '';

// 보안: 파일명 검증
if (empty($fileName) || !preg_match('/^dolbom_backup_\d{4}-\d{2}-\d{2}_\d{6}\.sql$/', $fileName)) {
    http_response_code(400);
    die('잘못된 파일명입니다.');
}

$filePath = __DIR__ . '/' . $fileName;

// 파일 존재 확인
if (!file_exists($filePath)) {
    http_response_code(404);
    die('파일을 찾을 수 없습니다.');
}

// 파일 다운로드
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($filePath);
exit;
