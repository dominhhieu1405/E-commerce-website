<?php

declare(strict_types=1);

$pageTitle = 'Thanh toán';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$profile = [
  'full_name' => '',
  'email' => '',
  'phone' => '',
  'address' => '',
];

if (is_logged_in()) {
  $user = current_user();
  $profileStmt = $pdo->prepare('SELECT full_name, email, phone, address FROM users WHERE id = :id LIMIT 1');
  $profileStmt->execute(['id' => (int) ($user['id'] ?? 0)]);
  $fetchedProfile = $profileStmt->fetch();
  if ($fetchedProfile) {
    $profile = $fetchedProfile;
  }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8">
  <h1 class="text-2xl font-bold mb-6">Thanh toán</h1>
  <?php if (!is_logged_in()): ?>
    <p class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
      Bạn có thể đặt hàng mà không cần đăng nhập.
    </p>
  <?php endif; ?>

  <div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg border border-gray-200 p-5">
      <h2 class="font-semibold mb-3">Thông tin khách hàng</h2>
      <form action="/api/checkout_process.php" method="post" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
        <input id="cart-json" type="hidden" name="cart_json" value="[]" />

        <div>
          <label class="text-sm">Họ tên</label>
          <input id="customer-name" name="customer_name" required value="<?= htmlspecialchars((string) ($profile['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm">Email</label>
          <input id="customer-email" type="email" name="customer_email" required value="<?= htmlspecialchars((string) ($profile['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm">Điện thoại</label>
          <input id="customer-phone" name="customer_phone" required value="<?= htmlspecialchars((string) ($profile['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm">Địa chỉ</label>
          <textarea id="shipping-address" name="shipping_address" required rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"><?= htmlspecialchars((string) ($profile['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div>
          <label class="text-sm">Phương thức thanh toán</label>
          <select name="payment_method" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
            <option value="cod">COD</option>
            <option value="visa">Visa</option>
            <option value="bank_transfer">Chuyển khoản</option>
          </select>
        </div>

        <div>
          <label class="text-sm">Loại vận chuyển</label>
          <select id="shipping-method" name="shipping_method" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
            <option value="slow">Giao hàng chậm</option>
            <option value="standard" selected>Giao hàng bình thường</option>
            <option value="fast">Giao hàng nhanh</option>
            <option value="express">Giao hàng hỏa tốc</option>
          </select>
          <p class="mt-1 text-xs text-gray-500">Phí vận chuyển sẽ được cộng vào tổng thanh toán.</p>
        </div>

        <button class="w-full rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition">Đặt hàng</button>
      </form>
    </div>

    <aside class="bg-white rounded-lg border border-gray-200 p-5">
      <h2 class="font-semibold mb-3">Đơn hàng của bạn</h2>
      <div id="checkout-items" class="space-y-3"></div>
      <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between text-sm">
        <span>Phí vận chuyển</span>
        <span id="shipping-fee">0 ₫</span>
      </div>
      <div class="border-t border-gray-200 mt-4 pt-4 flex justify-between font-semibold">
        <span>Tổng thanh toán</span>
        <span id="checkout-total">0 ₫</span>
      </div>
    </aside>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
