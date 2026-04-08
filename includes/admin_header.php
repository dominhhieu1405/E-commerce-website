<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$user = current_user();
if (!$user || ($user['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('403 Forbidden');
}

$adminNavItems = [
    '/admin/index.php' => 'Tổng quan',
    '/admin/products.php' => 'Sản phẩm',
    '/admin/product_form.php' => 'Thêm sản phẩm',
    '/admin/categories.php' => 'Danh mục',
    '/admin/orders.php' => 'Đơn hàng',
    '/admin/users.php' => 'Người dùng',
    '/admin/reviews.php' => 'Đánh giá',
];

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'Admin Dashboard', ENT_QUOTES, 'UTF-8'); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 min-h-screen">
  <div class="min-h-screen md:flex">
    <aside class="bg-gray-950 text-gray-100 md:w-72 p-4 md:p-5 shrink-0">
      <a href="/admin/index.php" class="block text-xl font-bold">Admin Dashboard</a>
      <p class="text-xs text-gray-400 mt-1">Xin chào, <?= htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8'); ?></p>

      <nav class="mt-6 space-y-1">
        <?php foreach ($adminNavItems as $url => $label): ?>
          <?php $active = str_starts_with($currentPath, $url) || ($url === '/admin/product_form.php' && str_starts_with($currentPath, '/admin/product_form.php')); ?>
          <a
            href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
            class="block rounded-lg px-3 py-2 text-sm transition <?= $active ? 'bg-white text-gray-900 font-semibold' : 'text-gray-200 hover:bg-gray-800'; ?>"
          ><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endforeach; ?>
      </nav>

      <div class="mt-6 border-t border-gray-800 pt-4 text-sm space-y-2">
        <a href="/index.php" class="block text-gray-200 hover:text-white">← Về trang bán hàng</a>
        <a href="/auth/logout.php" class="block text-gray-200 hover:text-white">Đăng xuất</a>
      </div>
    </aside>

    <main class="flex-1 p-4 md:p-8 space-y-6">
