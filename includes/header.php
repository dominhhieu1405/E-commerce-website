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
<body class="bg-gray-100 text-gray-900 min-h-screen flex flex-col" data-user-id="<?= (int) ($user['id'] ?? 0); ?>">

  <header class="sticky top-0 z-50 shadow-sm">
    <!-- Topbar -->
    <div class="bg-gray-900 text-white text-xs">
      <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between h-9 gap-3">
          <div class="hidden md:flex items-center gap-4 text-gray-300">
            <a href="#" class="hover:text-white transition">Kênh người bán</a>
            <a href="#" class="hover:text-white transition">Tải ứng dụng</a>
            <a href="#" class="hover:text-white transition">Kết nối</a>
          </div>

          <div class="flex items-center gap-4 ml-auto">
            <a href="#" class="hidden sm:inline hover:text-gray-200 transition">Thông báo</a>
            <a href="#" class="hidden sm:inline hover:text-gray-200 transition">Hỗ trợ</a>

            <?php if ($user): ?>
              <a href="/user/profile.php" class="hover:text-gray-200 transition">
                Xin chào, <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
              <a href="/auth/logout.php" class="hover:text-gray-200 transition">Đăng xuất</a>
            <?php else: ?>
              <a href="/auth/register.php" class="hover:text-gray-200 transition">Đăng ký</a>
              <span class="text-gray-500 hidden sm:inline">|</span>
              <a href="/auth/login.php" class="hover:text-gray-200 transition">Đăng nhập</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Main header -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 py-3">
        <div class="flex flex-col lg:flex-row lg:items-center gap-4">
          
          <!-- Logo -->
          <div class="flex items-center justify-between lg:w-auto">
            <a href="/index.php" class="flex items-center gap-3 shrink-0">
              <div class="w-11 h-11 rounded-2xl bg-black text-white flex items-center justify-center font-bold text-lg shadow-sm">
                M
              </div>
              <div>
                <div class="text-xl font-extrabold leading-tight">Minimal Store</div>
                <div class="text-xs text-gray-500 hidden sm:block">Mua sắm tiện lợi mỗi ngày</div>
              </div>
            </a>

            <!-- Mobile quick actions -->
            <div class="flex items-center gap-2 lg:hidden">
              <a href="/checkout.php" class="relative inline-flex items-center justify-center rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium hover:bg-gray-50 transition">
                Giỏ hàng
              </a>
            </div>
          </div>

          <!-- Search -->
          <div class="flex-1">
            <form method="get" action="/search.php" class="w-full relative">
              <div class="flex items-stretch rounded-2xl border-2 border-black bg-white overflow-hidden shadow-sm">
                <input
                  id="live-search-input"
                  type="text"
                  name="q"
                  placeholder="Tìm sản phẩm, thương hiệu, danh mục..."
                  value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                  class="flex-1 px-4 py-3 text-sm outline-none bg-transparent"
                />
                <button
                  type="submit"
                  class="bg-black text-white px-5 sm:px-6 text-sm font-semibold hover:opacity-90 transition"
                >
                  Tìm kiếm
                </button>
              </div>
              <div id="live-search-results" class="hidden absolute left-0 right-0 top-full mt-2 max-h-80 overflow-auto rounded-xl border border-gray-200 bg-white shadow-lg z-50"></div>
            </form>

            <div class="hidden md:flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 mt-2 px-1">
              <a href="/search.php?q=áo" class="hover:text-black transition">Áo</a>
              <a href="/search.php?q=quần" class="hover:text-black transition">Quần</a>
              <a href="/search.php?q=giày" class="hover:text-black transition">Giày</a>
              <a href="/search.php?q=túi+xách" class="hover:text-black transition">Túi xách</a>
              <a href="/search.php?q=phụ+kiện" class="hover:text-black transition">Phụ kiện</a>
            </div>
          </div>

          <!-- Desktop actions -->
          <div class="hidden lg:flex items-center gap-3">
            <?php if ($user): ?>
              <a href="/user/orders.php" class="px-4 py-2 rounded-xl border border-gray-300 text-sm font-medium hover:bg-gray-50 transition">
                Đơn đã mua
              </a>
              <a href="/user/profile.php" class="px-4 py-2 rounded-xl border border-gray-300 text-sm font-medium hover:bg-gray-50 transition">
                Tài khoản
              </a>
            <?php endif; ?>

            <?php if ($user && $user['role'] === 'admin'): ?>
              <a href="/admin/index.php" class="px-4 py-2 rounded-xl border border-gray-300 text-sm font-medium hover:bg-gray-50 transition">
                Admin
              </a>
            <?php endif; ?>

            <a href="/checkout.php" class="relative inline-flex items-center gap-2 rounded-2xl bg-black text-white px-4 py-3 text-sm font-semibold hover:opacity-90 transition shadow-sm">
              <span>🛒</span>
              <span>Giỏ hàng</span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <div class="bg-white">
      <div class="max-w-7xl mx-auto px-4">
        <nav class="flex items-center gap-2 sm:gap-4 overflow-x-auto whitespace-nowrap py-3 text-sm scrollbar-hide">
          <a href="/index.php" class="px-3 py-2 rounded-xl hover:bg-gray-100 transition font-medium">Trang chủ</a>
          <a href="/search.php?q=thời+trang" class="px-3 py-2 rounded-xl hover:bg-gray-100 transition">Thời trang</a>
          <a href="/search.php?q=điện+tử" class="px-3 py-2 rounded-xl hover:bg-gray-100 transition">Điện tử</a>
          <a href="/search.php?q=mỹ+phẩm" class="px-3 py-2 rounded-xl hover:bg-gray-100 transition">Mỹ phẩm</a>
          <a href="/search.php?q=gia+dụng" class="px-3 py-2 rounded-xl hover:bg-gray-100 transition">Gia dụng</a>
          <a href="/search.php?q=sách" class="px-3 py-2 rounded-xl hover:bg-gray-100 transition">Sách</a>
          <a href="/search.php?q=phụ+kiện" class="px-3 py-2 rounded-xl hover:bg-gray-100 transition">Phụ kiện</a>
          <a href="/search.php?q=khuyến+mãi" class="px-3 py-2 rounded-xl hover:bg-gray-100 transition text-red-600 font-medium">Khuyến mãi</a>
        </nav>
      </div>
    </div>
  </header>

  <main class="flex-1">
