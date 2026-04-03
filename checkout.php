<?php

declare(strict_types=1);

$pageTitle = 'Thanh toán';
require_once __DIR__ . '/includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8">
  <h1 class="text-2xl font-bold mb-6">Thanh toán</h1>

  <div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg border border-gray-200 p-5">
      <h2 class="font-semibold mb-3">Thông tin khách hàng</h2>
      <form action="/api/checkout_process.php" method="post" class="space-y-4">
        <input id="cart-json" type="hidden" name="cart_json" value="[]" />

        <div>
          <label class="text-sm">Họ tên</label>
          <input name="customer_name" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm">Email</label>
          <input type="email" name="customer_email" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm">Điện thoại</label>
          <input name="customer_phone" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm">Địa chỉ</label>
          <textarea name="shipping_address" required rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"></textarea>
        </div>

        <button class="w-full rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition">Đặt hàng</button>
      </form>
    </div>

    <aside class="bg-white rounded-lg border border-gray-200 p-5">
      <h2 class="font-semibold mb-3">Đơn hàng của bạn</h2>
      <div id="checkout-items" class="space-y-3"></div>
      <div class="border-t border-gray-200 mt-4 pt-4 flex justify-between font-semibold">
        <span>Tổng thanh toán</span>
        <span id="checkout-total">$0.00</span>
      </div>
    </aside>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
