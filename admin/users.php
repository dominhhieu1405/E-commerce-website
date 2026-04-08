<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pagination.php';
require_login();
require_admin();

$pageTitle = 'Admin | Quản lý người dùng';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(422);
        exit('CSRF token không hợp lệ.');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    if ($id > 0 && in_array($action, ['ban', 'unban'], true)) {
        $stmt = $pdo->prepare('UPDATE users SET is_banned = :is_banned WHERE id = :id AND role <> :role_admin');
        $stmt->execute([
            'id' => $id,
            'is_banned' => $action === 'ban' ? 1 : 0,
            'role_admin' => 'admin',
        ]);
    }

    header('Location: /admin/users.php');
    exit;
}

$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$pagination = paginate($totalUsers, $perPage, $currentPage);

$listStmt = $pdo->prepare(
    'SELECT id, username, email, role, is_banned, created_at
     FROM users
     ORDER BY created_at DESC
     LIMIT :limit OFFSET :offset'
);
$listStmt->bindValue(':limit', (int) $pagination['per_page'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', (int) $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$users = $listStmt->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>
<section class="space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Quản lý người dùng</h1>
  </div>

  <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100">
      <tr>
        <th class="p-3 text-left">ID</th>
        <th class="p-3 text-left">Username</th>
        <th class="p-3 text-left">Email</th>
        <th class="p-3 text-left">Vai trò</th>
        <th class="p-3 text-left">Trạng thái</th>
        <th class="p-3 text-left">Ngày tạo</th>
        <th class="p-3 text-left">Thao tác</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($users as $targetUser): ?>
        <tr class="border-t border-gray-100">
          <td class="p-3"><?= (int) $targetUser['id']; ?></td>
          <td class="p-3"><?= htmlspecialchars((string) $targetUser['username'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3"><?= htmlspecialchars((string) $targetUser['email'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3"><?= htmlspecialchars((string) $targetUser['role'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3">
            <?php if ((int) $targetUser['is_banned'] === 1): ?>
              <span class="rounded bg-red-100 text-red-700 px-2 py-1 text-xs font-medium">Đã cấm</span>
            <?php else: ?>
              <span class="rounded bg-green-100 text-green-700 px-2 py-1 text-xs font-medium">Đang hoạt động</span>
            <?php endif; ?>
          </td>
          <td class="p-3"><?= htmlspecialchars((string) $targetUser['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3">
            <?php if ((string) $targetUser['role'] === 'admin'): ?>
              <span class="text-xs text-gray-500">Không thao tác</span>
            <?php else: ?>
              <form method="post" onsubmit="return confirm('Xác nhận thay đổi trạng thái user này?')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
                <input type="hidden" name="id" value="<?= (int) $targetUser['id']; ?>" />
                <?php if ((int) $targetUser['is_banned'] === 1): ?>
                  <input type="hidden" name="action" value="unban" />
                  <button class="rounded border px-3 py-1 hover:opacity-80 transition">Bỏ cấm</button>
                <?php else: ?>
                  <input type="hidden" name="action" value="ban" />
                  <button class="rounded border border-red-300 text-red-700 px-3 py-1 hover:opacity-80 transition">Cấm user</button>
                <?php endif; ?>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php render_pagination($pagination); ?>
</section>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
