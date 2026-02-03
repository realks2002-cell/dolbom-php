<?php
/**
 * 주소 자동완성 API (VWorld 다중 쿼리 방식)
 * GET ?keyword=...
 * 여러 패턴을 시도해서 후보 주소 목록 반환
 * 응답: { success, items: [ { address, x, y }, ... ] }
 */
// 에러 출력 완전 차단 (가장 먼저)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 출력 버퍼링 시작 (에러 메시지 차단)
ob_start();

// JSON 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// 에러 핸들러 설정
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'PHP 에러 발생',
        'error' => [
            'code' => $errno,
            'message' => $errstr,
            'file' => basename($errfile),
            'line' => $errline
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}, E_ALL);

try {
    require_once dirname(__DIR__) . '/config/app.php';
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => '설정 파일 로드 실패',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Error $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => '설정 파일 로드 실패 (Fatal Error)',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$keyword = trim((string) ($_GET['keyword'] ?? $_GET['address'] ?? $_GET['q'] ?? ''));
$debugMode = isset($_GET['debug']);

if ($keyword === '') {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '주소를 입력해주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 디버그 모드: 버퍼 내용 확인
if ($debugMode) {
    $bufferContent = ob_get_contents();
    if ($bufferContent && strlen($bufferContent) > 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => '출력 버퍼에 예상치 못한 내용이 있습니다',
            'buffer' => substr($bufferContent, 0, 500) // 처음 500자만
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$apiKey = defined('VWORLD_API_KEY') ? VWORLD_API_KEY : '';
if ($apiKey === '') {
    ob_end_clean(); // 버퍼 비우기
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'VWorld API Key가 설정되지 않았습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
// 디버그 모드 (호스팅 환경에서 문제 진단용)
$debugMode = defined('APP_DEBUG') && APP_DEBUG && isset($_GET['debug']);
$debugInfo = [];

// 검색 패턴 생성: 기본 키워드 + 번지 조합
$patterns = [$keyword]; // 기본 검색어

// 키워드가 번지 없이 도로명/동만 있는 경우, 최소한의 패턴만 추가 (성능 최적화)
if (!preg_match('/\d+/', $keyword)) {
    // 번지가 없으면 대표 번지 2개만 추가 (빠른 응답을 위해)
    $patterns[] = $keyword . ' 1';
    $patterns[] = $keyword . ' 100';
}

$results = [];
$seen = []; // 중복 제거용
$shouldBreak = false; // 루프 종료 플래그

/**
 * HTTP 요청 헬퍼 함수 (cURL 우선, file_get_contents 폴백)
 */
function fetch_url($url) {
    $response = null;
    
    // cURL 시도
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5, // 10초 → 5초로 단축 (빠른 응답)
            CURLOPT_CONNECTTIMEOUT => 3, // 연결 타임아웃 3초
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err || $status !== 200) {
            $response = null;
        }
    }
    
    // cURL 실패 시 file_get_contents 시도
    if ($response === null && ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => ['timeout' => 5]]); // 10초 → 5초로 단축
        $response = @file_get_contents($url, false, $ctx);
    }
    
    return $response !== false && $response !== null && $response !== '' ? $response : null;
}

// 호스팅 도메인 가져오기 (VWorld API 도메인 등록용)
$hostDomain = $_SERVER['HTTP_HOST'] ?? 'localhost';
if ($hostDomain === 'localhost' || strpos($hostDomain, '127.0.0.1') !== false) {
    $hostDomain = 'localhost';
}

foreach ($patterns as $pattern) {
    if ($shouldBreak) break; // 플래그 확인
    
    $address = rawurlencode($pattern);
    
    // road 타입 시도 (domain 파라미터 추가)
    $url = "http://api.vworld.kr/req/address?service=address&request=getcoord&version=2.0&crs=EPSG:4326&address={$address}&type=road&format=json&key={$apiKey}&domain={$hostDomain}";
    
    $response = fetch_url($url);
    if ($debugMode) {
        $debugInfo[] = ['pattern' => $pattern, 'url' => $url, 'has_response' => $response !== null];
    }
    if ($response) {
        $data = json_decode($response, true);
        if ($debugMode && is_array($data)) {
            $debugInfo[count($debugInfo) - 1]['status'] = $data['response']['status'] ?? 'UNKNOWN';
        }
        if (is_array($data) && ($data['response']['status'] ?? '') === 'OK') {
            $result = $data['response']['result'] ?? [];
            $point = $result['point'] ?? [];
            $x = isset($point['x']) ? (float) $point['x'] : null;
            $y = isset($point['y']) ? (float) $point['y'] : null;
            $refined = $data['response']['refined']['text'] ?? '';
            
            if ($x !== null && $y !== null && $refined !== '' && !isset($seen[$refined])) {
                $results[] = ['address' => $refined, 'x' => $x, 'y' => $y];
                $seen[$refined] = true;
                
                // 최대 개수 제한 없음 (모든 결과 표시)
            }
        }
    }
    
    // parcel(지번) 타입도 시도
    if (!$shouldBreak) {
        $url2 = "http://api.vworld.kr/req/address?service=address&request=getcoord&version=2.0&crs=EPSG:4326&address={$address}&type=parcel&format=json&key={$apiKey}&domain={$hostDomain}";
        
        $response2 = fetch_url($url2);
        if ($response2) {
            $data2 = json_decode($response2, true);
            if (is_array($data2) && ($data2['response']['status'] ?? '') === 'OK') {
                $result2 = $data2['response']['result'] ?? [];
                $point2 = $result2['point'] ?? [];
                $x2 = isset($point2['x']) ? (float) $point2['x'] : null;
                $y2 = isset($point2['y']) ? (float) $point2['y'] : null;
                $refined2 = $data2['response']['refined']['text'] ?? '';
                
                if ($x2 !== null && $y2 !== null && $refined2 !== '' && !isset($seen[$refined2])) {
                    $results[] = ['address' => $refined2, 'x' => $x2, 'y' => $y2];
                    $seen[$refined2] = true;
                }
            }
        }
    }
    
    // 모든 패턴 검색 (결과 최대화)
}

if (count($results) === 0) {
    ob_end_clean(); // 버퍼 비우기
    $errorMsg = '일치하는 주소를 찾지 못했습니다. 시/구/동 또는 도로명을 포함해주세요.';
    if ($debugMode) {
        $errorMsg .= ' (디버그: 패턴 ' . count($patterns) . '개 시도, API 키: ' . substr($apiKey, 0, 10) . '...)';
    }
    echo json_encode(['success' => false, 'message' => $errorMsg, 'debug' => $debugMode ? $debugInfo : null], JSON_UNESCAPED_UNICODE);
    exit;
}

ob_end_clean(); // 버퍼 비우기
$response = ['success' => true, 'items' => $results];
if ($debugMode) {
    $response['debug'] = $debugInfo;
}
echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => '주소 검색 중 오류가 발생했습니다',
        'error' => [
            'type' => 'Exception',
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'trace' => $debugMode ? $e->getTraceAsString() : null
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Error $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => '주소 검색 중 치명적 오류가 발생했습니다',
        'error' => [
            'type' => 'Fatal Error',
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'trace' => $debugMode ? $e->getTraceAsString() : null
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
