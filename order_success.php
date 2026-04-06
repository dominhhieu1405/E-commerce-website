<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/format.php';
require_login();

$orderId = (int) ($_GET['order_id'] ?? 0);
$pageTitle = 'Đặt hàng thành công';

$stmt = $pdo->prepare('SELECT id, total_price, payment_method, status FROM orders WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute(['id' => $orderId, 'user_id' => (int) (current_user()['id'] ?? 0)]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    exit('Không tìm thấy đơn hàng.');
}

require_once __DIR__ . '/includes/header.php';
?>
<section id="order-success-sync" class="max-w-2xl mx-auto px-4 py-12">
  <div class="bg-white rounded-lg border border-gray-200 p-8 text-center space-y-4">
    <div class="text-5xl">✅</div>
    <h1 class="text-3xl font-bold">Đặt hàng thành công!</h1>
    <p class="text-gray-600">Mã đơn #<?= (int) $order['id']; ?> đã được ghi nhận.</p>
    <div class="bg-gray-50 rounded-lg p-4 text-left text-sm space-y-1">
      <p><strong>Tổng tiền:</strong> <?= format_currency_vnd((float) $order['total_price']); ?></p>
      <p><strong>Phương thức:</strong> <?= htmlspecialchars((string) $order['payment_method'], ENT_QUOTES, 'UTF-8'); ?></p>
      <p><strong>Trạng thái:</strong> <?= htmlspecialchars((string) $order['status'], ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="flex flex-wrap gap-3 justify-center">
      <a href="/user/orders.php" class="rounded-lg border border-black px-4 py-2 hover:opacity-80 transition">Xem đơn hàng</a>
      <a href="/review.php?order_id=<?= (int) $order['id']; ?>" class="rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition">Đánh giá đơn hàng</a>
      <a href="/index.php" class="rounded-lg border border-black px-4 py-2 hover:opacity-80 transition">Tiếp tục mua</a>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
