<?php
/**
 * 매니저 승인 API
 * POST /api/admin/approve-manager
 *
 * 요청: { "manager_id": "uuid" }
 * 응답: { "ok": true }
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/api/cors.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

// 관리자 권한 확인
require_admin();

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

$managerId = trim($input['manager_id'] ?? '');

if ($managerId === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => '매니저 ID가 필요합니다.']);
    exit;
}

try {
    $pdo = require dirname(__DIR__, 2) . '/database/connect.php';

    // 매니저 존재 및 상태 확인
    $stmt = $pdo->prepare("SELECT id, name, approval_status FROM managers WHERE id = ?");
    $stmt->execute([$managerId]);
    $manager = $stmt->fetch();

    if (!$manager) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => '매니저를 찾을 수 없습니다.']);
        exit;
    }

    if ($manager['approval_status'] !== 'pending') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => '이미 처리된 지원입니다. (현재 상태: ' . $manager['approval_status'] . ')']);
        exit;
    }

    // 승인 처리
    $updateStmt = $pdo->prepare("
        UPDATE managers
        SET approval_status = 'approved', approved_at = NOW(), updated_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$managerId]);

    echo json_encode([
        'ok' => true,
        'message' => $manager['name'] . '님이 승인되었습니다.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => '승인 처리 중 오류가 발생했습니다.',
        'error' => APP_DEBUG ? $e->getMessage() : null
    ]);
}
