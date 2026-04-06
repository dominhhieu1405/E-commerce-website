<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../includes/format.php';
require_login();
require_admin();
$pageTitle = 'Admin | Đơn hàng';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(422);
        exit('CSRF token không hợp lệ.');
    }

    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? 'pending');
    $allowed = ['pending', 'completed', 'cancelled'];

    if ($orderId > 0 && in_array($status, $allowed, true)) {
        $update = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $update->execute(['status' => $status, 'id' => $orderId]);
    }

    header('Location: /admin/orders.php');
    exit;
}

$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$totalOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$pagination = paginate($totalOrders, $perPage, $currentPage);

$ordersStmt = $pdo->prepare('SELECT * FROM orders ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$ordersStmt->bindValue(':limit', (int) $pagination['per_page'], PDO::PARAM_INT);
$ordersStmt->bindValue(':offset', (int) $pagination['offset'], PDO::PARAM_INT);
$ordersStmt->execute();
$orders = $ordersStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8">
  <div class="flex items-center justify-between mb-5">
    <h1 class="text-2xl font-bold">Quản lý đơn hàng</h1>
    <a href="/admin/index.php" class="text-sm hover:opacity-80 transition">← Dashboard</a>
  </div>

  <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100">
      <tr>
        <th class="p-3 text-left">Mã</th>
        <th class="p-3 text-left">Khách hàng</th>
        <th class="p-3 text-left">Tổng</th>
        <th class="p-3 text-left">Trạng thái</th>
        <th class="p-3 text-left">Ngày</th>
        <th class="p-3 text-left">Cập nhật</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($orders as $order): ?>
        <tr class="border-t border-gray-100">
          <td class="p-3">#<?= (int) $order['id']; ?></td>
          <td class="p-3"><?= htmlspecialchars((string) $order['customer_name'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3"><?= format_currency_vnd((float) $order['total_price']); ?></td>
          <td class="p-3 capitalize"><?= htmlspecialchars((string) $order['status'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3"><?= htmlspecialchars((string) $order['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3">
            <form method="post" class="flex items-center gap-2">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>" />
              <select name="status" class="rounded-lg border border-gray-300 px-2 py-1">
                <?php foreach (['pending', 'completed', 'cancelled'] as $status): ?>
                  <option value="<?= $status; ?>" <?= $status === $order['status'] ? 'selected' : ''; ?>><?= ucfirst($status); ?></option>
                <?php endforeach; ?>
              </select>
              <button class="rounded border px-2 py-1 hover:opacity-80 transition">Lưu</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php render_pagination($pagination); ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
