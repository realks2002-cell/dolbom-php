<?php
/**
 * POST /api/auth/login
 * Body: { "email": "...", "password": "..." }
 * 매니저만 허용. JWT + user 반환.
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/api/cors.php';
require_once dirname(__DIR__, 2) . '/includes/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input') ?: '{}';
$body = json_decode($raw, true) ?? [];
$email = trim((string) ($body['email'] ?? ''));
$password = (string) ($body['password'] ?? '');

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '이메일과 비밀번호를 입력해주세요.']);
    exit;
}

$pdo = require dirname(__DIR__, 2) . '/database/connect.php';
$st = $pdo->prepare('SELECT id, email, name, role, password_hash FROM users WHERE email = ? AND is_active = 1');
$st->execute([$email]);
$user = $st->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => '이메일 또는 비밀번호가 올바르지 않습니다.']);
    exit;
}

if ($user['role'] !== ROLE_MANAGER) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => '매니저 계정만 앱 로그인이 가능합니다.']);
    exit;
}

unset($user['password_hash']);
$payload = [
    'sub' => $user['id'],
    'role' => $user['role'],
    'exp' => time() + (30 * 24 * 3600),
];
$token = jwt_encode($payload, API_JWT_SECRET);

echo json_encode([
    'ok' => true,
    'token' => $token,
    'user' => $user,
]);
