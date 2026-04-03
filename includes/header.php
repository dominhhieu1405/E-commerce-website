<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'Minimal Store', ENT_QUOTES, 'UTF-8'); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">
  <header class="bg-white border-b border-gray-200 sticky top-0 z-20">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
      <a href="/index.php" class="font-semibold text-lg">Minimal Store</a>
      <nav class="flex items-center gap-4 text-sm">
        <a href="/index.php" class="hover:opacity-80 transition">Sản phẩm</a>
        <a href="/checkout.php" class="hover:opacity-80 transition">Thanh toán</a>
        <a href="/search.php" class="hover:opacity-80 transition">Tìm kiếm</a>
        <a href="/admin/index.php" class="hover:opacity-80 transition">Admin</a>
      </nav>
    </div>
  </header>
  <main class="flex-1">
