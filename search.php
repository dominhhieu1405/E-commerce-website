<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
$pageTitle = 'Tìm kiếm sản phẩm';

$q = trim((string) ($_GET['q'] ?? ''));
$minPrice = isset($_GET['min_price']) ? (float) $_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) ? (float) $_GET['max_price'] : null;
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
    $where[] = '(p.name LIKE :q OR p.description LIKE :q)';
    $params['q'] = '%' . $q . '%';
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

$sql = "SELECT p.id, p.name, p.description, p.price, p.image_url, p.stock, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        {$whereSql}
        ORDER BY {$orderBy}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8 space-y-6">
  <div>
    <h1 class="text-3xl font-bold">Tìm kiếm sản phẩm</h1>
    <p class="text-gray-500 mt-2">Tìm nhanh theo từ khóa, danh mục, giá, tồn kho và sắp xếp.</p>
  </div>

  <form method="get" class="bg-white rounded-lg border border-gray-200 p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
    <div class="lg:col-span-2">
      <label class="text-sm">Từ khóa</label>
      <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Tên hoặc mô tả..." class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
    </div>
    <div>
      <label class="text-sm">Danh mục</label>
      <select name="category_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
        <option value="0">Tất cả</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?= (int) $category['id']; ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm">Giá từ</label>
      <input type="number" min="0" step="0.01" name="min_price" value="<?= htmlspecialchars((string) ($_GET['min_price'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
    </div>
    <div>
      <label class="text-sm">Giá đến</label>
      <input type="number" min="0" step="0.01" name="max_price" value="<?= htmlspecialchars((string) ($_GET['max_price'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
    </div>
    <div>
      <label class="text-sm">Sắp xếp</label>
      <select name="sort" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : ''; ?>>Giá tăng dần</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : ''; ?>>Giá giảm dần</option>
        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
        <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : ''; ?>>Tồn kho cao</option>
      </select>
    </div>

    <label class="lg:col-span-6 inline-flex items-center gap-2 text-sm">
      <input type="checkbox" name="in_stock" value="1" <?= $inStock ? 'checked' : ''; ?> class="rounded border-gray-300" />
      Chỉ hiện sản phẩm còn hàng
    </label>

    <div class="lg:col-span-6 flex gap-3">
      <button class="rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition">Áp dụng bộ lọc</button>
      <a href="/search.php" class="rounded-lg border border-black px-4 py-2 hover:opacity-80 transition">Xóa lọc</a>
    </div>
  </form>

  <p class="text-sm text-gray-600">Tìm thấy <strong><?= count($products); ?></strong> sản phẩm.</p>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (!$products): ?>
      <div class="sm:col-span-2 lg:col-span-3 bg-white rounded-lg border border-gray-200 p-6 text-gray-500">Không có kết quả phù hợp.</div>
    <?php endif; ?>

    <?php foreach ($products as $product): ?>
      <article class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
        <a href="/product.php?id=<?= (int) $product['id']; ?>">
          <img src="<?= htmlspecialchars((string) ($product['image_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>" class="h-52 w-full object-cover" />
        </a>
        <div class="p-4 space-y-3">
          <p class="text-xs text-gray-500"><?= htmlspecialchars((string) ($product['category_name'] ?? 'Chưa phân loại'), ENT_QUOTES, 'UTF-8'); ?></p>
          <h2 class="font-semibold text-lg"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
          <p class="text-sm text-gray-500 min-h-[44px]"><?= htmlspecialchars((string) mb_strimwidth(strip_tags((string) $product['description']), 0, 95, '...'), ENT_QUOTES, 'UTF-8'); ?></p>
          <div class="flex items-center justify-between">
            <p class="font-bold">$<?= number_format((float) $product['price'], 2); ?></p>
            <span class="text-xs text-gray-500">Kho: <?= (int) $product['stock']; ?></span>
          </div>
          <button type="button" class="add-to-cart w-full rounded-lg border border-black px-3 py-2 text-sm font-medium hover:opacity-80 transition" data-id="<?= (int) $product['id']; ?>" data-name="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>" data-price="<?= (float) $product['price']; ?>" data-image="<?= htmlspecialchars((string) ($product['image_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-stock="<?= (int) $product['stock']; ?>">Thêm vào giỏ</button>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
