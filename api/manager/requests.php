<?php
/**
 * GET /api/manager/requests
 * 새 요청 목록 (활동 지역 내) — stub
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/api/cors.php';
require_once dirname(__DIR__, 2) . '/api/middleware/auth.php';

echo json_encode(['ok' => true, 'items' => [], 'message' => '새 요청 목록 (추가 예정)']);
