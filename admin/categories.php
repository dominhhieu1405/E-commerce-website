<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pagination.php';
require_login();
require_admin();

$pageTitle = 'Admin | Quản lý danh mục';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(422);
        exit('CSRF token không hợp lệ.');
    }

    $action = (string) ($_POST['action'] ?? 'add');
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $slug = trim((string) ($_POST['slug'] ?? ''));

    if ($action === 'delete' && $id > 0) {
        $deleteStmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
        $deleteStmt->execute(['id' => $id]);
    }

    if ($action === 'add' && $name !== '' && $slug !== '') {
        $insertStmt = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (:name, :slug)');
        $insertStmt->execute(['name' => $name, 'slug' => $slug]);
    }

    if ($action === 'edit' && $id > 0 && $name !== '' && $slug !== '') {
        $updateStmt = $pdo->prepare('UPDATE categories SET name = :name, slug = :slug WHERE id = :id');
        $updateStmt->execute(['id' => $id, 'name' => $name, 'slug' => $slug]);
    }

    header('Location: /admin/categories.php');
    exit;
}

$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$totalCategories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$pagination = paginate($totalCategories, $perPage, $currentPage);

$listStmt = $pdo->prepare('SELECT id, name, slug, created_at FROM categories ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$listStmt->bindValue(':limit', (int) $pagination['per_page'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', (int) $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$categories = $listStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<section class="max-w-6xl mx-auto px-4 py-8 space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Quản lý danh mục</h1>
    <div class="flex items-center gap-2">
      <button class="rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition" data-modal-open="add-modal">+ Thêm danh mục</button>
      <a href="/admin/index.php" class="text-sm hover:opacity-80 transition">← Dashboard</a>
    </div>
  </div>

  <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100">
      <tr>
        <th class="p-3 text-left">ID</th>
        <th class="p-3 text-left">Tên</th>
        <th class="p-3 text-left">Slug</th>
        <th class="p-3 text-left">Ngày tạo</th>
        <th class="p-3 text-left">Thao tác</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($categories as $category): ?>
        <tr class="border-t border-gray-100">
          <td class="p-3"><?= (int) $category['id']; ?></td>
          <td class="p-3"><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3"><?= htmlspecialchars((string) $category['slug'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3"><?= htmlspecialchars((string) $category['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3 flex items-center gap-2">
            <button
              class="rounded border px-3 py-1 hover:opacity-80 transition"
              data-modal-open="edit-modal"
              data-id="<?= (int) $category['id']; ?>"
              data-name="<?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?>"
              data-slug="<?= htmlspecialchars((string) $category['slug'], ENT_QUOTES, 'UTF-8'); ?>"
            >Sửa</button>
            <button class="rounded border px-3 py-1 hover:opacity-80 transition" data-modal-open="delete-modal" data-id="<?= (int) $category['id']; ?>" data-name="<?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?>">Xóa</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php render_pagination($pagination); ?>
</section>

<div id="add-modal" class="hidden fixed inset-0 bg-black/50 items-center justify-center p-4">
  <form method="post" class="bg-white rounded-lg p-4 w-full max-w-md space-y-3">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="action" value="add" />
    <h2 class="text-lg font-semibold">Thêm danh mục</h2>
    <input name="name" required placeholder="Tên" class="w-full rounded border border-gray-300 px-3 py-2" />
    <input name="slug" required placeholder="Slug" class="w-full rounded border border-gray-300 px-3 py-2" />
    <div class="flex justify-end gap-2">
      <button type="button" class="rounded border px-3 py-2" data-modal-close>Hủy</button>
      <button class="rounded bg-black text-white px-3 py-2">Lưu</button>
    </div>
  </form>
</div>

<div id="edit-modal" class="hidden fixed inset-0 bg-black/50 items-center justify-center p-4">
  <form method="post" class="bg-white rounded-lg p-4 w-full max-w-md space-y-3">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="action" value="edit" />
    <input type="hidden" name="id" id="edit-id" />
    <h2 class="text-lg font-semibold">Sửa danh mục</h2>
    <input name="name" id="edit-name" required class="w-full rounded border border-gray-300 px-3 py-2" />
    <input name="slug" id="edit-slug" required class="w-full rounded border border-gray-300 px-3 py-2" />
    <div class="flex justify-end gap-2">
      <button type="button" class="rounded border px-3 py-2" data-modal-close>Hủy</button>
      <button class="rounded bg-black text-white px-3 py-2">Cập nhật</button>
    </div>
  </form>
</div>

<div id="delete-modal" class="hidden fixed inset-0 bg-black/50 items-center justify-center p-4">
  <form method="post" class="bg-white rounded-lg p-4 w-full max-w-md space-y-3">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="action" value="delete" />
    <input type="hidden" name="id" id="delete-id" />
    <h2 class="text-lg font-semibold">Xóa danh mục</h2>
    <p>Bạn có chắc chắn muốn xóa <strong id="delete-name"></strong>?</p>
    <div class="flex justify-end gap-2">
      <button type="button" class="rounded border px-3 py-2" data-modal-close>Hủy</button>
      <button class="rounded bg-red-600 text-white px-3 py-2">Xóa</button>
    </div>
  </form>
</div>

<script>
  function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  function closeModal(modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  document.querySelectorAll('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const modalId = button.dataset.modalOpen;
      if (modalId === 'edit-modal') {
        document.getElementById('edit-id').value = button.dataset.id || '';
        document.getElementById('edit-name').value = button.dataset.name || '';
        document.getElementById('edit-slug').value = button.dataset.slug || '';
      }
      if (modalId === 'delete-modal') {
        document.getElementById('delete-id').value = button.dataset.id || '';
        document.getElementById('delete-name').textContent = button.dataset.name || '';
      }
      openModal(modalId);
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => {
      const modal = button.closest('.fixed');
      if (modal) closeModal(modal);
    });
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
