<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_admin();

$pageTitle = 'Admin | Thêm/Sửa sản phẩm';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(422);
        exit('CSRF token không hợp lệ.');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $categoryId = (int) ($_POST['category_id'] ?? 0) ?: null;
    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $price = (float) ($_POST['price'] ?? 0);
    $stock = (int) ($_POST['stock'] ?? 0);
    $currentImageUrls = json_decode((string) ($_POST['current_image_urls'] ?? '[]'), true);
    $imageUrls = is_array($currentImageUrls) ? $currentImageUrls : [];

    if (!empty($_FILES['images']['name'][0])) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        foreach ($_FILES['images']['name'] as $idx => $filename) {
            if (!is_uploaded_file($_FILES['images']['tmp_name'][$idx])) {
                continue;
            }
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $safeName = uniqid('product_', true) . '.' . strtolower($extension);
            $targetFile = $uploadDir . $safeName;
            if (move_uploaded_file($_FILES['images']['tmp_name'][$idx], $targetFile)) {
                $imageUrls[] = '/uploads/' . $safeName;
            }
        }
    }

    $imageUrl = $imageUrls[0] ?? null;

    if ($id > 0) {
        $updateStmt = $pdo->prepare(
            'UPDATE products
             SET category_id = :category_id, name = :name, description = :description, price = :price, image_url = :image_url, image_urls = :image_urls, stock = :stock
             WHERE id = :id'
        );
        $updateStmt->execute([
            'id' => $id,
            'category_id' => $categoryId,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'image_url' => $imageUrl,
            'image_urls' => json_encode(array_values($imageUrls), JSON_UNESCAPED_UNICODE),
            'stock' => $stock,
        ]);
    } else {
        $insertStmt = $pdo->prepare(
            'INSERT INTO products (category_id, name, description, price, image_url, image_urls, stock)
             VALUES (:category_id, :name, :description, :price, :image_url, :image_urls, :stock)'
        );
        $insertStmt->execute([
            'category_id' => $categoryId,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'image_url' => $imageUrl,
            'image_urls' => json_encode(array_values($imageUrls), JSON_UNESCAPED_UNICODE),
            'stock' => $stock,
        ]);
        $id = (int) $pdo->lastInsertId();
    }

    $pdo->prepare('DELETE FROM product_variants WHERE product_id = :product_id')->execute(['product_id' => $id]);
    $variantNames = $_POST['variant_name'] ?? [];
    $variantPrices = $_POST['variant_price'] ?? [];
    $variantStocks = $_POST['variant_stock'] ?? [];

    if (is_array($variantNames) && is_array($variantPrices) && is_array($variantStocks)) {
        $variantInsert = $pdo->prepare(
            'INSERT INTO product_variants (product_id, variant_name, additional_price, stock)
             VALUES (:product_id, :variant_name, :additional_price, :stock)'
        );

        foreach ($variantNames as $index => $variantName) {
            $trimmedName = trim((string) $variantName);
            if ($trimmedName === '') {
                continue;
            }

            $variantInsert->execute([
                'product_id' => $id,
                'variant_name' => $trimmedName,
                'additional_price' => (float) ($variantPrices[$index] ?? 0),
                'stock' => max(0, (int) ($variantStocks[$index] ?? 0)),
            ]);
        }
    }

    header('Location: /admin/products.php');
    exit;
}

$editingId = (int) ($_GET['id'] ?? 0);
$productEditing = null;
$variants = [];

if ($editingId > 0) {
    $editStmt = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $editStmt->execute(['id' => $editingId]);
    $productEditing = $editStmt->fetch();

    $variantStmt = $pdo->prepare('SELECT * FROM product_variants WHERE product_id = :id');
    $variantStmt->execute(['id' => $editingId]);
    $variants = $variantStmt->fetchAll();
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css" rel="stylesheet">
<section class="max-w-6xl mx-auto px-4 py-8 space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold"><?= $productEditing ? 'Sửa sản phẩm' : 'Thêm sản phẩm'; ?></h1>
    <a href="/admin/products.php" class="text-sm hover:opacity-80 transition">← Danh sách sản phẩm</a>
  </div>

  <form method="post" enctype="multipart/form-data" class="bg-white rounded-lg border border-gray-200 p-5 space-y-4">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="id" value="<?= (int) ($productEditing['id'] ?? 0); ?>" />
    <input type="hidden" name="current_image_urls" value="<?= htmlspecialchars((string) ($productEditing['image_urls'] ?? '[]'), ENT_QUOTES, 'UTF-8'); ?>" />

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm">Tên sản phẩm</label>
        <input name="name" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" value="<?= htmlspecialchars((string) ($productEditing['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div>
        <label class="text-sm">Danh mục</label>
        <select name="category_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
          <option value="">-- Chọn danh mục --</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= (int) $category['id']; ?>" <?= (int) ($productEditing['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : ''; ?>>
              <?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm">Giá</label>
        <input type="number" step="0.01" min="0" name="price" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" value="<?= htmlspecialchars((string) ($productEditing['price'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div>
        <label class="text-sm">Tồn kho</label>
        <input type="number" min="0" name="stock" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" value="<?= htmlspecialchars((string) ($productEditing['stock'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="md:col-span-2">
        <label class="text-sm">Ảnh sản phẩm (chọn nhiều)</label>
        <input id="images-input" type="file" name="images[]" accept="image/*" multiple class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
        <div id="images-preview" class="mt-3 grid grid-cols-3 md:grid-cols-6 gap-2"></div>
      </div>
    </div>

    <div>
      <label class="text-sm">Mô tả</label>
      <textarea id="summernote" name="description" class="mt-1"><?= htmlspecialchars((string) ($productEditing['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>

    <div>
      <div class="flex items-center justify-between mb-2">
        <label class="text-sm">Biến thể</label>
        <button id="add-variant" type="button" class="rounded border px-3 py-1 text-sm hover:opacity-80 transition">+ Thêm biến thể</button>
      </div>
      <div id="variants-list" class="space-y-2"></div>
    </div>

    <button class="rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition">
      <?= $productEditing ? 'Cập nhật sản phẩm' : 'Thêm sản phẩm'; ?>
    </button>
  </form>
</section>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.js"></script>
<script>
  $('#summernote').summernote({
    placeholder: 'Nhập mô tả sản phẩm...',
    height: 220
  });

  const variantsList = document.querySelector('#variants-list');
  const existingVariants = <?= json_encode(array_map(static fn ($variant) => [
      'name' => $variant['variant_name'] ?? '',
      'price' => (float) ($variant['additional_price'] ?? 0),
      'stock' => (int) ($variant['stock'] ?? 0),
  ], $variants), JSON_UNESCAPED_UNICODE); ?>;

  function variantRow(data = {name: '', price: 0, stock: 0}) {
    const wrapper = document.createElement('div');
    wrapper.className = 'grid md:grid-cols-12 gap-2 border rounded p-2';
    wrapper.innerHTML = `
      <input name="variant_name[]" required placeholder="Tên biến thể" value="${data.name}" class="md:col-span-5 rounded border border-gray-300 px-3 py-2" />
      <input name="variant_price[]" type="number" step="0.01" min="0" value="${data.price}" placeholder="Giá" class="md:col-span-3 rounded border border-gray-300 px-3 py-2" />
      <input name="variant_stock[]" type="number" min="0" value="${data.stock}" placeholder="Số lượng" class="md:col-span-3 rounded border border-gray-300 px-3 py-2" />
      <button type="button" class="md:col-span-1 rounded border px-3 py-2 hover:opacity-80 transition remove-variant">X</button>
    `;
    wrapper.querySelector('.remove-variant').addEventListener('click', () => wrapper.remove());
    variantsList.appendChild(wrapper);
  }

  if (existingVariants.length > 0) {
    existingVariants.forEach((variant) => variantRow(variant));
  } else {
    variantRow();
  }

  document.querySelector('#add-variant').addEventListener('click', () => variantRow());

  const input = document.querySelector('#images-input');
  const preview = document.querySelector('#images-preview');
  input.addEventListener('change', () => {
    preview.innerHTML = '';
    [...input.files].forEach((file) => {
      const reader = new FileReader();
      reader.onload = (event) => {
        const img = document.createElement('img');
        img.src = event.target.result;
        img.className = 'h-20 w-full object-cover rounded border';
        preview.appendChild(img);
      };
      reader.readAsDataURL(file);
    });
  });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
