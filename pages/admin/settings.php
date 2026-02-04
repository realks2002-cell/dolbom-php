<?php
/**
 * 관리자 설정
 * URL: /admin/settings
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

require_admin();

$base = rtrim(BASE_URL, '/');
$pdo = require dirname(__DIR__, 2) . '/database/connect.php';

$message = '';
$error = '';
$currentAdminId = $_SESSION['admin_id'] ?? '';

// 관리자 추가 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $adminId = trim($_POST['admin_id'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($adminId) || empty($password)) {
        $error = '관리자 ID와 비밀번호를 모두 입력해주세요.';
    } elseif (strlen($adminId) < 3 || strlen($adminId) > 50) {
        $error = '관리자 ID는 3자 이상 50자 이하여야 합니다.';
    } elseif (strlen($password) < 6) {
        $error = '비밀번호는 6자 이상이어야 합니다.';
    } else {
        try {
            // 중복 확인
            $stmt = $pdo->prepare('SELECT id FROM admins WHERE admin_id = ?');
            $stmt->execute([$adminId]);
            if ($stmt->fetch()) {
                $error = '이미 존재하는 관리자 ID입니다.';
            } else {
                // 비밀번호 해시 생성
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // 관리자 추가
                $stmt = $pdo->prepare('INSERT INTO admins (admin_id, password_hash, created_at) VALUES (?, ?, NOW())');
                $stmt->execute([$adminId, $passwordHash]);
                
                $message = '관리자가 추가되었습니다.';
            }
        } catch (Exception $e) {
            error_log('Admin add error: ' . $e->getMessage());
            $error = '관리자 추가 중 오류가 발생했습니다.';
        }
    }
}

// 관리자 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $adminIdToDelete = trim($_POST['admin_id'] ?? '');
    
    if (empty($adminIdToDelete)) {
        $error = '삭제할 관리자 ID가 없습니다.';
    } elseif ($adminIdToDelete === $currentAdminId) {
        $error = '자기 자신은 삭제할 수 없습니다.';
    } else {
        try {
            // 관리자 존재 확인
            $stmt = $pdo->prepare('SELECT id FROM admins WHERE admin_id = ?');
            $stmt->execute([$adminIdToDelete]);
            if (!$stmt->fetch()) {
                $error = '존재하지 않는 관리자입니다.';
            } else {
                // 관리자 삭제
                $stmt = $pdo->prepare('DELETE FROM admins WHERE admin_id = ?');
                $stmt->execute([$adminIdToDelete]);
                
                $message = '관리자가 삭제되었습니다.';
            }
        } catch (Exception $e) {
            error_log('Admin delete error: ' . $e->getMessage());
            $error = '관리자 삭제 중 오류가 발생했습니다.';
        }
    }
}

// 관리자 목록 조회
$stmt = $pdo->query('SELECT id, admin_id, created_at FROM admins ORDER BY created_at DESC');
$admins = $stmt->fetchAll();

$pageTitle = '관리자 설정 - ' . APP_NAME;
ob_start();
?>
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">관리자 설정</h1>
    </div>
    
    <!-- 메시지 표시 -->
    <?php if ($message): ?>
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <!-- 관리자 추가 폼 -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">관리자 추가</h2>
        <form method="post" class="space-y-4" autocomplete="off">
            <input type="hidden" name="action" value="add">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="admin_id" class="block text-sm font-medium text-gray-700 mb-2">
                        관리자 ID <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="admin_id" 
                           name="admin_id" 
                           value=""
                           required
                           minlength="3"
                           maxlength="50"
                           autocomplete="off"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="관리자 ID 입력">
                    <p class="text-xs text-gray-500 mt-1">3자 이상 50자 이하</p>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        비밀번호 <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           value=""
                           required
                           minlength="6"
                           autocomplete="new-password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="비밀번호 입력">
                    <p class="text-xs text-gray-500 mt-1">6자 이상</p>
                </div>
                <div class="flex items-end">
                    <button type="submit" 
                            class="min-h-[44px] w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        관리자 추가
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- 관리자 목록 -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold">관리자 목록</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">관리자 ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">생성일시</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">작업</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($admins) > 0): ?>
                    <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?= htmlspecialchars($admin['admin_id']) ?>
                            <?php if ($admin['admin_id'] === $currentAdminId): ?>
                            <span class="ml-2 px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">현재 로그인</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?= htmlspecialchars($admin['created_at']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-center">
                            <?php if ($admin['admin_id'] !== $currentAdminId): ?>
                            <form method="post" class="inline" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="admin_id" value="<?= htmlspecialchars($admin['admin_id']) ?>">
                                <button type="submit" 
                                        class="min-h-[44px] px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm font-medium">
                                    삭제
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-gray-400 text-sm">삭제 불가</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-gray-500">관리자가 없습니다.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$layoutContent = ob_get_clean();
require dirname(__DIR__, 2) . '/components/admin-layout.php';
?>
