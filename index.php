<?php
/**
 * 진입점 및 라우팅
 * - config 로드 후 route에 따라 pages/* include
 */
require_once __DIR__ . '/config/app.php';

$raw = trim((string) ($_GET['route'] ?? ''), '/');
$route = $raw !== '' ? $raw : 'index';

// route → 파일 매핑 (PRD URL 구조)
$map = [
    'api/address-search' => 'api/address-search.php',
    'api/address-suggest' => 'api/address-suggest.php',
    'api/requests/save-temp' => 'api/requests/save-temp.php',
    'api/bookings/cancel' => 'api/bookings/cancel.php',
    'api/auth/login' => 'api/auth/login.php',
    'api/manager/login' => 'api/manager/login.php',
    'api/manager/me' => 'api/manager/me.php',
    'api/manager/requests' => 'api/manager/requests.php',
    'api/manager/applications' => 'api/manager/applications.php',
    'api/manager/schedule' => 'api/manager/schedule.php',
    'api/manager/apply' => 'api/manager/apply.php',
    'api/manager/register-token' => 'api/manager/register-token.php',
    'api/payments/refund' => 'api/payments/refund.php',
    'api/test/send-push' => 'api/test/send-push.php',
    'api/test/send-push-to-all' => 'api/test/send-push-to-all.php',
    'api/test/register-token-by-phone' => 'api/test/register-token-by-phone.php',
    'test/push-notification' => 'pages/test/push-notification.php',
    'database/export' => 'database/export.php',
    'database/download' => 'database/download.php',
    'index' => 'pages/index.php',
    'about' => 'pages/about.php',
    'service-guide' => 'pages/service-guide.php',
    'faq' => 'pages/faq.php',
    'auth/login' => 'pages/auth/login.php',
    'auth/logout' => 'pages/auth/logout.php',
    'auth/signup' => 'pages/auth/signup.php',
    'requests/new' => 'pages/requests/new.php',
    'requests/detail' => 'pages/requests/detail.php',
    'bookings' => 'pages/bookings/index.php',
    'bookings/guest-check' => 'pages/bookings/guest-check.php',
    'bookings/review' => 'pages/bookings/review.php',
    'payment/success' => 'pages/payment/success.php',
    'payment/fail' => 'pages/payment/fail.php',
    'payment/register-card' => 'pages/payment/register-card.php',
    'manager/login' => 'pages/manager/login.php',
    'manager/signup' => 'pages/manager/signup.php',
    'manager/logout' => 'pages/manager/logout.php',
    'manager/dashboard' => 'pages/manager/dashboard.php',
    'manager/recruit' => 'pages/manager/recruit.php',
    'manager/profile' => 'pages/manager/profile.php',
    'manager/requests' => 'pages/manager/requests.php',
    'manager/applications' => 'pages/manager/applications.php',
    'manager/schedule' => 'pages/manager/schedule.php',
    'manager/earnings' => 'pages/manager/earnings.php',
    'manager/check-manager' => 'pages/manager/check-manager.php',
    'manager/reset-password' => 'pages/manager/reset-password.php',
    'admin' => 'pages/admin/index.php',
    'admin/users' => 'pages/admin/users.php',
    'admin/managers' => 'pages/admin/managers.php',
    'admin/requests' => 'pages/admin/requests.php',
    'admin/payments' => 'pages/admin/payments.php',
    'admin/refunds' => 'pages/admin/refunds.php',
    'admin/refund-info' => 'pages/admin/refund-info.php',
    'admin/revenue' => 'pages/admin/revenue.php',
];

$file = $map[$route] ?? null;
if ($file === null || !is_file(__DIR__ . '/' . $file)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404</title></head><body><h1>404 Not Found</h1><p>' . htmlspecialchars($route) . '</p></body></html>';
    return;
}

require __DIR__ . '/' . $file;
