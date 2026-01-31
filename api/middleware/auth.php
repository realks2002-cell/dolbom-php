<?php
/**
 * API 인증 미들웨어
 * Authorization: Bearer <token> 검사 후 $apiUser 설정
 */

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__, 2) . '/config/app.php';
}
require_once dirname(__DIR__, 2) . '/includes/jwt.php';

$apiUser = null;
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^\s*Bearer\s+(.+)\s*$/', $auth, $m)) {
    $payload = jwt_decode(trim($m[1]), API_JWT_SECRET);
    if ($payload && isset($payload['sub']) && ($payload['role'] ?? '') === 'manager') {
        $pdo = require dirname(__DIR__, 2) . '/database/connect.php';
        // 매니저는 managers 테이블에서 조회
        $st = $pdo->prepare('SELECT id, name, phone FROM managers WHERE id = ?');
        $st->execute([$payload['sub']]);
        $manager = $st->fetch();
        if ($manager) {
            // API 응답 형식에 맞게 변환
            $apiUser = [
                'id' => $manager['id'],
                'name' => $manager['name'],
                'phone' => $manager['phone'],
                'role' => 'manager'
            ];
        }
    }
}

if (!$apiUser) {
    header('Content-Type: application/json; charset=utf-8');
    require_once dirname(__DIR__, 2) . '/api/cors.php';
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => '인증이 필요합니다.']);
    exit;
}
