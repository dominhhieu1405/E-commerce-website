<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pagination.php';
require_login();

$pageTitle = 'Đơn hàng đã mua';
$user = current_user();

$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = :user_id');
$countStmt->execute(['user_id' => (int) $user['id']]);
$totalOrders = (int) $countStmt->fetchColumn();
$pagination = paginate($totalOrders, $perPage, $currentPage);

$stmt = $pdo->prepare(
    'SELECT o.*, r.id AS review_id
     FROM orders o
     LEFT JOIN reviews r ON r.order_id = o.id
     WHERE o.user_id = :user_id
     ORDER BY o.created_at DESC
     LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
$stmt->bindValue(':limit', (int) $pagination['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<section class="max-w-5xl mx-auto px-4 py-8 space-y-4">
  <h1 class="text-2xl font-bold">Đơn hàng của bạn</h1>
  <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100">
      <tr>
        <th class="p-3 text-left">Mã đơn</th>
        <th class="p-3 text-left">Ngày</th>
        <th class="p-3 text-left">Tổng</th>
        <th class="p-3 text-left">Thanh toán</th>
        <th class="p-3 text-left">Trạng thái</th>
        <th class="p-3 text-left">Đánh giá</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($orders as $order): ?>
        <tr class="border-t border-gray-100">
          <td class="p-3">#<?= (int) $order['id']; ?></td>
          <td class="p-3"><?= htmlspecialchars((string) $order['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3">$<?= number_format((float) $order['total_price'], 2); ?></td>
          <td class="p-3"><?= htmlspecialchars((string) $order['payment_method'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3"><?= htmlspecialchars((string) $order['status'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3">
            <?php if ($order['review_id']): ?>
              <span class="text-gray-500">Đã đánh giá</span>
            <?php else: ?>
              <a href="/review.php?order_id=<?= (int) $order['id']; ?>" class="rounded border px-3 py-1 hover:opacity-80 transition">Đánh giá</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php render_pagination($pagination); ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
