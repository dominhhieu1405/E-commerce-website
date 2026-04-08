<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../includes/format.php';
require_login();
require_admin();

$pageTitle = 'Admin | Quản lý đánh giá';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(422);
        exit('CSRF token không hợp lệ.');
    }

    if ((string) ($_POST['action'] ?? '') === 'delete') {
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        if ($reviewId > 0) {
            $stmt = $pdo->prepare('DELETE FROM reviews WHERE id = :id');
            $stmt->execute(['id' => $reviewId]);
        }
    }

    header('Location: /admin/reviews.php');
    exit;
}

$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$totalReviews = (int) $pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn();
$pagination = paginate($totalReviews, $perPage, $currentPage);

$stmt = $pdo->prepare(
    'SELECT r.id, r.rating, r.comment, r.created_at, r.order_id, u.username
     FROM reviews r
     INNER JOIN users u ON u.id = r.user_id
     ORDER BY r.created_at DESC
     LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', (int) $pagination['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>
<section class="space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Quản lý đánh giá</h1>
  </div>

  <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100">
      <tr>
        <th class="p-3 text-left">ID</th>
        <th class="p-3 text-left">Đơn hàng</th>
        <th class="p-3 text-left">Khách hàng</th>
        <th class="p-3 text-left">Số sao</th>
        <th class="p-3 text-left">Nội dung</th>
        <th class="p-3 text-left">Ngày tạo</th>
        <th class="p-3 text-left">Thao tác</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($reviews as $review): ?>
        <tr class="border-t border-gray-100 align-top">
          <td class="p-3"><?= (int) $review['id']; ?></td>
          <td class="p-3">#<?= (int) $review['order_id']; ?></td>
          <td class="p-3"><?= htmlspecialchars((string) $review['username'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3"><?= format_number_vn((int) $review['rating']); ?>/5</td>
          <td class="p-3 max-w-md"><?= nl2br(htmlspecialchars((string) $review['comment'], ENT_QUOTES, 'UTF-8')); ?></td>
          <td class="p-3"><?= htmlspecialchars((string) $review['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="p-3">
            <form method="post" onsubmit="return confirm('Xóa đánh giá này?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="action" value="delete" />
              <input type="hidden" name="review_id" value="<?= (int) $review['id']; ?>" />
              <button class="rounded border border-red-300 text-red-700 px-3 py-1 hover:opacity-80 transition">Xóa</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php render_pagination($pagination); ?>
</section>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
