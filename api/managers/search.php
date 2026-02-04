<?php
/**
 * 도우미 검색 API
 * POST /api/managers/search
 * 
 * 요청: { "phone": "01012345678", "name": "김매니저" } (둘 중 하나는 필수)
 * 응답: { "ok": true, "managers": [...] }
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/api/cors.php';
require_once dirname(__DIR__, 2) . '/includes/security.php';

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'POST 요청만 허용됩니다.']);
    exit;
}

// JSON 입력 파싱
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => '잘못된 요청 형식입니다.']);
    exit;
}

$phone = trim($input['phone'] ?? '');
$name = trim($input['name'] ?? '');

// 최소 하나는 입력되어야 함
if ($phone === '' && $name === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => '전화번호 또는 이름을 입력해주세요.']);
    exit;
}

try {
    $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
    
    // 쿼리 조건 구성
    $conditions = [];
    $params = [];
    
    if ($phone !== '') {
        // 전화번호 정규화 (하이픈 제거) - normalize_phone 함수 사용
        if (function_exists('normalize_phone')) {
            $normalizedPhone = normalize_phone($phone);
        } else {
            // 함수가 없으면 직접 정규화
            $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
        }
        $conditions[] = "REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+82', '0') LIKE ?";
        $params[] = '%' . $normalizedPhone . '%';
    }
    
    if ($name !== '') {
        // 이름 부분 일치 검색
        $conditions[] = "name LIKE ?";
        $params[] = '%' . $name . '%';
    }
    
    // 조건이 없으면 에러
    if (empty($conditions)) {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'message' => '검색 조건을 입력해주세요.'
        ]);
        exit;
    }
    
    // OR 조건으로 연결
    $whereClause = implode(' OR ', $conditions);
    
    // 민감정보 제외하고 조회
    $sql = "
        SELECT 
            id,
            name,
            phone,
            address1,
            specialty,
            photo,
            gender
        FROM managers
        WHERE {$whereClause}
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 결과 반환
    echo json_encode([
        'ok' => true,
        'managers' => $managers,
        'count' => count($managers)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => '검색 중 오류가 발생했습니다.',
        'error' => APP_DEBUG ? $e->getMessage() : null
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => '서버 오류가 발생했습니다.',
        'error' => APP_DEBUG ? $e->getMessage() : null
    ]);
}
