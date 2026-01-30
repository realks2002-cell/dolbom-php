<?php
/**
 * VWorld 주소 검색 프록시 (Geocoder API 2.0)
 * GET ?keyword=... 또는 ?address=... 또는 ?q=...
 * service=address, request=getcoord (단일 결과 반환)
 * 응답: { success, address, x, y, message? }
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

$address = rawurlencode($keyword);
$url = "http://api.vworld.kr/req/address?service=address&request=getcoord&version=2.0&crs=EPSG:4326&address={$address}&type=road&format=json&key={$apiKey}";

$response = null;
if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err || $status !== 200) {
        $response = null;
    }
}
if ($response === null && ini_get('allow_url_fopen')) {
    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents($url, false, $ctx);
}

if ($response === false || $response === null || $response === '') {
    echo json_encode(['success' => false, 'message' => '주소 검색에 실패했습니다. 잠시 후 다시 시도해주세요.']);
    exit;
}

$data = json_decode($response, true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => '검색 결과를 처리할 수 없습니다.']);
    exit;
}

// 디버그 모드
if (defined('APP_DEBUG') && APP_DEBUG && isset($_GET['debug'])) {
    echo json_encode([
        'success' => true,
        'debug_mode' => true,
        'vworld_response' => $data
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$status = $data['response']['status'] ?? '';
if ($status === 'ERROR') {
    $errorMsg = $data['response']['error']['text'] ?? '주소 검색에 실패했습니다.';
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    exit;
}

if ($status !== 'OK') {
    echo json_encode(['success' => false, 'message' => '주소를 찾을 수 없습니다.']);
    exit;
}

$result = $data['response']['result'] ?? [];
$point = $result['point'] ?? [];
$x = isset($point['x']) ? (float) $point['x'] : null;
$y = isset($point['y']) ? (float) $point['y'] : null;

if ($x === null || $y === null) {
    // road 실패 시 parcel(지번) 재시도
    $url2 = "http://api.vworld.kr/req/address?service=address&request=getcoord&version=2.0&crs=EPSG:4326&address={$address}&type=parcel&format=json&key={$apiKey}";
    $response2 = null;
    if (function_exists('curl_init')) {
        $ch2 = curl_init($url2);
        curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $response2 = curl_exec($ch2);
        curl_close($ch2);
    }
    if ($response2 && trim($response2) !== '') {
        $data2 = json_decode($response2, true);
        if (is_array($data2) && ($data2['response']['status'] ?? '') === 'OK') {
            $result2 = $data2['response']['result'] ?? [];
            $point2 = $result2['point'] ?? [];
            $x = isset($point2['x']) ? (float) $point2['x'] : null;
            $y = isset($point2['y']) ? (float) $point2['y'] : null;
            if ($x !== null && $y !== null) {
                $refined = $data2['response']['refined']['text'] ?? $keyword;
                echo json_encode(['success' => true, 'address' => $refined, 'x' => $x, 'y' => $y]);
                exit;
            }
        }
    }
    echo json_encode(['success' => false, 'message' => '일치하는 주소를 찾지 못했습니다. 도로명 또는 지번 주소를 확인해주세요.']);
    exit;
}

$refined = $data['response']['refined']['text'] ?? $keyword;
echo json_encode(['success' => true, 'address' => $refined, 'x' => $x, 'y' => $y]);
