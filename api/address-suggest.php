<?php
/**
 * 주소 자동완성 API (VWorld 다중 쿼리 방식)
 * GET ?keyword=...
 * 여러 패턴을 시도해서 후보 주소 목록 반환
 * 응답: { success, items: [ { address, x, y }, ... ] }
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/app.php';

$keyword = trim((string) ($_GET['keyword'] ?? $_GET['address'] ?? $_GET['q'] ?? ''));

if ($keyword === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '주소를 입력해주세요.']);
    exit;
}

$apiKey = defined('VWORLD_API_KEY') ? VWORLD_API_KEY : '';
if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'VWorld API Key가 설정되지 않았습니다.']);
    exit;
}

// 디버그 모드 (호스팅 환경에서 문제 진단용)
$debugMode = defined('APP_DEBUG') && APP_DEBUG && isset($_GET['debug']);
$debugInfo = [];

// 검색 패턴 생성: 기본 키워드 + 번지 조합
$patterns = [$keyword]; // 기본 검색어

// 키워드가 번지 없이 도로명/동만 있는 경우, 대표 번지들을 추가
if (!preg_match('/\d+/', $keyword)) {
    // 번지가 없으면 대표 번지 추가
    $patterns[] = $keyword . ' 1';
    $patterns[] = $keyword . ' 10';
    $patterns[] = $keyword . ' 100';
    $patterns[] = $keyword . ' 200';
    $patterns[] = $keyword . ' 300';
    $patterns[] = $keyword . ' 500';
}

$results = [];
$seen = []; // 중복 제거용

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
            CURLOPT_TIMEOUT => 10,
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
        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
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
                
                // 최대 10개까지만
                if (count($results) >= 10) break;
            }
        }
    }
    
    // parcel(지번) 타입도 시도 (domain 파라미터 추가)
    if (count($results) < 10) {
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
                    
                    if (count($results) >= 10) break;
                }
            }
        }
    }
}

if (count($results) === 0) {
    $errorMsg = '일치하는 주소를 찾지 못했습니다. 시/구/동 또는 도로명을 포함해주세요.';
    if ($debugMode) {
        $errorMsg .= ' (디버그: 패턴 ' . count($patterns) . '개 시도, API 키: ' . substr($apiKey, 0, 10) . '...)';
    }
    echo json_encode(['success' => false, 'message' => $errorMsg, 'debug' => $debugMode ? $debugInfo : null]);
    exit;
}

$response = ['success' => true, 'items' => $results];
if ($debugMode) {
    $response['debug'] = $debugInfo;
}
echo json_encode($response);
