<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
$productId = (int) ($_GET['id'] ?? 0);
$pageTitle = 'Chi tiết sản phẩm';

$stmt = $pdo->prepare('SELECT id, name, description, price, image_url, stock FROM products WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

require_once __DIR__ . '/includes/header.php';
?>
<section class="max-w-5xl mx-auto px-4 py-8">
  <?php if (!$product): ?>
    <div class="bg-white rounded-lg border border-gray-200 p-6">Sản phẩm không tồn tại.</div>
  <?php else: ?>
    <div class="grid md:grid-cols-2 gap-6 bg-white rounded-lg border border-gray-200 p-4 md:p-6">
      <img src="<?= htmlspecialchars((string) $product['image_url'], ENT_QUOTES, 'UTF-8'); ?>" class="rounded-lg w-full h-80 object-cover" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>" />
      <div class="space-y-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="text-gray-600"><?= htmlspecialchars((string) $product['description'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="text-2xl font-semibold">$<?= number_format((float) $product['price'], 2); ?></p>
        <p class="text-sm text-gray-500">Tồn kho: <?= (int) $product['stock']; ?></p>
        <button
          class="add-to-cart rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition"
          data-id="<?= (int) $product['id']; ?>"
          data-name="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>"
          data-price="<?= (float) $product['price']; ?>"
          data-image="<?= htmlspecialchars((string) $product['image_url'], ENT_QUOTES, 'UTF-8'); ?>"
          data-stock="<?= (int) $product['stock']; ?>"
        >
          Thêm vào giỏ
        </button>
      </div>
    </div>
  <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
