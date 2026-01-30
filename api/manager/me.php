<?php
/**
 * GET /api/manager/me
 * Bearer 토큰 필수. 현재 매니저 정보 반환.
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/api/cors.php';
require_once dirname(__DIR__, 2) . '/api/middleware/auth.php';

echo json_encode(['ok' => true, 'user' => $apiUser]);
