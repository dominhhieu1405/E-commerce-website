<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/pagination.php';
require_once __DIR__ . '/includes/format.php';
$pageTitle = 'Home | The Editorial Atelier';

$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$totalProducts = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$pagination = paginate($totalProducts, $perPage, $currentPage);

$stmt = $pdo->prepare(
    "SELECT p.id, p.name, p.description, p.price, p.image_url, p.stock, p.category_id,
            COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN oi.quantity ELSE 0 END), 0) AS sold_count
     FROM products p
     LEFT JOIN order_items oi ON oi.product_id = p.id
     LEFT JOIN orders o ON o.id = oi.order_id
     GROUP BY p.id
     ORDER BY p.created_at DESC
     LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':limit', (int) $pagination['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
  <div class="rounded-3xl overflow-hidden bg-gradient-to-r from-amber-700 to-orange-300 text-white p-8 md:p-14 shadow-soft">
    <p class="text-xs uppercase tracking-[0.22em]">Summer Collection 2026</p>
    <h1 class="mt-4 text-4xl md:text-6xl font-semibold leading-tight max-w-3xl">The Art of Curated Living</h1>
    <p class="mt-5 text-white/90 max-w-xl">Giao diện mới đồng bộ theo phong cách editorial: tối giản, sang trọng và tập trung vào sản phẩm.</p>
    <div class="mt-8 flex flex-wrap gap-3">
      <a href="/search.php" class="rounded-xl bg-white text-ink px-5 py-3 text-sm font-semibold">Shop Now</a>
      <a href="/search.php?sort=newest" class="rounded-xl border border-white/40 px-5 py-3 text-sm font-semibold">New Arrivals</a>
    </div>
  </div>

  <div class="flex items-end justify-between gap-4">
    <div>
      <h2 class="text-3xl font-semibold tracking-tight">New Arrivals</h2>
      <p class="text-slate-500 mt-2">Sản phẩm mới nhất với tinh thần thiết kế thống nhất.</p>
    </div>
    <a href="/search.php" class="text-sm font-medium text-accent hover:underline">View All →</a>
  </div>

  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($products as $product): ?>
      <article class="group rounded-2xl overflow-hidden bg-white border border-slate-200 hover:-translate-y-1 transition duration-300 shadow-soft">
        <a href="/product.php?id=<?= (int) $product['id']; ?>" class="block bg-slate-100">
          <img
            src="<?= htmlspecialchars((string) ($product['image_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>"
            class="aspect-[4/5] w-full object-cover group-hover:scale-[1.02] transition duration-300"
          />
        </a>
        <div class="p-5 space-y-3">
          <h3 class="font-semibold text-xl line-clamp-1"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <p class="text-sm text-slate-500 min-h-[44px]"><?= htmlspecialchars((string) mb_strimwidth(strip_tags((string) $product['description']), 0, 90, '...'), ENT_QUOTES, 'UTF-8'); ?></p>
          <div class="flex items-center justify-between">
            <p class="text-2xl font-semibold text-accent"><?= format_currency_vnd((float) $product['price']); ?></p>
            <span class="text-xs text-slate-500 uppercase tracking-wider">Đã bán <?= format_number_vn((int) $product['sold_count']); ?></span>
          </div>
          <button
            type="button"
            class="add-to-cart w-full rounded-xl bg-ink text-white px-3 py-2.5 text-sm font-medium hover:opacity-90 transition"
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
