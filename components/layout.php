<?php
/**
 * 공통 레이아웃 (랜딩 디자인 통일)
 * - Pretendard, Tailwind CDN, AOS, Lucide
 * - header, main, footer
 * 사용: $pageTitle, $hideHeader, $hideFooter, $mainClass, $layoutContent
 */
if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config/app.php';
}
require_once dirname(__DIR__) . '/includes/auth.php';
$pageTitle = $pageTitle ?? APP_NAME;
$hideHeader = $hideHeader ?? false;
$hideFooter = $hideFooter ?? false;
$mainClass = $mainClass ?? 'min-h-screen pt-24';
$base = rtrim(BASE_URL, '/');
$layoutContent = $layoutContent ?? '';
$userRole = $userRole ?? null;
$currentUser = $currentUser ?? null;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="믿을 수 있는 병원동행과 돌봄 서비스 - <?= htmlspecialchars(APP_NAME) ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Fonts - Pretendard -->
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Pretendard', '-apple-system', 'BlinkMacSystemFont', 'system-ui', 'Roboto', 'Helvetica Neue', 'Segoe UI', 'Apple SD Gothic Neo', 'Noto Sans KR', 'Malgun Gothic', 'sans-serif'],
                    },
                    colors: {
                        primary: { DEFAULT: '#2563eb' },
                        orange: { 50: '#fff7ed', 100: '#ffedd5', 500: '#f97316', 600: '#ea580c' },
                        teal: { 50: '#f0fdfa', 600: '#0d9488', 800: '#115e59', 900: '#134e4a' }
                    }
                }
            }
        }
    </script>

    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="stylesheet" href="<?= $base ?>/assets/css/custom.css">
    <style>html { scroll-behavior: smooth; }</style>
    <?php if (isset($additionalHead)) echo $additionalHead; ?>
</head>
<body class="bg-stone-50 text-gray-800 font-sans selection:bg-orange-200 antialiased">
<?php if (!$hideHeader) { require __DIR__ . '/header.php'; } ?>
<main class="<?= htmlspecialchars($mainClass) ?>" role="main">
    <?= $layoutContent ?>
</main>
<?php if (!$hideFooter) { require __DIR__ . '/footer.php'; } ?>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="<?= $base ?>/assets/js/main.js"></script>
<script>
    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof AOS !== 'undefined') AOS.init({ once: true, offset: 50, duration: 800 });
    var navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('bg-white/90', 'backdrop-blur-md', 'shadow-sm', 'py-3');
                navbar.classList.remove('py-5');
            } else {
                navbar.classList.remove('bg-white/90', 'backdrop-blur-md', 'shadow-sm', 'py-3');
                navbar.classList.add('py-5');
            }
        });
    }
    var mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    var mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
            var icon = mobileMenuToggle.querySelector('i[data-lucide="menu"]');
            if (icon) {
                icon.setAttribute('data-lucide', mobileMenu.classList.contains('hidden') ? 'menu' : 'x');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        });
    }
</script>
<?php if (isset($additionalScripts)) echo $additionalScripts; ?>
</body>
</html>
