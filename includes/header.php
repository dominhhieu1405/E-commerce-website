<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$user = current_user();
$currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/index.php', '?') ?: '/index.php';

$navItems = [
    '/index.php' => 'Home',
    '/search.php' => 'Catalog',
    '/checkout.php' => 'Cart',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'The Editorial Atelier', ENT_QUOTES, 'UTF-8'); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            pearl: '#f3f2f8',
            ink: '#0f172a',
            accent: '#b6452b',
            mist: '#e9ecf8'
          },
          boxShadow: {
            soft: '0 10px 35px rgba(15, 23, 42, 0.08)'
          }
        }
      }
    }
  </script>
</head>
<body class="bg-pearl text-ink min-h-screen flex flex-col antialiased" data-user-id="<?= (int) ($user['id'] ?? 0); ?>">
  <header class="sticky top-0 z-50 border-b border-slate-200/70 bg-white/95 backdrop-blur">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="h-16 flex items-center justify-between gap-4">
        <a href="/index.php" class="text-xl font-semibold tracking-tight">The Editorial Atelier</a>

        <nav class="hidden md:flex items-center gap-2 text-sm">
          <?php foreach ($navItems as $path => $label): ?>
            <?php $active = ($currentPath === $path) || ($path === '/search.php' && str_starts_with($currentPath, '/search.php')) || ($path === '/checkout.php' && str_starts_with($currentPath, '/checkout.php')); ?>
            <a href="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>" class="px-4 py-2 rounded-full transition <?= $active ? 'text-accent border border-accent/25 bg-accent/5' : 'text-slate-600 hover:text-ink hover:bg-slate-100'; ?>">
              <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
            </a>
          <?php endforeach; ?>
        </nav>

        <div class="flex items-center gap-2 sm:gap-3">
          <form method="get" action="/search.php" class="hidden lg:block">
            <div class="rounded-full bg-mist px-4 h-10 flex items-center">
              <input
                type="text"
                name="q"
                placeholder="Search curated pieces..."
                value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                class="bg-transparent outline-none text-sm w-56"
              />
            </div>
          </form>

          <?php if ($user): ?>
            <a href="/user/profile.php" class="hidden sm:inline-flex px-3 py-2 text-sm rounded-xl border border-slate-300 hover:bg-slate-50 transition">Tài khoản</a>
            <a href="/auth/logout.php" class="hidden sm:inline-flex px-3 py-2 text-sm rounded-xl border border-slate-300 hover:bg-slate-50 transition">Đăng xuất</a>
          <?php else: ?>
            <a href="/auth/login.php" class="hidden sm:inline-flex px-3 py-2 text-sm rounded-xl border border-slate-300 hover:bg-slate-50 transition">Đăng nhập</a>
          <?php endif; ?>

          <a href="/checkout.php" class="inline-flex items-center gap-2 rounded-full bg-ink text-white px-4 py-2 text-sm font-medium hover:opacity-90 transition shadow-soft">
            <span>🛒</span><span class="hidden sm:inline">Cart</span>
          </a>
        </div>
      </div>
    </div>
  </header>

  <main class="flex-1">
