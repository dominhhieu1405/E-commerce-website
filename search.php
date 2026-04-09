<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/pagination.php';
require_once __DIR__ . '/includes/format.php';
$pageTitle = 'Catalog | The Editorial Atelier';

$q = trim((string) ($_GET['q'] ?? ''));
$minPrice = isset($_GET['min_price']) ? (float) $_GET['min_price'] : null;
$maxPrice = $_GET['max_price'] ?? 999999999;
if (!is_numeric($maxPrice)) $maxPrice = 999999999;
$categoryId = (int) ($_GET['category_id'] ?? 0);
$inStock = (($_GET['in_stock'] ?? '') === '1');
$sort = (string) ($_GET['sort'] ?? 'newest');

$sortMap = [
    'newest' => 'p.created_at DESC',
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name_asc' => 'p.name ASC',
    'stock_desc' => 'p.stock DESC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['newest'];

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(p.name LIKE :q_name OR p.description LIKE :q_desc)';
    $params['q_name'] = '%' . $q . '%';
    $params['q_desc'] = '%' . $q . '%';
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = :category_id';
    $params['category_id'] = $categoryId;
}
if ($minPrice !== null && $minPrice >= 0) {
    $where[] = 'p.price >= :min_price';
    $params['min_price'] = $minPrice;
}
if ($maxPrice !== null && $maxPrice >= 0) {
    $where[] = 'p.price <= :max_price';
    $params['max_price'] = $maxPrice;
}
if ($inStock) {
    $where[] = 'p.stock > 0';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p {$whereSql}");
$countStmt->execute($params);
$totalProducts = (int) $countStmt->fetchColumn();
$pagination = paginate($totalProducts, $perPage, $currentPage);

$sql = "SELECT p.id, p.name, p.description, p.price, p.image_url, p.stock, c.name AS category_name,
               COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN oi.quantity ELSE 0 END), 0) AS sold_count
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN order_items oi ON oi.product_id = p.id
        LEFT JOIN orders o ON o.id = oi.order_id
        {$whereSql}
        GROUP BY p.id, c.name
        ORDER BY {$orderBy}
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', (int) $pagination['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <div class="flex items-end justify-between gap-4 mb-6">
    <div>
      <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Home · Catalog</p>
      <h1 class="text-4xl font-semibold mt-2">Collection</h1>
      <p class="text-slate-500 mt-1">Showing <?= $totalProducts; ?> products</p>
    </div>
  </div>

  <div class="grid lg:grid-cols-[280px_minmax(0,1fr)] gap-6">
    <form method="get" class="h-fit bg-white rounded-3xl border border-slate-200 p-5 space-y-4">
      <h2 class="font-semibold text-lg">Filter</h2>
      <div>
        <label class="text-xs uppercase tracking-[0.14em] text-slate-500">Keyword</label>
        <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5" placeholder="Search name..." />
      </div>
      <div>
        <label class="text-xs uppercase tracking-[0.14em] text-slate-500">Category</label>
        <select name="category_id" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5">
          <option value="0">All categories</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= (int) $category['id']; ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs uppercase tracking-[0.14em] text-slate-500">Min</label>
          <input type="number" min="0" step="1" name="min_price" value="<?= htmlspecialchars((string) ($_GET['min_price'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
        </div>
        <div>
          <label class="text-xs uppercase tracking-[0.14em] text-slate-500">Max</label>
          <input type="number" min="0" step="1" name="max_price" value="<?= htmlspecialchars((string) ($_GET['max_price'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
        </div>
      </div>
      <div>
        <label class="text-xs uppercase tracking-[0.14em] text-slate-500">Sort</label>
        <select name="sort" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
          <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : ''; ?>>Price ↑</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : ''; ?>>Price ↓</option>
          <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : ''; ?>>Name A-Z</option>
          <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : ''; ?>>Stock high</option>
        </select>
      </div>
      <label class="inline-flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="in_stock" value="1" <?= $inStock ? 'checked' : ''; ?> class="rounded border-slate-300" /> In stock only
      </label>
      <div class="grid grid-cols-2 gap-2 pt-1">
        <button class="rounded-xl bg-ink text-white px-4 py-2.5 text-sm">Apply</button>
        <a href="/search.php" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-center">Reset</a>
      </div>
    </form>

    <div class="space-y-6">
      <?php if (!$products): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-6 text-slate-500">Không có kết quả phù hợp.</div>
      <?php endif; ?>

      <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php foreach ($products as $product): ?>
          <article class="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:-translate-y-1 transition shadow-soft">
            <a href="/product.php?id=<?= (int) $product['id']; ?>">
              <img src="<?= htmlspecialchars((string) ($product['image_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>" class="aspect-square w-full object-cover" />
            </a>
            <div class="p-4 space-y-2.5">
              <p class="text-xs uppercase tracking-[0.14em] text-slate-500"><?= htmlspecialchars((string) ($product['category_name'] ?? 'Uncategorized'), ENT_QUOTES, 'UTF-8'); ?></p>
              <h2 class="font-semibold text-xl line-clamp-1"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
              <div class="flex items-center justify-between">
                <p class="font-semibold text-xl text-accent"><?= format_currency_vnd((float) $product['price']); ?></p>
                <span class="text-xs text-slate-500">Sold <?= format_number_vn((int) $product['sold_count']); ?></span>
              </div>
              <button type="button" class="add-to-cart w-full rounded-xl bg-mist text-ink px-3 py-2.5 text-sm font-medium hover:bg-ink hover:text-white transition" data-id="<?= (int) $product['id']; ?>" data-name="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>" data-price="<?= (float) $product['price']; ?>" data-image="<?= htmlspecialchars((string) ($product['image_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-stock="<?= (int) $product['stock']; ?>">Thêm vào giỏ</button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <?php render_pagination($pagination); ?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
