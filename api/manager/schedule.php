<?php
/**
 * GET /api/manager/schedule
 * 내 일정 — stub
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/api/cors.php';
require_once dirname(__DIR__, 2) . '/api/middleware/auth.php';

echo json_encode(['ok' => true, 'items' => [], 'message' => '일정 (추가 예정)']);
