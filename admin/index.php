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

$dailyStatsStmt = $pdo->query(
    "SELECT DATE(created_at) AS order_date,
            COUNT(*) AS total_orders,
            COALESCE(SUM(total_price), 0) AS total_revenue
     FROM orders
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
     GROUP BY DATE(created_at)
     ORDER BY order_date ASC"
);
$dailyStats = $dailyStatsStmt->fetchAll();

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
    <a href="/admin/products.php" class="rounded-lg border border-black px-4 py-2 hover:opacity-80 transition">Danh sách sản phẩm</a>
    <a href="/admin/product_form.php" class="rounded-lg border border-black px-4 py-2 hover:opacity-80 transition">Thêm sản phẩm</a>
    <a href="/admin/categories.php" class="rounded-lg border border-black px-4 py-2 hover:opacity-80 transition">Quản lý danh mục</a>
    <a href="/admin/orders.php" class="rounded-lg border border-black px-4 py-2 hover:opacity-80 transition">Quản lý đơn hàng</a>
  </div>

  <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6 space-y-4">
    <h2 class="text-lg font-semibold">Thống kê đơn hàng theo ngày (14 ngày gần nhất)</h2>
    <canvas id="orders-chart" height="120"></canvas>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2 text-left">Ngày</th>
            <th class="p-2 text-left">Số đơn</th>
            <th class="p-2 text-left">Doanh thu</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($dailyStats as $daily): ?>
            <tr class="border-t border-gray-100">
              <td class="p-2"><?= htmlspecialchars((string) $daily['order_date'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="p-2"><?= (int) $daily['total_orders']; ?></td>
              <td class="p-2">$<?= number_format((float) $daily['total_revenue'], 2); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const dailyStats = <?= json_encode($dailyStats, JSON_UNESCAPED_UNICODE); ?>;
  const labels = dailyStats.map((item) => item.order_date);
  const ordersData = dailyStats.map((item) => Number(item.total_orders || 0));
  const revenueData = dailyStats.map((item) => Number(item.total_revenue || 0));
  const canvas = document.getElementById('orders-chart');
  if (canvas && labels.length) {
    new Chart(canvas, {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: 'Số đơn',
            data: ordersData,
            borderColor: '#111827',
            backgroundColor: 'rgba(17, 24, 39, 0.12)',
            yAxisID: 'yOrders',
            tension: 0.3,
          },
          {
            label: 'Doanh thu',
            data: revenueData,
            borderColor: '#059669',
            backgroundColor: 'rgba(5, 150, 105, 0.12)',
            yAxisID: 'yRevenue',
            tension: 0.3,
          },
        ],
      },
      options: {
        responsive: true,
        scales: {
          yOrders: { position: 'left', beginAtZero: true },
          yRevenue: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } },
        },
      },
    });
  }
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
