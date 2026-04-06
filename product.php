<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
$productId = (int) ($_GET['id'] ?? 0);
$pageTitle = 'Chi tiết sản phẩm';

$stmt = $pdo->prepare(
    "SELECT p.id, p.name, p.description, p.price, p.image_url, p.image_urls, p.stock,
            COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN oi.quantity ELSE 0 END), 0) AS sold_count
     FROM products p
     LEFT JOIN order_items oi ON oi.product_id = p.id
     LEFT JOIN orders o ON o.id = oi.order_id
     WHERE p.id = :id
     GROUP BY p.id
     LIMIT 1"
);
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

$variants = [];
if ($product) {
    $variantStmt = $pdo->prepare('SELECT id, variant_name, additional_price, stock FROM product_variants WHERE product_id = :id');
    $variantStmt->execute(['id' => $productId]);
    $variants = $variantStmt->fetchAll();
}

$reviews = [];
if ($product) {
    $reviewStmt = $pdo->prepare(
        'SELECT DISTINCT r.rating, r.comment, r.created_at, u.username
         FROM reviews r
         INNER JOIN users u ON u.id = r.user_id
         INNER JOIN order_items oi ON oi.order_id = r.order_id
         WHERE oi.product_id = :product_id
         ORDER BY r.created_at DESC'
    );
    $reviewStmt->execute(['product_id' => $productId]);
    $reviews = $reviewStmt->fetchAll();
}

$images = [];
if ($product) {
    $decoded = json_decode((string) ($product['image_urls'] ?? '[]'), true);
    $images = is_array($decoded) ? $decoded : [];
    if (!$images && !empty($product['image_url'])) {
        $images = [(string) $product['image_url']];
    }
}

$allowedDescription = $product ? strip_tags((string) $product['description'], '<p><b><strong><i><em><ul><ol><li><br>') : '';
$shortDescription = $product ? mb_strimwidth(trim(strip_tags((string) $product['description'])), 0, 160, '...') : '';

require_once __DIR__ . '/includes/header.php';
?>
<section class="max-w-5xl mx-auto px-4 py-8 space-y-6">
  <?php if (!$product): ?>
    <div class="bg-white rounded-lg border border-gray-200 p-6">Sản phẩm không tồn tại.</div>
  <?php else: ?>
    <div class="grid md:grid-cols-2 gap-6 bg-white rounded-lg border border-gray-200 p-4 md:p-6">
      <div>
        <img id="main-image" src="<?= htmlspecialchars((string) ($images[0] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="rounded-lg w-full aspect-square object-cover" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>" />
        <div class="mt-3 grid grid-cols-4 gap-2">
          <?php foreach ($images as $image): ?>
            <img src="<?= htmlspecialchars((string) $image, ENT_QUOTES, 'UTF-8'); ?>" class="thumb aspect-square w-full rounded object-cover border cursor-pointer" alt="thumb" />
          <?php endforeach; ?>
        </div>
      </div>
      <div class="space-y-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="text-gray-600"><?= htmlspecialchars($shortDescription, ENT_QUOTES, 'UTF-8'); ?></p>
        <p id="product-price" class="text-2xl font-semibold" data-base-price="<?= (float) $product['price']; ?>">$<?= number_format((float) $product['price'], 2); ?></p>
        <p class="text-sm text-gray-500">Tồn kho: <?= (int) $product['stock']; ?> • Đã bán: <?= (int) $product['sold_count']; ?></p>

        <?php if ($variants): ?>
          <div>
            <label class="text-sm">Biến thể</label>
            <select id="variant-select" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
              <?php foreach ($variants as $variant): ?>
                <option value="<?= (int) $variant['id']; ?>" data-additional-price="<?= (float) $variant['additional_price']; ?>" data-variant-name="<?= htmlspecialchars((string) $variant['variant_name'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?= htmlspecialchars((string) $variant['variant_name'], ENT_QUOTES, 'UTF-8'); ?> (+$<?= number_format((float) $variant['additional_price'], 2); ?>) - Kho: <?= (int) $variant['stock']; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <button
          class="add-to-cart rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition"
          data-id="<?= (int) $product['id']; ?>"
          data-name="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>"
          data-price="<?= (float) $product['price']; ?>"
          data-image="<?= htmlspecialchars((string) ($images[0] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
          data-stock="<?= (int) $product['stock']; ?>"
        >
          Thêm vào giỏ
        </button>
      </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6 space-y-3 max-w-4xl mx-auto">
      <h2 class="text-xl font-bold text-center">Mô tả chi tiết sản phẩm</h2>
      <div class="text-gray-700 prose prose-sm max-w-none"><?= $allowedDescription; ?></div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6 space-y-4">
      <h2 class="text-xl font-bold">Đánh giá sản phẩm</h2>
      <?php if (!$reviews): ?>
        <p class="text-sm text-gray-500">Chưa có đánh giá nào cho sản phẩm này.</p>
      <?php endif; ?>
      <?php foreach ($reviews as $review): ?>
        <article class="border-t border-gray-100 pt-3 first:border-0 first:pt-0">
          <div class="flex items-center justify-between">
            <p class="font-medium"><?= htmlspecialchars((string) $review['username'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="text-xs text-gray-500"><?= htmlspecialchars((string) $review['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
          <p class="text-yellow-500"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']); ?></p>
          <p class="text-sm text-gray-700"><?= nl2br(htmlspecialchars((string) $review['comment'], ENT_QUOTES, 'UTF-8')); ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<script>
  const thumbs = document.querySelectorAll('.thumb');
  thumbs.forEach((thumb) => {
    thumb.addEventListener('click', () => {
      const main = document.querySelector('#main-image');
      if (main) main.src = thumb.src;
    });
  });

  if (thumbs.length > 1) {
    let index = 0;
    setInterval(() => {
      index = (index + 1) % thumbs.length;
      const main = document.querySelector('#main-image');
      if (main) main.src = thumbs[index].src;
    }, 3500);
  }

  const variantSelect = document.querySelector('#variant-select');
  const priceElement = document.querySelector('#product-price');
  if (variantSelect && priceElement) {
    const updateDisplayedPrice = () => {
      const selectedOption = variantSelect.selectedOptions[0];
      const additionalPrice = Number(selectedOption?.dataset?.additionalPrice || 0);
      const basePrice = Number(priceElement.dataset.basePrice || 0);
      const finalPrice = basePrice + additionalPrice;
      priceElement.textContent = `$${finalPrice.toFixed(2)}`;
    };

    variantSelect.addEventListener('change', updateDisplayedPrice);
    updateDisplayedPrice();
  }
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
