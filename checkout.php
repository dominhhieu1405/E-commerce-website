<?php

declare(strict_types=1);

$pageTitle = 'Checkout | The Editorial Atelier';
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
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <div class="flex items-end justify-between mb-6">
    <div>
      <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Secure checkout</p>
      <h1 class="text-5xl font-semibold tracking-tight">Shipping Information</h1>
    </div>
  </div>

  <?php if (!is_logged_in()): ?>
    <p class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      Bạn có thể đặt hàng mà không cần đăng nhập.
    </p>
  <?php endif; ?>

  <div class="grid lg:grid-cols-[minmax(0,1fr)_400px] gap-6">
    <div class="bg-white rounded-3xl border border-slate-200 p-6">
      <form action="/api/checkout_process.php" method="post" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
        <input id="cart-json" type="hidden" name="cart_json" value="[]" />

        <div>
          <label class="text-xs uppercase tracking-[0.14em] text-slate-500">Họ tên</label>
          <input id="customer-name" name="customer_name" required value="<?= htmlspecialchars((string) ($profile['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3" />
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="text-xs uppercase tracking-[0.14em] text-slate-500">Email</label>
            <input id="customer-email" type="email" name="customer_email" required value="<?= htmlspecialchars((string) ($profile['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3" />
          </div>
          <div>
            <label class="text-xs uppercase tracking-[0.14em] text-slate-500">Điện thoại</label>
            <input id="customer-phone" name="customer_phone" required value="<?= htmlspecialchars((string) ($profile['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3" />
          </div>
        </div>

        <div>
          <label class="text-xs uppercase tracking-[0.14em] text-slate-500">Địa chỉ</label>
          <textarea id="shipping-address" name="shipping_address" required rows="3" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3"><?= htmlspecialchars((string) ($profile['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="text-xs uppercase tracking-[0.14em] text-slate-500">Phương thức thanh toán</label>
            <select name="payment_method" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
              <option value="cod">COD</option>
              <option value="visa">Visa</option>
              <option value="bank_transfer">Chuyển khoản</option>
            </select>
          </div>

          <div>
            <label class="text-xs uppercase tracking-[0.14em] text-slate-500">Vận chuyển</label>
            <select id="shipping-method" name="shipping_method" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
              <option value="slow">Giao hàng chậm</option>
              <option value="standard" selected>Giao hàng bình thường</option>
              <option value="fast">Giao hàng nhanh</option>
              <option value="express">Giao hàng hỏa tốc</option>
            </select>
          </div>
        </div>

        <p class="text-xs text-slate-500">Phí vận chuyển sẽ được cộng vào tổng thanh toán.</p>
        <button class="w-full rounded-xl bg-gradient-to-r from-accent to-orange-400 text-white px-4 py-3 font-semibold hover:opacity-90 transition">Complete Order</button>
      </form>
    </div>

    <aside class="bg-mist rounded-3xl border border-slate-200 p-6 h-fit sticky top-24">
      <h2 class="text-3xl font-semibold mb-4">Order Summary</h2>
      <div id="checkout-items" class="space-y-3"></div>
      <div class="mt-5 pt-4 border-t border-slate-300/70 flex justify-between text-sm">
        <span>Phí vận chuyển</span>
        <span id="shipping-fee">0 ₫</span>
      </div>
      <div class="border-t border-slate-300/70 mt-4 pt-4 flex justify-between text-lg font-semibold">
        <span>Tổng thanh toán</span>
        <span id="checkout-total" class="text-accent">0 ₫</span>
      </div>
    </aside>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
