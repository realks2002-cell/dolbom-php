<?php
/**
 * 매니저 서비스 요청 지원 API
 * POST /api/manager/apply
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

init_session();

// 매니저 로그인 체크
if (empty($_SESSION['manager_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => '로그인이 필요합니다.']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('잘못된 요청입니다.');
    }

    $requestId = $input['request_id'] ?? null;
    $message = trim($input['message'] ?? '');

    if (!$requestId) {
        throw new Exception('요청 ID가 필요합니다.');
    }

    $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
    $managerId = $_SESSION['manager_id'];

    // 서비스 요청 존재 및 상태 확인
    $stmt = $pdo->prepare('SELECT id, status FROM service_requests WHERE id = ?');
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        throw new Exception('존재하지 않는 서비스 요청입니다.');
    }

    if (!in_array($request['status'], ['PENDING', 'MATCHING', 'CONFIRMED'])) {
        throw new Exception('지원할 수 없는 상태의 요청입니다.');
    }

    // 이미 지원했는지 확인
    $checkStmt = $pdo->prepare('SELECT id FROM applications WHERE request_id = ? AND manager_id = ?');
    $checkStmt->execute([$requestId, $managerId]);
    if ($checkStmt->fetch()) {
        throw new Exception('이미 지원한 요청입니다.');
    }

    // 지원 등록
    $applicationId = uuid4();
    $insertStmt = $pdo->prepare('
        INSERT INTO applications (id, request_id, manager_id, status, message, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ');
    $insertStmt->execute([
        $applicationId,
        $requestId,
        $managerId,
        'PENDING',
        $message ?: null
    ]);

    // 서비스 요청 상태를 MATCHING으로 변경
    // 매니저가 지원하면 PENDING 또는 CONFIRMED 상태를 MATCHING으로 변경
    if (in_array($request['status'], ['PENDING', 'CONFIRMED'])) {
        $updateStmt = $pdo->prepare("UPDATE service_requests SET status = 'MATCHING' WHERE id = ?");
        $updateStmt->execute([$requestId]);
        
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $affectedRows = $updateStmt->rowCount();
            if ($affectedRows > 0) {
                error_log("상태 변경 성공: request_id={$requestId}, {$request['status']} → MATCHING");
            } else {
                error_log("경고: 상태 변경 실패. request_id={$requestId}, 현재 상태={$request['status']}");
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => '지원이 완료되었습니다.',
        'application_id' => $applicationId
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
