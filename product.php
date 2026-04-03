<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
$productId = (int) ($_GET['id'] ?? 0);
$pageTitle = 'Chi tiết sản phẩm';

$stmt = $pdo->prepare('SELECT id, name, description, price, image_url, image_urls, stock FROM products WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

$variants = [];
if ($product) {
    $variantStmt = $pdo->prepare('SELECT id, variant_name, color, size, additional_price, stock FROM product_variants WHERE product_id = :id');
    $variantStmt->execute(['id' => $productId]);
    $variants = $variantStmt->fetchAll();
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

require_once __DIR__ . '/includes/header.php';
?>
<section class="max-w-5xl mx-auto px-4 py-8">
  <?php if (!$product): ?>
    <div class="bg-white rounded-lg border border-gray-200 p-6">Sản phẩm không tồn tại.</div>
  <?php else: ?>
    <div class="grid md:grid-cols-2 gap-6 bg-white rounded-lg border border-gray-200 p-4 md:p-6">
      <div>
        <img id="main-image" src="<?= htmlspecialchars((string) ($images[0] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="rounded-lg w-full h-80 object-cover" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>" />
        <div class="mt-3 grid grid-cols-4 gap-2">
          <?php foreach ($images as $image): ?>
            <img src="<?= htmlspecialchars((string) $image, ENT_QUOTES, 'UTF-8'); ?>" class="thumb h-16 w-full rounded object-cover border cursor-pointer" alt="thumb" />
          <?php endforeach; ?>
        </div>
      </div>
      <div class="space-y-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="text-gray-600 prose prose-sm max-w-none"><?= $allowedDescription; ?></div>
        <p class="text-2xl font-semibold">$<?= number_format((float) $product['price'], 2); ?></p>
        <p class="text-sm text-gray-500">Tồn kho: <?= (int) $product['stock']; ?></p>

        <?php if ($variants): ?>
          <div>
            <label class="text-sm">Biến thể</label>
            <select id="variant-select" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
              <?php foreach ($variants as $variant): ?>
                <option value="<?= (int) $variant['id']; ?>">
                  <?= htmlspecialchars((string) $variant['variant_name'], ENT_QUOTES, 'UTF-8'); ?> - <?= htmlspecialchars((string) ($variant['color'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?> - Size <?= htmlspecialchars((string) ($variant['size'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?> (+$<?= number_format((float) $variant['additional_price'], 2); ?>)
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
  <?php endif; ?>
</section>
<script>
  document.querySelectorAll('.thumb').forEach((thumb) => {
    thumb.addEventListener('click', () => {
      const main = document.querySelector('#main-image');
      if (main) main.src = thumb.src;
    });
  });
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
