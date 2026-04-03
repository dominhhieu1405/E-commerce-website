<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pagination.php';
require_login();
require_admin();

$pageTitle = 'Admin | Danh sách sản phẩm';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(422);
        exit('CSRF token không hợp lệ.');
    }

    if ((string) ($_POST['action'] ?? '') === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $deleteStmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $deleteStmt->execute(['id' => $id]);
    }

    header('Location: /admin/products.php');
    exit;
}

$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$totalProducts = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$pagination = paginate($totalProducts, $perPage, $currentPage);

$listStmt = $pdo->prepare(
    'SELECT p.*, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     ORDER BY p.created_at DESC
     LIMIT :limit OFFSET :offset'
);
$listStmt->bindValue(':limit', (int) $pagination['per_page'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', (int) $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$products = $listStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8 space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Danh sách sản phẩm</h1>
    <div class="flex items-center gap-2">
      <a href="/admin/product_form.php" class="rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition">+ Thêm sản phẩm</a>
      <a href="/admin/index.php" class="text-sm hover:opacity-80 transition">← Dashboard</a>
    </div>
  </div>

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
            <a href="/admin/product_form.php?id=<?= (int) $product['id']; ?>" class="rounded border px-3 py-1 hover:opacity-80 transition">Sửa</a>
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

  <?php render_pagination($pagination); ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
