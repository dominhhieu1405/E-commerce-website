<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/format.php';
$productId = (int) ($_GET['id'] ?? 0);
$pageTitle = 'Product Details | The Editorial Atelier';

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
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
  <?php if (!$product): ?>
    <div class="bg-white rounded-2xl border border-slate-200 p-6">Sản phẩm không tồn tại.</div>
  <?php else: ?>
    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Home · Catalog · Product</p>

    <div class="grid lg:grid-cols-[100px_minmax(0,1fr)_420px] gap-4 lg:gap-8">
      <div class="order-2 lg:order-1 flex lg:flex-col gap-3 overflow-x-auto lg:overflow-visible pb-1">
        <?php foreach ($images as $image): ?>
          <img src="<?= htmlspecialchars((string) $image, ENT_QUOTES, 'UTF-8'); ?>" class="thumb h-20 w-20 lg:h-24 lg:w-full shrink-0 rounded-xl object-cover border border-slate-200 cursor-pointer" alt="thumb" />
        <?php endforeach; ?>
      </div>

      <div class="order-1 lg:order-2 rounded-3xl border border-slate-200 bg-white p-3 md:p-4">
        <img id="main-image" src="<?= htmlspecialchars((string) ($images[0] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="rounded-2xl w-full aspect-[4/5] object-cover" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>" />
      </div>

      <div class="order-3 space-y-5 bg-white rounded-3xl border border-slate-200 p-6 h-fit">
        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Summer Collection</p>
        <h1 class="text-4xl font-semibold leading-tight"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="text-slate-600"><?= htmlspecialchars($shortDescription, ENT_QUOTES, 'UTF-8'); ?></p>

        <p id="product-price" class="text-4xl font-semibold text-accent" data-base-price="<?= (float) $product['price']; ?>"><?= format_currency_vnd((float) $product['price']); ?></p>
        <p class="text-sm text-slate-500">Tồn kho: <?= format_number_vn((int) $product['stock']); ?> · Đã bán: <?= format_number_vn((int) $product['sold_count']); ?></p>

        <?php if ($variants): ?>
          <div>
            <label class="text-xs uppercase tracking-[0.14em] text-slate-500">Biến thể</label>
            <select id="variant-select" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5">
              <?php foreach ($variants as $variant): ?>
                <option value="<?= (int) $variant['id']; ?>" data-additional-price="<?= (float) $variant['additional_price']; ?>" data-variant-name="<?= htmlspecialchars((string) $variant['variant_name'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?= htmlspecialchars((string) $variant['variant_name'], ENT_QUOTES, 'UTF-8'); ?> (+<?= format_currency_vnd((float) $variant['additional_price']); ?>) - Kho: <?= format_number_vn((int) $variant['stock']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <button
          class="add-to-cart w-full rounded-xl bg-gradient-to-r from-accent to-orange-400 text-white px-4 py-3 text-base font-semibold hover:opacity-90 transition"
          data-id="<?= (int) $product['id']; ?>"
          data-name="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>"
          data-price="<?= (float) $product['price']; ?>"
          data-image="<?= htmlspecialchars((string) ($images[0] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
          data-stock="<?= (int) $product['stock']; ?>"
        >
          Add to Cart
        </button>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 p-6 space-y-4">
        <h2 class="text-2xl font-semibold">Description</h2>
        <div class="text-slate-700 prose prose-sm max-w-none"><?= $allowedDescription; ?></div>
      </div>

      <div class="bg-white rounded-3xl border border-slate-200 p-6 space-y-4">
        <h2 class="text-2xl font-semibold">Reviews</h2>
        <?php if (!$reviews): ?>
          <p class="text-sm text-slate-500">Chưa có đánh giá nào cho sản phẩm này.</p>
        <?php endif; ?>
        <?php foreach ($reviews as $review): ?>
          <article class="border-t border-slate-100 pt-3 first:border-0 first:pt-0">
            <div class="flex items-center justify-between gap-3">
              <p class="font-medium"><?= htmlspecialchars((string) $review['username'], ENT_QUOTES, 'UTF-8'); ?></p>
              <p class="text-xs text-slate-500"><?= htmlspecialchars((string) $review['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <p class="text-amber-500 text-sm"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']); ?></p>
            <p class="text-sm text-slate-700"><?= nl2br(htmlspecialchars((string) $review['comment'], ENT_QUOTES, 'UTF-8')); ?></p>
          </article>
        <?php endforeach; ?>
      </div>
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
      priceElement.textContent = `${new Intl.NumberFormat('vi-VN').format(finalPrice)} ₫`;
    };

    variantSelect.addEventListener('change', updateDisplayedPrice);
    updateDisplayedPrice();
  }
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
