<?php
/**
 * 매니저 관리 (권한 + 로그까지만)
 * URL: /admin/managers
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

require_admin();

$base = rtrim(BASE_URL, '/');
$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

// 검색 및 필터
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (name LIKE ? OR phone LIKE ? OR address1 LIKE ? OR specialty LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM managers WHERE {$where}");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT * FROM managers WHERE {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$managers = $stmt->fetchAll();

$pageTitle = '매니저 관리 - ' . APP_NAME;
ob_start();
?>
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">매니저 관리</h1>
    </div>
    
    <!-- 검색 -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <form method="get" class="flex gap-2">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                   placeholder="이름, 전화번호, 주소, 특기로 검색" 
                   class="flex-1 min-h-[44px] px-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            <button type="submit" class="min-h-[44px] px-6 bg-primary text-white rounded-lg hover:opacity-90">검색</button>
            <?php if ($search): ?>
            <a href="<?= $base ?>/admin/managers" class="min-h-[44px] px-6 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center">초기화</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- 매니저 목록 -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">이름</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">주민번호</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">전화번호</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">주소</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">계좌번호</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">특기</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">등록일</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($managers) > 0): ?>
                    <?php foreach ($managers as $manager): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($manager['id']) ?></td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= htmlspecialchars($manager['name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($manager['ssn']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($manager['phone']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <?= htmlspecialchars($manager['address1']) ?>
                            <?php if ($manager['address2']): ?>
                            <br><span class="text-gray-500 text-xs"><?= htmlspecialchars($manager['address2']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($manager['account_number']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($manager['specialty'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($manager['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">매니저가 없습니다.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 페이지네이션 -->
        <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-700">
                총 <?= number_format($total) ?>명 중 <?= number_format($offset + 1) ?>-<?= number_format(min($offset + $perPage, $total)) ?>명 표시
            </div>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="min-h-[44px] px-4 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center">이전</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="min-h-[44px] px-4 border border-gray-300 rounded-lg hover:bg-gray-50 inline-flex items-center">다음</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/admin-layout.php';
?>
