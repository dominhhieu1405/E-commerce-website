<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$orderId = (int) ($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
$pageTitle = 'Chuyển khoản ngân hàng';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(422);
        exit('CSRF token không hợp lệ.');
    }

    $update = $pdo->prepare("UPDATE orders SET payment_status = 'paid', status = 'completed' WHERE id = :id AND user_id = :user_id");
    $update->execute(['id' => $orderId, 'user_id' => (int) current_user()['id']]);
    header('Location: /order_success.php?order_id=' . $orderId);
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="max-w-md mx-auto px-4 py-10">
  <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4 text-center">
    <h1 class="text-2xl font-bold">Chuyển khoản</h1>
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=bank_transfer_order_<?= (int) $orderId; ?>" alt="QR" class="mx-auto rounded-lg border" />
    <p class="text-sm text-gray-600">Nội dung chuyển khoản: <strong>ORDER_<?= (int) $orderId; ?></strong></p>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
      <input type="hidden" name="order_id" value="<?= (int) $orderId; ?>" />
      <button class="rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition">Tôi đã chuyển khoản</button>
    </form>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
