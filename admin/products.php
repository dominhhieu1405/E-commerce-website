<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_admin();

$pageTitle = 'Admin | Quản lý sản phẩm';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(422);
        exit('CSRF token không hợp lệ.');
    }

    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $deleteStmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $deleteStmt->execute(['id' => $id]);
        header('Location: /admin/products.php');
        exit;
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
    $variantsRaw = trim((string) ($_POST['variants'] ?? ''));
    if ($variantsRaw !== '') {
        $variantInsert = $pdo->prepare(
            'INSERT INTO product_variants (product_id, variant_name, color, size, additional_price, stock)
             VALUES (:product_id, :variant_name, :color, :size, :additional_price, :stock)'
        );

        $lines = preg_split('/\r\n|\r|\n/', $variantsRaw) ?: [];
        foreach ($lines as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 5) continue;
            $variantInsert->execute([
                'product_id' => $id,
                'variant_name' => $parts[0],
                'color' => $parts[1] ?: null,
                'size' => $parts[2] ?: null,
                'additional_price' => (float) $parts[3],
                'stock' => max(0, (int) $parts[4]),
            ]);
        }
    }

    header('Location: /admin/products.php');
    exit;
}

$editingId = (int) ($_GET['edit'] ?? 0);
$productEditing = null;
$variantsText = '';

if ($editingId > 0) {
    $editStmt = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $editStmt->execute(['id' => $editingId]);
    $productEditing = $editStmt->fetch();

    $variantStmt = $pdo->prepare('SELECT * FROM product_variants WHERE product_id = :id');
    $variantStmt->execute(['id' => $editingId]);
    $variants = $variantStmt->fetchAll();
    $variantsText = implode("\n", array_map(static fn ($v) => sprintf('%s|%s|%s|%s|%s', $v['variant_name'], $v['color'], $v['size'], $v['additional_price'], $v['stock']), $variants));
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
$products = $pdo->query('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.created_at DESC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8 space-y-8">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Quản lý sản phẩm</h1>
    <a href="/admin/index.php" class="text-sm hover:opacity-80 transition">← Dashboard</a>
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
      <label class="text-sm">Mô tả (WYSIWYG)</label>
      <div class="mt-1 flex gap-2 mb-2 text-sm">
        <button type="button" data-cmd="bold" class="px-2 py-1 rounded border">B</button>
        <button type="button" data-cmd="italic" class="px-2 py-1 rounded border">I</button>
        <button type="button" data-cmd="insertUnorderedList" class="px-2 py-1 rounded border">• List</button>
      </div>
      <div id="editor" contenteditable="true" class="min-h-[150px] rounded-lg border border-gray-300 px-3 py-2 bg-white"><?= $productEditing ? $productEditing['description'] : ''; ?></div>
      <textarea id="description-input" name="description" class="hidden"></textarea>
    </div>

    <div>
      <label class="text-sm">Biến thể (mỗi dòng: Tên|Màu|Size|Giá thêm|Kho)</label>
      <textarea name="variants" rows="4" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="Basic Black M|Black|M|0|10"><?= htmlspecialchars($variantsText, ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>

    <button class="rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition">
      <?= $productEditing ? 'Cập nhật sản phẩm' : 'Thêm sản phẩm'; ?>
    </button>
  </form>

  <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100">
      <tr>
        <th class="text-left p-3">ID</th>
        <th class="text-left p-3">Tên</th>
        <th class="text-left p-3">Danh mục</th>
        <th class="text-left p-3">Giá</th>
        <th class="text-left p-3">Kho</th>
        <th class="text-left p-3">Thao tác</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($products as $product): ?>
        <tr class="border-t border-gray-100">
          <td class="p-3"><?= (int) $product['id']; ?></td>
          <td class="p-3"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3"><?= htmlspecialchars((string) ($product['category_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3">$<?= number_format((float) $product['price'], 2); ?></td>
          <td class="p-3"><?= (int) $product['stock']; ?></td>
          <td class="p-3 flex gap-2">
            <a href="/admin/products.php?edit=<?= (int) $product['id']; ?>" class="rounded border px-3 py-1 hover:opacity-80 transition">Sửa</a>
            <form method="post" onsubmit="return confirm('Xóa sản phẩm này?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="action" value="delete" />
              <input type="hidden" name="id" value="<?= (int) $product['id']; ?>" />
              <button class="rounded border px-3 py-1 hover:opacity-80 transition">Xóa</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
  document.querySelectorAll('[data-cmd]').forEach((button) => {
    button.addEventListener('click', () => document.execCommand(button.dataset.cmd, false));
  });

  const editor = document.querySelector('#editor');
  const descriptionInput = document.querySelector('#description-input');
  const form = editor.closest('form');
  form.addEventListener('submit', () => {
    descriptionInput.value = editor.innerHTML;
  });

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
