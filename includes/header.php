<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'Minimal Store', ENT_QUOTES, 'UTF-8'); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col" data-user-id="<?= (int) ($user['id'] ?? 0); ?>">
  <header class="bg-white border-b border-gray-200 sticky top-0 z-20">
    <div class="max-w-6xl mx-auto px-4 py-2 space-y-2">
      <form method="get" action="/search.php" class="flex gap-2">
        <input type="text" name="q" placeholder="Tìm sản phẩm..." class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        <button class="rounded-lg bg-black text-white px-4 py-2 text-sm hover:opacity-80 transition">Tìm</button>
      </form>
      <div class="flex items-center justify-between gap-3">
        <a href="/index.php" class="font-semibold text-lg">Minimal Store</a>
        <nav class="flex items-center gap-4 text-sm flex-wrap justify-end">
          <a href="/index.php" class="hover:opacity-80 transition">Sản phẩm</a>
          <a href="/checkout.php" class="hover:opacity-80 transition">Thanh toán</a>
          <?php if ($user): ?>
            <a href="/user/profile.php" class="hover:opacity-80 transition">Tài khoản</a>
            <a href="/user/orders.php" class="hover:opacity-80 transition">Đơn đã mua</a>
          <?php endif; ?>
          <?php if ($user && $user['role'] === 'admin'): ?>
            <a href="/admin/index.php" class="hover:opacity-80 transition">Admin</a>
          <?php endif; ?>

          <?php if ($user): ?>
            <span class="text-gray-500">Xin chào, <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="/auth/logout.php" class="rounded-lg border border-black px-3 py-1 hover:opacity-80 transition">Đăng xuất</a>
          <?php else: ?>
            <a href="/auth/login.php" class="hover:opacity-80 transition">Đăng nhập</a>
            <a href="/auth/register.php" class="rounded-lg border border-black px-3 py-1 hover:opacity-80 transition">Đăng ký</a>
          <?php endif; ?>
        </nav>
      </div>
    </div>
  </header>
  <main class="flex-1">
