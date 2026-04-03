<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
$pageTitle = 'Admin | Quản lý sản phẩm';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $deleteStmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $deleteStmt->execute(['id' => $id]);
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $price = (float) ($_POST['price'] ?? 0);
        $stock = (int) ($_POST['stock'] ?? 0);
        $imageUrl = trim((string) ($_POST['current_image'] ?? ''));

        if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $safeName = uniqid('product_', true) . '.' . strtolower($extension);
            $targetFile = $uploadDir . $safeName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imageUrl = '/uploads/' . $safeName;
            }
        }

        if ($id > 0) {
            $updateStmt = $pdo->prepare(
                'UPDATE products
                 SET name = :name, description = :description, price = :price, image_url = :image_url, stock = :stock
                 WHERE id = :id'
            );
            $updateStmt->execute([
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'image_url' => $imageUrl,
                'stock' => $stock,
            ]);
        } else {
            $insertStmt = $pdo->prepare(
                'INSERT INTO products (name, description, price, image_url, stock)
                 VALUES (:name, :description, :price, :image_url, :stock)'
            );
            $insertStmt->execute([
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'image_url' => $imageUrl,
                'stock' => $stock,
            ]);
        }
    }

    header('Location: /admin/products.php');
    exit;
}

$editingId = (int) ($_GET['edit'] ?? 0);
$productEditing = null;

if ($editingId > 0) {
    $editStmt = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $editStmt->execute(['id' => $editingId]);
    $productEditing = $editStmt->fetch();
}

$productsStmt = $pdo->query('SELECT * FROM products ORDER BY created_at DESC');
$products = $productsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8 space-y-8">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Quản lý sản phẩm</h1>
    <a href="/admin/index.php" class="text-sm hover:opacity-80 transition">← Dashboard</a>
  </div>

  <form method="post" enctype="multipart/form-data" class="bg-white rounded-lg border border-gray-200 p-5 space-y-4">
    <input type="hidden" name="id" value="<?= (int) ($productEditing['id'] ?? 0); ?>" />
    <input type="hidden" name="current_image" value="<?= htmlspecialchars((string) ($productEditing['image_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" />

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm">Tên sản phẩm</label>
        <input name="name" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" value="<?= htmlspecialchars((string) ($productEditing['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div>
        <label class="text-sm">Giá</label>
        <input type="number" step="0.01" min="0" name="price" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" value="<?= htmlspecialchars((string) ($productEditing['price'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div>
        <label class="text-sm">Tồn kho</label>
        <input type="number" min="0" name="stock" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" value="<?= htmlspecialchars((string) ($productEditing['stock'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div>
        <label class="text-sm">Ảnh sản phẩm</label>
        <input type="file" name="image" accept="image/*" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
      </div>
    </div>

    <div>
      <label class="text-sm">Mô tả</label>
      <textarea name="description" required rows="4" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"><?= htmlspecialchars((string) ($productEditing['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
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
          <td class="p-3">$<?= number_format((float) $product['price'], 2); ?></td>
          <td class="p-3"><?= (int) $product['stock']; ?></td>
          <td class="p-3 flex gap-2">
            <a href="/admin/products.php?edit=<?= (int) $product['id']; ?>" class="rounded border px-3 py-1 hover:opacity-80 transition">Sửa</a>
            <form method="post" onsubmit="return confirm('Xóa sản phẩm này?')">
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
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
