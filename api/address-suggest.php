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

foreach ($patterns as $pattern) {
    $address = rawurlencode($pattern);
    
    // road 타입 시도
    $url = "http://api.vworld.kr/req/address?service=address&request=getcoord&version=2.0&crs=EPSG:4326&address={$address}&type=road&format=json&key={$apiKey}";
    
    $response = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5]]));
    if ($response) {
        $data = json_decode($response, true);
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
    
    // parcel(지번) 타입도 시도
    if (count($results) < 10) {
        $url2 = "http://api.vworld.kr/req/address?service=address&request=getcoord&version=2.0&crs=EPSG:4326&address={$address}&type=parcel&format=json&key={$apiKey}";
        
        $response2 = @file_get_contents($url2, false, stream_context_create(['http' => ['timeout' => 5]]));
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
    echo json_encode(['success' => false, 'message' => '일치하는 주소를 찾지 못했습니다. 시/구/동 또는 도로명을 포함해주세요.']);
    exit;
}

echo json_encode(['success' => true, 'items' => $results]);
