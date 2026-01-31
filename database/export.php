<?php
/**
 * 데이터베이스 전체 덤프 생성 스크립트
 * 
 * 사용법:
 * 1. 브라우저에서 http://localhost:8000/database/export 접속
 * 2. 또는 명령줄: php database/export.php
 * 
 * 생성된 SQL 파일을 호스팅 서버의 phpMyAdmin에서 임포트하세요.
 * 
 * ⚠️ 보안: 프로덕션 환경에서는 관리자 인증 추가 권장
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/database/connect.php';

// 브라우저에서 실행 시 HTML 형식으로 출력
$isBrowser = !empty($_SERVER['HTTP_USER_AGENT']);

$outputFile = dirname(__DIR__) . '/database/dolbom_backup_' . date('Y-m-d_His') . '.sql';

// SQL 덤프 시작
$sql = "-- 행복안심동행 데이터베이스 백업\n";
$sql .= "-- 생성일시: " . date('Y-m-d H:i:s') . "\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
$sql .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
$sql .= "SET AUTOCOMMIT=0;\n";
$sql .= "START TRANSACTION;\n\n";

try {
    // 모든 테이블 목록 가져오기
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $sql .= "-- 테이블 구조: {$table}\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        
        // 테이블 구조 가져오기
        $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
        $sql .= $createTable['Create Table'] . ";\n\n";
        
        // 테이블 데이터 가져오기
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            $sql .= "-- 테이블 데이터: {$table}\n";
            $sql .= "LOCK TABLES `{$table}` WRITE;\n";
            
            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';
            
            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = $pdo->quote($value);
                    }
                }
                $sql .= "INSERT INTO `{$table}` ({$columnList}) VALUES (" . implode(', ', $values) . ");\n";
            }
            
            $sql .= "UNLOCK TABLES;\n\n";
        }
    }
    
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $sql .= "COMMIT;\n";
    
    // 파일로 저장
    file_put_contents($outputFile, $sql);
    
    $fileSize = filesize($outputFile);
    $fileSizeMB = round($fileSize / 1024 / 1024, 2);
    $fileName = basename($outputFile);
    
    if ($isBrowser) {
        // HTML 형식으로 출력
        header('Content-Type: text/html; charset=utf-8');
        
        // 다운로드 URL 생성 (절대 경로 사용)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $downloadUrl = $protocol . '://' . $host . '/database/download?file=' . urlencode($fileName);
        $homeUrl = $protocol . '://' . $host . '/';
        ?>
        <!DOCTYPE html>
        <html lang="ko">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>데이터베이스 백업 완료</title>
            <link rel="stylesheet" href="<?= $base ?>/assets/css/tailwind.min.css">
        </head>
        <body class="bg-gray-50 p-8">
            <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
                <h1 class="text-2xl font-bold mb-4 text-green-600">✅ 데이터베이스 백업 완료!</h1>
                
                <div class="space-y-2 mb-6">
                    <p><strong>파일 이름:</strong> <?= htmlspecialchars($fileName) ?></p>
                    <p><strong>파일 위치:</strong> <code class="bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($outputFile) ?></code></p>
                    <p><strong>파일 크기:</strong> <?= $fileSizeMB ?> MB</p>
                    <p><strong>테이블 수:</strong> <?= count($tables) ?>개</p>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <h2 class="font-semibold mb-2">다음 단계:</h2>
                    <ol class="list-decimal list-inside space-y-1 text-sm">
                        <li>생성된 SQL 파일을 다운로드하세요: <a href="<?= htmlspecialchars($downloadUrl) ?>" class="text-blue-600 hover:underline"><?= htmlspecialchars($fileName) ?></a></li>
                        <li>카페24 호스팅 관리자 > 데이터베이스 관리 > phpMyAdmin 접속</li>
                        <li>생성한 데이터베이스 선택</li>
                        <li>상단 "가져오기" 탭 클릭</li>
                        <li>다운로드한 SQL 파일 선택 후 "실행" 클릭</li>
                    </ol>
                </div>
                
                <div class="flex gap-2">
                    <a href="<?= htmlspecialchars($downloadUrl) ?>" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        SQL 파일 다운로드
                    </a>
                    <a href="<?= htmlspecialchars($homeUrl) ?>" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        홈으로 돌아가기
                    </a>
                </div>
            </div>
        </body>
        </html>
        <?php
    } else {
        // 명령줄 형식으로 출력
        echo "✅ 데이터베이스 백업 완료!\n\n";
        echo "파일 위치: {$outputFile}\n";
        echo "파일 크기: {$fileSizeMB} MB\n";
        echo "테이블 수: " . count($tables) . "개\n\n";
        echo "다음 단계:\n";
        echo "1. 이 파일을 호스팅 서버의 phpMyAdmin에서 임포트하세요.\n";
        echo "2. 또는 FTP로 업로드 후 phpMyAdmin에서 가져오기 기능 사용\n";
    }
    
} catch (Exception $e) {
    if ($isBrowser) {
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>오류</title></head><body>";
        echo "<h1 style='color: red;'>❌ 오류 발생</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</body></html>";
    } else {
        echo "❌ 오류 발생: " . $e->getMessage() . "\n";
    }
    exit(1);
}
