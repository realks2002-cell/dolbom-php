<?php
/**
 * POST /api/manager/login
 * Body: { "phone": "...", "password": "..." }
 * 전화번호와 비밀번호로 매니저 로그인. JWT + user 반환.
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/api/cors.php';
require_once dirname(__DIR__, 2) . '/includes/jwt.php';
require_once dirname(__DIR__, 2) . '/includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// normalize_phone() 함수는 includes/security.php에 정의되어 있음

$raw = file_get_contents('php://input') ?: '{}';
$body = json_decode($raw, true) ?? [];
$phone = trim((string) ($body['phone'] ?? ''));
$password = (string) ($body['password'] ?? '');

if ($phone === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '전화번호와 비밀번호를 입력해주세요.']);
    exit;
}

// 전화번호 정규화
$normalizedPhone = normalize_phone($phone);

$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

// 전화번호로 매니저 조회 (하이픈 포함/미포함 모두 검색)
$st = $pdo->prepare('SELECT id, name, phone, password_hash FROM managers');
$st->execute();
$managers = $st->fetchAll();

$manager = null;
foreach ($managers as $m) {
    $dbPhone = normalize_phone($m['phone']);
    if ($dbPhone === $normalizedPhone) {
        $manager = $m;
        break;
    }
}

if (!$manager) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => '전화번호 또는 비밀번호가 올바르지 않습니다.']);
    exit;
}

if (empty($manager['password_hash'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => '비밀번호가 설정되지 않은 계정입니다. 관리자에게 문의하세요.']);
    exit;
}

if (!password_verify($password, $manager['password_hash'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => '전화번호 또는 비밀번호가 올바르지 않습니다.']);
    exit;
}

// JWT 토큰 생성
$payload = [
    'sub' => $manager['id'],
    'role' => 'manager',
    'exp' => time() + (30 * 24 * 3600), // 30일
];
$token = jwt_encode($payload, API_JWT_SECRET);

// 사용자 정보 반환 (비밀번호 제외)
unset($manager['password_hash']);
$user = [
    'id' => $manager['id'],
    'name' => $manager['name'],
    'phone' => $manager['phone'],
    'role' => 'manager',
];

echo json_encode([
    'ok' => true,
    'token' => $token,
    'user' => $user,
]);
