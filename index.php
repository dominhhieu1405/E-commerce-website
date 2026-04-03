<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/pagination.php';
$pageTitle = 'Trang chủ | Minimal Store';

$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$totalProducts = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$pagination = paginate($totalProducts, $perPage, $currentPage);

$stmt = $pdo->prepare('SELECT id, name, description, price, image_url, stock, category_id FROM products ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', (int) $pagination['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8">
  <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3 mb-8">
    <div>
      <h1 class="text-3xl font-bold">Sản phẩm mới</h1>
      <p class="text-gray-500 mt-2">Thiết kế tối giản, tập trung trải nghiệm mua hàng nhanh.</p>
    </div>
    <button id="cart-counter" class="rounded-lg bg-black text-white px-4 py-2 text-sm">Giỏ hàng: 0</button>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($products as $product): ?>
      <article class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
        <a href="/product.php?id=<?= (int) $product['id']; ?>">
          <img
            src="<?= htmlspecialchars((string) ($product['image_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>"
            class="h-52 w-full object-cover"
          />
        </a>
        <div class="p-4 space-y-3">
          <h2 class="font-semibold text-lg line-clamp-1">
            <?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>
          </h2>
          <p class="text-sm text-gray-500 min-h-[44px]">
            <?= htmlspecialchars((string) mb_strimwidth(strip_tags((string) $product['description']), 0, 95, '...'), ENT_QUOTES, 'UTF-8'); ?>
          </p>
          <div class="flex items-center justify-between">
            <p class="font-bold">$<?= number_format((float) $product['price'], 2); ?></p>
            <span class="text-xs text-gray-500">Kho: <?= (int) $product['stock']; ?></span>
          </div>
          <button
            type="button"
            class="add-to-cart w-full rounded-lg border border-black px-3 py-2 text-sm font-medium hover:opacity-80 transition"
            data-id="<?= (int) $product['id']; ?>"
            data-name="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>"
            data-price="<?= (float) $product['price']; ?>"
            data-image="<?= htmlspecialchars((string) ($product['image_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            data-stock="<?= (int) $product['stock']; ?>"
          >
            Thêm vào giỏ
          </button>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <?php render_pagination($pagination); ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
