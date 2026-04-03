<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$orderId = (int) ($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
$pageTitle = 'Thanh toán Visa';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        $message = 'CSRF token không hợp lệ.';
    } else {
        $update = $pdo->prepare("UPDATE orders SET payment_status = 'paid', status = 'completed' WHERE id = :id AND user_id = :user_id");
        $update->execute(['id' => $orderId, 'user_id' => (int) current_user()['id']]);
        header('Location: /order_success.php?order_id=' . $orderId);
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="max-w-md mx-auto px-4 py-10">
  <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
    <h1 class="text-2xl font-bold">Nhập thông tin Visa</h1>
    <?php if ($message): ?><p class="text-sm text-red-700 bg-red-50 rounded p-2"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    <form method="post" class="space-y-3">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
      <input type="hidden" name="order_id" value="<?= (int) $orderId; ?>" />
      <input placeholder="Số thẻ" required class="w-full rounded-lg border border-gray-300 px-3 py-2" />
      <div class="grid grid-cols-2 gap-3">
        <input placeholder="MM/YY" required class="rounded-lg border border-gray-300 px-3 py-2" />
        <input placeholder="CVV" required class="rounded-lg border border-gray-300 px-3 py-2" />
      </div>
      <button class="w-full rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition">Thanh toán Visa</button>
    </form>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
