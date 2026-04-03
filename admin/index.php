<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_admin();
$pageTitle = 'Admin Dashboard';

$summaryStmt = $pdo->query("SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_price),0) AS revenue FROM orders WHERE status = 'completed'");
$summary = $summaryStmt->fetch() ?: ['total_orders' => 0, 'revenue' => 0];

$newOrdersStmt = $pdo->query("SELECT COUNT(*) AS pending_orders FROM orders WHERE status = 'pending'");
$newOrders = $newOrdersStmt->fetch() ?: ['pending_orders' => 0];

require_once __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8 space-y-6">
  <h1 class="text-2xl font-bold">Admin Dashboard</h1>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <article class="bg-white rounded-lg border border-gray-200 p-4">
      <p class="text-sm text-gray-500">Doanh thu hoàn thành</p>
      <p class="text-2xl font-semibold mt-2">$<?= number_format((float) $summary['revenue'], 2); ?></p>
    </article>
    <article class="bg-white rounded-lg border border-gray-200 p-4">
      <p class="text-sm text-gray-500">Đơn hoàn thành</p>
      <p class="text-2xl font-semibold mt-2"><?= (int) $summary['total_orders']; ?></p>
    </article>
    <article class="bg-white rounded-lg border border-gray-200 p-4">
      <p class="text-sm text-gray-500">Đơn mới (pending)</p>
      <p class="text-2xl font-semibold mt-2"><?= (int) $newOrders['pending_orders']; ?></p>
    </article>
  </div>

  <div class="flex gap-3">
    <a href="/admin/products.php" class="rounded-lg border border-black px-4 py-2 hover:opacity-80 transition">Quản lý sản phẩm</a>
    <a href="/admin/orders.php" class="rounded-lg border border-black px-4 py-2 hover:opacity-80 transition">Quản lý đơn hàng</a>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
